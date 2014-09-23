<?php

class logger {
  
  var $i = 0;
  var $batchSize = 1;
  var $DATA;
  var $DB;

  public function __construct($CFG)
    {
      $this->DB = $CFG->DB;
    }
  /**
   * ReadLog
   * -------------------------
   **/
  public function ReadLog($DATA)
    {
      if(is_array($DATA)){
        $this->DATA = $DATA;
        $this->ParseLog($DATA);
        if($this->i>=$this->batchSize)
          $this->DB_Insert();
      }
      file_put_contents(LOGS.'apps/logger/data.log',implode(',',$DATA).LF,FILE_APPEND);
    }
  /**
   * DB_Insert
   * -------------------------
   **/
  public function DB_Insert()
    {
      $F = ['type','time','send_date','email','status','error','bounce','jobid','domain','ip','longip','server'];
      if(!empty($this->V)){
        $this->DB->PUT('LOGS.logs.pmta_'.strtoupper(hostname),$F,$V,'DELAYED');
        $this->V = [];
        $this->i = 0;
      }
    }
  /**
   * ParseLog
   * -------------------------
   **/
  public function ParseLog($d)
    {
      $TYPE = $d[0];
      $TIME = date('Y-m-d H:i:s', strtotime($d[1]));
      $SENT = date('Y-m-d H:i:s', strtotime($d[2]));
      $IPD = explode('/', $d[21]);
      $IP = $IPD[1];
      $EMAIL = $d[4];
      $STATUS = $d[7];
      $ERROR = $d[8];
      $BOUNCE = $d[10];
      $JOBID = $d[19];
      $DOMAIN = $IPD[0];
      if(preg_match('/([a-zA-Z0-9\-]+\.[a-zA-Z]+)$/', $DOMAIN, $x))
        $DOMAIN = $x[1];
      if($TYPE == 'tq'){
        $EMAIL = $d[15];
        $BOUNCE = $d[9];
        $SENT = '0000-00-00 00:00:00';
      }elseif($TYPE == 'r'){
        $STATUS = $data[3];
        $ERROR = $data[6];
        $IP = $data[18];
        $BOUNCE = $data[23];
        $DOMAIN = $data[12];
        $SENT = '0000-00-00 00:00:00';
      }
      $this->i ++;
      $this->V[] = [$TYPE,$TIME,$SENT,$EMAIL,$STATUS,$ERROR,$BOUNCE,$JOBID,$DOMAIN,$IP,ip2long($IP),hostID];
    }
}

?>