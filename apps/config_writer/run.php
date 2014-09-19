<?php
  require_once __DIR__.'/../../core/core.php';
  Req('config_writer/class/config_writer.class.php',APPS);
  $CONFIG = new config_writer($CFG->DB,$argv);
  $CONFIG = implode(LF,$CONFIG->Conf);
  file_put_contents(DATA.'config',$CONFIG);
  $cmd = 'cp -f '.DATA.'config '.DIR.'../';
  exec($cmd,$r);
  echo Debug($cmd);
  echo Debug($r);
?>