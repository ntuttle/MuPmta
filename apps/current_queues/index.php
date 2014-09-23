<?php
define('output','html');
require_once __DIR__.'/../../core/core.php';
Req('class/current_queues.class.php',__DIR__);
$CQ = new current_queues($CFG->DB);

echo www::ScriptHead('Current Queues');
$T  = new TBL();
$T  = $T->Make($CQ->DB_Update);
echo www::Alt($T);
?>