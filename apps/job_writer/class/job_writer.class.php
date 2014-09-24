<?php
class job_writer {
  
  var $DB;
  var $ID;
  var $ALERTS;

  public function __construct($CFG)
    {
      $this->DB = $CFG->DB;
      $this->CheckLineup();
    }
  /**
   * CheckLineup
   * -------------------------
   **/
  public function CheckLineup()
    {
      $Q = $this->DB->GET('MUP.jobs.lineup__'.strtoupper(hostname),['status'=>'PENDING','send_date__<='=>'NOW()','active'=>1],['id','listID','json_params'],10);
      if(!empty($Q)){
        $Q = isset($Q['id'])?[$Q['id']=>$Q]:$Q;
        foreach($Q as $i=>$q){
          $IDs[$i] = $i;
          $this->Lineup[$i] = $q;
        }
        $this->ClaimLineup($IDs);
        $this->BuildJobs();
      }
    }
  /**
   * ClaimLineup
   * -------------------------
   **/
  public function ClaimLineup($IDs)
    {
      $count = count($IDs);
      $this->DB->SET('MUP.jobs.lineup__'.strtoupper(hostname),['status'=>'BUILDING'],['id'=>$IDs],$count);
      if($this->DB->aR < $count)
        $this->ALERTS[] = FAIL('Problem with claiming lineup ids!'.Debug($this->DB));
    }
  /**
   * BuildJobs
   * -------------------------
   **/
  public function BuildJobs()
    {
      
    }
}
?>