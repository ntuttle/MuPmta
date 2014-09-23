<?php
define('output','html');
require_once __DIR__.'/../../core/core.php';
Req('incoming_emails/class/incoming_emails.class.php',APPS);
echo www::ScriptHead('Incoming Email Reader');

$IE = new incoming_emails($CFG);
$EMAIL = file_get_contents(LOGS.'apps/incoming_emails/last.msg');
$IE->ReadEmail($EMAIL);

$Headers = $IE->ShowHeaders();
$T = new TBL();
$T = $T->Make($Headers).'<br><br>';
$Body = $IE->ShowParts();
$HTML = '<h1>'.$IE->Subject.'</h1>'.$T.$Body;
echo www::Alt($HTML);

?>