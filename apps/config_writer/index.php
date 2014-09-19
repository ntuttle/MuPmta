<?php
  define('output','html');
  require_once __DIR__.'/../../core/core.php';
  Req('config_writer/class/config_writer.class.php',APPS);
  echo www::ScriptHead('PMTA Configuration Builder');
  $CONFIG = new config_writer($CFG->DB);
  $CONFIG = implode(LF,$CONFIG->Conf);
  echo www::Btns(['Save','Edit','Upload']);
  echo www::Alt(htmlspecialchars($CONFIG));
  file_put_contents(DATA.'config',$CONFIG);
  exec('cp '.DATA.'config '.CORE.'../');
?>