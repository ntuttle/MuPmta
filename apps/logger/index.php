<?php
define('output','html');
require_once __DIR__.'/../../core/core.php';
Req('class/logger.class.php',__DIR__);
www::ScriptHead('PMTA Accounting Logger');
$LOG = new logger($CFG);
echo Debug($LOG);
?>