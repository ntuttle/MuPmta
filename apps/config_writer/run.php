<?php
  require_once __DIR__.'/../../core/core.php';
  Req('config_writer/class/config_writer.class.php',APPS);
  $CONFIG = new config_writer($CFG->DB,$argv);
  print_r($CONFIG);
?>