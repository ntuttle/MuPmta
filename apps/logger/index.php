<?php
define('output','html');
require_once __DIR__.'/../../core/core.php';
Req('class/logger.class.php',__DIR__);
$LOG = new logger($CFG);

echo www::ScriptHead('PMTA Accounting Logger');
echo www::Alt(Debug($LOG));
?>