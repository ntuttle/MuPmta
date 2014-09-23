<?php
require_once __DIR__.'/../../core/core.php';
Req('job_writer/class/job_writer.class.php',APPS);
$JW = new job_writer($CFG);

?>