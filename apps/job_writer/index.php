<?php
define('output','html');
require_once __DIR__.'/../../core/core.php';
Req('job_writer/class/job_writer.class.php',APPS);
www::ScriptHead('Job Writer');
?>