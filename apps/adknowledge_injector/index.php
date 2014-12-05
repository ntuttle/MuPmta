<?php
  define('output','html');
  require_once __DIR__.'/../../core/core.php';
  Req('config_writer/class/adknowledge_injector.class.php',APPS);
  echo www::ScriptHead('Adknowledge Injector');
  $INJECT = new adknowledge_injector($CFG->DB);
  
  echo "<pre>";
  print_r($INJECT);
  echo "</pre>";
?>