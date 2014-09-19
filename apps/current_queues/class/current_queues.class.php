<?php
class CurrentQueues {

  var $DB;
  var $cmd   = '/usr/sbin/pmta --dom show topqueues --maxitems=100000 --errors';
  var $regex = '/queue\[([0-9]+)\]\.([a-zA-Z]+)[\[]?([0-9]{0,})[\]]?[\.]?([a-z]{0,})\=\"(.*)\"/';

  public function __construct($DB)
    {
      $this->DB = $DB;
      $this->GetQueues();
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
      exec($this->cmd,$cq);
      if(!empty($cq) && is_array($cq))
        foreach($cq as $q)
          $this->ParseLine($q);
    }
  /**
   * SaveQueueFile
   * -------------------------
   * save the current queues as a json array 
   * -------------------------
   **/
  public function SaveQueueFile()
    {
      if(!empty($this->Queues)){
        $json = json_encode($this->Queues,JSON_PRETTY_PRINT);
        file_put_contents(DATA.'current_queues.json',$json);
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
   * SetDB
   * -------------------------
   * Set IP Details in DB to match queues currently
   * running on this server
   * -------------------------
   **/
  public function SetDB()
    {
      
    }
}
?>