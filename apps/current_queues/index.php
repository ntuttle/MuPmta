<?php
define('output','html');
require_once __DIR__.'/../../core/core.php';
Req('current_queues/class/current_queues.class.php',APPS);
www::ScriptHead('Current Queues');
$CQ = new CurrentQueues($CFG->DB);
echo Debug($CQ);
?>