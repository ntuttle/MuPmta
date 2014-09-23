<?php
class job_writer {
  
  var $DB;
  var $ID;

  public function __construct($CFG)
    {
      $this->DB = $CFG->DB;
      $this->CheckLineup();
    }
  public function CheckLineup()
    {
      $Q = $this->DB->GET('MUP.jobs.lineup__'.strtoupper(hostname),['status'=>'PENDING','send_date__<='=>'NOW()','active'=>1],['id','listID','json_params'],10);
      if(!empty($Q)){
        $Q = isset($Q['id'])?[$Q['id']=>$Q]:$Q;
        foreach($Q as $i=>$q){
          $IDs[] = [$i];
          $this->Lineup[$i] = $q;
        }
        $this->BuildJobs();
      }
    }
  public function BuildJobs()
    {
      
    }
}
?>