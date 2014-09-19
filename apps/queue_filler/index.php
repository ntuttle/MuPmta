<?php
define('output','html');
require_once __DIR__.'/../../core/core.php';
Req('queue_filler/class/queue_filler.class.php',APPS);
www::ScriptHead('PMTA Configuration Builder');
$QF = new queue_filler($CFG->DB,$argv);

?>