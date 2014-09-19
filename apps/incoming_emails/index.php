<?php
define('output','html');
require_once __DIR__.'/../../core/core.php';
Req('incoming_emails/class/incoming_emails.class.php',APPS);
www::ScriptHead('Incoming Email Reader');
?>