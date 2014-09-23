<?php
require_once __DIR__.'/../../core/core.php';
Req('incoming_emails/class/incoming_emails.class.php',APPS);
$IE = new incoming_emails($CFG);
$EM = file_get_contents('php://stdin');
$IE->ReadEmail($EM);
$IE->DB_Insert();
file_put_contents(LOGS.'apps/incoming_emails/last.log',Debug($IE));
?>