<?php
class current_queues {

  var $Debug = false;
  var $DB;
  var $cmd = '/usr/sbin/pmta --dom show topqueues --maxitems=100000 --errors';
  var $regex = '/queue\[([0-9]+)\]\.([a-zA-Z]+)[\[]?([0-9]{0,})[\]]?[\.]?([a-z]{0,})\=\"(.*)\"/';

  public function __construct($DB)
    {
      $this->DB = $DB;
      $this->GetQueues();
      $this->ClearDB();
      $this->SetDB();
      $this->SaveQueueFile();
    }
  /**
   * GetQueues
   * -------------------------
   * Query PMTA for the current queues
   * -------------------------
   **/
  public function GetQueues()
    {
      if($this->Debug)
        $cq = $this->TestQueues();
      else
        exec($this->cmd,$cq);
      if(!empty($cq) && is_array($cq)){
        foreach($cq as $q)
          $this->ParseLine($q);
        $this->FormatQueues();
        unset($this->Queues);
      }
    }
  /**
   * TestQueues
   * -------------------------
   **/
  public function TestQueues()
    {
      $f = file_get_contents(APPS.'current_queues/class/testQueues.txt');
      $cq = explode("\n",$f);
      return $cq;
    }
  /**
   * SaveQueueFile
   * -------------------------
   * save the current queues as a json array 
   * -------------------------
   **/
  public function SaveQueueFile()
    {
      if(!empty($this->_Queues)){
        $json = json_encode($this->_Queues,JSON_PRETTY_PRINT);
        file_put_contents(DATA.'current_queues',$json);
        unset($this->_Queues);
      }
    }
  /**
   * ParseLine
   * -------------------------
   * Take the current queue output, and create a working array
   * -------------------------
   **/
  public function ParseLine($q)
    {
      
      if(preg_match($this->regex,$q,$x)){
        if(empty($x[4])){
          $this->Queues[$x[1]][$x[2]] = $x[5];
        }elseif($x[4]!='type'){
          $this->Queues[$x[1]][$x[2]][$x[3]][$x[4]] = $x[5];
        }
      }
    }
  /**
   * FormatQueues
   * -------------------------
   **/
  public function FormatQueues()
    {
      foreach($this->Queues as $i=>$Queue){
        list($TARGET,$IP) = explode('/',$Queue['name'],2);
        $IP = ip2long($IP);
        list($TARGET,$ext) = explode('.',$TARGET,2);
        $RCPT= $Queue['rcp'];
        $RETRY = $Queue['retryTime'];
        $STS = strtoupper(substr($Queue['mode'],0,1));
        $_ = ['queue'=>$RCPT];
        $_['paused'] = $Queue['paused'];
        $_['mode'] = $Queue['mode'];
        $_['retry'] = $RETRY;
        $ERROR = [];
        if(is_array($Queue['event']))
          foreach($Queue['event'] as $E){
            $_['errors'][] = [$E['time']=>$E['text']];
            $ERROR[] = $this->CheckError($E['text']);
          }
        $this->_Queues[$IP][$TARGET] = $_;
        $this->DB_Update[] = ['IP'=>$IP,'TARGET'=>$TARGET,'RCPT'=>$RCPT,'STREAK'=>0,'RETRY'=>$RETRY,'ERROR'=>implode(LF,$ERROR),'STATUS'=>$STS];
      }
    }
  /**
   * CheckError
   * -------------------------
   **/
  public function CheckError($E)
    {
      // Target level errors
      $_['try again later']     = 'SVC_UNAVAIL';
      $_['Service unavailable'] = 'SVC_UNAVAIL';
      $_['(DYN:T1)']            = 'DYN_T1';
      $_['(CON:B1)']            = 'CON_B1';
      $_['AOL will not accept'] = 'REJECT';
      // PMTA level errors
      $_['message rate limit']  = 'PMTA__MSG_RL';
      $_['connect rate limit']  = 'PMTA__CON_RL';
      $_['skip of MX']          = 'PMTA__SKIP_MX';
      $_['All SMTP sources']    = 'PMTA__DISABLED';
      // check for a match
      foreach($_ as $m=>$e)if(strstr($E,$m)){$E = $e;break;}
      return $E;
    }
  /**
   * SetDB
   * -------------------------
   * Set IP Details in DB to match queues currently
   * running on this server
   * -------------------------
   **/
  public function ClearDB()
    {
      $q = "UPDATE `ipconfig`.`queues` INNER JOIN `ipconfig`.`global_config` ON (`global_config`.`ip`=`queues`.`ip`) SET `queues`.`queue` = 0 WHERE `global_config`.`pmta` = ".hostID;
      $this->DB->Q('MUP.ipconfig.queues',$q);
    }
  /**
   * SetDB
   * -------------------------
   **/
  public function SetDB()
    {
      $F = ['ip','target','queue','streak','retry','error','status'];
      $this->DB->PUT('MUP.ipconfig.queues',$F,$this->DB_Update,"UPDATE `queue`=VALUES(`queue`), `retry`=VALUES(`retry`), `error`=VALUES(`error`), `status`=VALUES(`status`), `streak`= CASE WHEN  `status`=VALUES(`status`) THEN `streak`+1  ELSE '0' END");
    }
}
?>