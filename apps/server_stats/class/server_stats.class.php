<?php
class STATS {
  
  var $HddStats = "/bin/df -h | /bin/grep -o '\([0-9]*%\) \/$' | /bin/sed 's/ \///'";
  var $NetStats = "netstat -antu | awk '$5 ~ /[0-9]:/{split($5, a, \":\"); ips[a[1]]++} END {for (ip in ips) print ips[ip], ip | \"sort -k1 -nr\"}'";
  var $MemStats = "ls";
  var $DB;

  public function __construct($DB)
    {
      $this->DB = $DB;
    }
  /**
   * HddStats
   * -------------------------
   **/
  public function HddStats()
    {
      exec($this->HddStats,$_);
      $_ = implode(LF,$_);
      $n = str_ireplace('%','',$_);
      if($n >= 90)
        $n = red.$n.white.'%';
      elseif($n >=80)
        $n = yellow.$n.white.'%';
      else
        $n = green.$n.white.'%';
      return 'Filesystem: '.$n;
    }
  /**
   * NetStats
   * -------------------------
   **/
  public function NetStats()
    {
      exec($this->NetStats,$_);
      $_ = implode(LF,$_);
      //return 'Network: '.$_;
      return 'Network: ';
    }
  /**
   * MemStats
   * -------------------------
   **/
  public function MemStats()
    {
      exec($this->MemStats,$_);
      $_ = implode(LF,$_);
      //return 'Memory: '.$_;
      return 'Memory: ';
    }
} 
?>