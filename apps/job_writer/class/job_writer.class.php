<?php
class job_writer {
  
  var $DB;
  var $ID;

  public function __construct($CFG)
    {
      $this->DB = $CFG->DB;
    }
}
?>