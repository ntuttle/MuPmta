<?php
class CFG {
  var $settings;
  var $DB;
  public function __construct($ARGS)
    {
      $this->start = time();
      $this->SetGlobals($ARGS);
    }
  /** 
   * SetGlobals
   * -------------------------
   * Set the running directories
   * -------------------------
   **/
  public function SetGlobals($ARGS)
    {
      $this->ARGS = $ARGS;
      $this->user = $ARGS['user'];
      $this->pass = $ARGS['pass'];
      $this->debug = $ARGS['debug'];
      $this->mtaPort = $ARGS['mtaPort'];
      $this->wwwPort = $ARGS['wwwPort'];
      $this->domain = $ARGS['domain'];
      $this->IniSet(@$ARGS['ini']);
      $this->Dependancies();
      $this->SetStyles();
      $this->CheckDB(@$ARGS['hosts']);
      $this->CheckHost(@$ARGS['host']);
    }
  /** 
   * IniSet
   * -------------------------
   * Set any php.ini directives before any script output if needed
   * -------------------------
   * @param array $INI // array of [ directive => value [, ...] ]
   * -------------------------
   **/
  public function IniSet($INI=false)
    {
      $this->settings = $this->defaultINI();
      if(!empty($INI))
        foreach($INI as $N=>$V){
          $this->settings[$N] = $V;
          ini_set($N,$V);
        }
    }
  /**
   * defaultINI
   * -------------------------
   * Set default php.ini settings
   * -------------------------
   **/
  public function defaultINI()
    {
      include CONF.'ini.php';
      return $INI;
    }
  /** 
   * SetStyles
   * -------------------------
   * return the value of a variable, while 
   * destroying the existing variable
   * -------------------------
   * @param mixed $X // variable to unset and return
   * -------------------------
   **/
  public function SetStyles()
    {
      $O = $this->GetScriptOutput();
      if($O=='html')
        {
          define('red',    "<span style=\"color:red\">");
          define('green',  "<span style=\"color:green\">");
          define('yellow', "<span style=\"color:yellow\">");
          define('grey',   "<span style=\"color:grey\">");
          define('white',  "</span>");
        }
      else
        {
          define('red',    "\e[1;31m");
          define('green',  "\e[1;32m");
          define('yellow', "\e[1;93m");
          define('grey',   "\e[1;90m");
          define('white',  "\e[0m");
        }
      define('LF', "\n");
      define('br', LF.str_repeat('-', 50).LF);
      define('BR', LF.str_repeat('=', 50).LF);
      define('FAIL', red."FAILED! ".white);
      define('PASS', green."PASSED! ".white);
    }
  /** 
   * GetScriptOutput
   * -------------------------
   * determine the output format needed for the running script
   * -------------------------
   **/
  public function GetScriptOutput()
    {
      if(!defined('output'))
        define('output',false);
      return output;
    }
  /** 
   * CheckDB
   * -------------------------
   * Connect to all the hosts provided using correct 
   * IP address based on `debug` setting at top.
   * -------------------------
   * @param array $HOSTS // array of hosts to connect to
   * -------------------------
   * $HOST = array('NAME' => array('IP'[,'IP2'] [,PORT]));
   * -------------------------
   **/
  public function CheckDB($HOSTS)
    {
      $i = ($this->debug === true)?'public':'private';
      $U = $this->user;
      $P = $this->pass;
      if(!empty($HOSTS)){
        foreach($HOSTS as $H=>$INFO){
          $PORT = empty($INFO['port'])?'':':'.$INFO['port'];
          $IP = $INFO[$i].$PORT;
          if(empty($DB))
            $DB = $this->NewDB($H,$IP,$U,$P);
          if(empty($DB->S[$H]))
            $DB->C($H,$IP,$U,$P,true);
          if(!defined($H))
            define($H,$IP);
        }
        if(is_object($DB))
          return $this->DB = $DB;
      }
      trigger_error('No Valid Database Hosts Provided');
    }
  /** 
   * NewDB
   * -------------------------
   * Initiate a new DBC Object with a new persistant 
   * connection to the first host specified in config.php
   * -------------------------
   * @param string $N // Reference name to be used in queries
   * @param string $H // IP:Port of the database host
   * @param string $U // Username of database
   * @param string $P // Password for $U
   * -------------------------
   **/
  public function NewDB($N,$H,$U=false,$P=false)
    {
      $U = empty($U)?$this->user:$U;
      $P = empty($U)?$this->pass:$P;
      $DB = new DBC($N,$H,$U,$P);
      $_ = isset($DB->S[$N])?$DB:'Initial DB Connection Failed';
      return empty($DB)?Quit('No Initial Database Connection'):$DB;
    }
  /** 
   * CheckHost
   * -------------------------
   * get server hostname and check for active status
   * in the `MUP.hardware.servers` database
   * -------------------------
   * @param string $HN // pass a specific hostname. false will use hostname where the script is running.
   * -------------------------
   **/
  public function CheckHost($HN=false)
    {
      $H = empty($HN)?trim(strtolower(gethostname())):$HN;
      $Q = $this->DB->GET('MUP.hardware.servers',['type'=>'PMTA','active'=>1,'name'=>$H],['name','id'],1);
      if(isset($Q['name'])){
        $this->hostname = $Q['name'];
        define('hostname',$this->hostname);
        $this->hostID = $Q['id'];
        define('hostID',$this->hostID);
      }else{
        Quit('Invalid Hostname!');
      }
    }
  /** 
   * Dependancies
   * -------------------------
   **/
  public function Dependancies()
    {
      foreach(['db','form','sms','table'] as $d)
        $D[] = $d.'.class.php';
      $_ = Req($D,DIR.'core/class/');
      if(!is_numeric($_) || empty($_)){
        echo $_;
        return false;
      }
      return true;
    }
}
?>