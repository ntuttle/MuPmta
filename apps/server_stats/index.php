<?php
define('output','html');
require_once __DIR__.'/../../core/core.php';
Req('server_stats/class/server_stats.class.php',APPS);
echo www::ScriptHead('Server Stats');
echo www::Btns(['Clear Logs','Pause Traffic']);
$_ = new STATS($CFG->DB);

?>