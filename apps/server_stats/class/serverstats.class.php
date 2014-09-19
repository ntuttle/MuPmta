<?php
require_once __DIR__.'../../core/config.php';
class ServerStats {

  var $DB;
  var $WWW = DIR.'output/ServerStats/index.html';

  public function __construct($DB)
    {
      $this->DB;
      $this->Netstat();
      $this->Memstat();
    }

  public function Netstat()
    {
      $CMD = 'netstat -ntu | awk \' $5 ~ /^(::ffff:|[0-9|])/ { gsub("::ffff:","",$5); print $5}\' | cut -d: -f1 | sort | uniq -c | sort -nr';
      $OUT = $this->RunCMD($CMD);
    }

  /**
   * RunCMD
   * -------------------------
   * Run a command and return the resulting output
   * -------------------------
   * @param string $CMD // command to execute
   * -------------------------
   **/
  public function RunCMD($CMD)
    {
      exec($CMD,$OUT);
      $this->CMDs[] = $CMD;
      $this->OUT[] = $OUT;
      return $OUT;
    }


}
$SS = new ServerStats($CFG->DB);
print_r($SS);
?>
