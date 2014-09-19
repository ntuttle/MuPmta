<?
define('output','html');
require_once __DIR__ .'/core/core.php';
$WWW = new WWW($CFG);
echo implode(LF,$WWW->HTML);
?>