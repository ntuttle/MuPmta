<?php
require_once __DIR__.'/../../core/core.php';
Req('logger/class/logger.class.php',APPS);
$LOG = new logger($CFG);
while($DATA = fgetcsv(STDIN))
  $LOG->ReadLog($DATA);
$LOG->DB_Insert();
?>