<?php
define('output','html');
require_once __DIR__.'/../../core/core.php';
Req('class/current_queues.class.php',__DIR__);
$CQ = new CurrentQueues($CFG->DB);

echo www::ScriptHead('Current Queues');
echo www::Alt(Debug($CQ->Queues));
?>