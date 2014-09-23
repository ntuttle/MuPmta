<?php
require_once __DIR__.'/../../core/core.php';
Req('class/current_queues.class.php',__DIR__);
$CQ = new CurrentQueues($CFG->DB);
echo Debug(@$CQ->DB_Update);
?>