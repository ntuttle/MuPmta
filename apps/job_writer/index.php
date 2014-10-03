<?php
define('output','html');
require_once __DIR__.'/../../core/core.php';
Req('class/job_writer.class.php',__DIR__);
$JW = new job_writer($CFG);

echo www::ScriptHead('Job Writer');
echo www::Alt(implode('',$JW->ALERTS));

echo www::Alt(Debug($JW));
?>