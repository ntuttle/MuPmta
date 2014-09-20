<?php
define('output','html');
require_once __DIR__.'/../../core/core.php';
Req('class/current_queues.class.php',__DIR__);
www::ScriptHead('Current Queues');
$CQ = new CurrentQueues($CFG->DB);
echo Debug($CQ);
?>