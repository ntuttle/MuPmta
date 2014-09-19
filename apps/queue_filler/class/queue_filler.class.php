<?php
/** 
 * QueueFiller
 * -------------------------
 *     Checks Queues and Fills them up if they are low
 * -------------------------
 */
class queue_filler { 
  /** 
   * Editable Settings
   * -------------------------
   */
  var $Active        = 1,     // Turn this script off by setting to 0, 1 for on.
      $Low           = 40,    // Queue is low if below this number
      $Fill          = 40,    // number of rcpts to be added
      $debug         = false, // debug mode
      $debugPools    = [],    // List of pool ids to display in debug mode
      $ForceSettings = 0;     // (true | 1) to use default settings, (false | 0) to use database settings.
  /**
   *    DO NOT TOUCH BELOW !
   * -------------------------
   */
  var $PMTA,                  // This Server
      $JC,                     // Job Creator
      $ALERTS,                // messages will be here
      $DB;                     // Database
  public function __construct($DB,$ARGS) 
    {
      list($out,$debug,$debugPools) = $this->ParseArgs($ARGS);
      $this->StatsDir   = DIR.'output/pmtaQueues.xml';
      $this->Injector   = DIR.'JobReader/reinject.class.php';
      $this->debug      = $debug;
      $this->debugPools = $debugPools;
      $this->SetStyles($out);
      echo BR."  PMTA - Queue Filler".BR;
      if ($this->CheckPMTAStatus())
        if(is_object($DB)){
          $this->DB = $DB;
          $this->HOSTID = PMTAID;
          $this->GetTargets();
          $this->GetActiveDomains();
          if ($this->GetActiveIPS()){
            $this->GetQueuedJobs();
            $this->ReconcileQueues();
            $JC = $this->AddJobsToLineup();
            $this->LaunchInjector();
          }
        }
      echo implode("\n",$this->ALERTS).@$JC;
    }
  /** 
   * AddJobsToLineup
   * -------------------------
   * Creates New job lineup entries for any low queue
   * that is set to auto inject.
   * -------------------------
   */
  public function AddJobsToLineup()
    {
      $TITLE = br.'Adding Jobs to Lineup...'.br;
      require_once DIR.'QueueFiller/JobCreate.class.php';
      $this->JC = new JobCreate($this->EmptyQueues,$this->DB);
      $ALERTS = empty($this->JC->ALERTS)?'No Alerts...':implode(white."\n",$this->JC->ALERTS).white;
      return LF.$TITLE.$ALERTS.br;
    }
  /** 
   * CheckPMTAStatus
   * -------------------------
   * Make sure PMTA is running
   * -------------------------
   */
  public function CheckPMTAStatus()
    {
      if (!file_exists($this->StatsDir)) {
        $this->ALERTS[] = FAIL." pmtaQueues.xml is missing!";
        return false;
      } else {
        $status = file_get_contents($this->StatsDir);
        if (stristr($status, 'NOT RUNNING')) {
          $this->ALERTS[] = FAIL." PMTA is off!";
          return false;
        }
      }
      return true;
    }
  /** 
   * LaunchInjector
   * -------------------------
   * Launch Injector for each pool needing injecting
   * -------------------------
   */
  public function LaunchInjector()
    {
      if(!empty($this->JOBS)){
        $this->Debug('LOW QUEUE JOBS', $this->JOBS);
        foreach ($this->JOBS as $ID => $JOB) {
          $cmd = '/usr/bin/php '.$this->Injector.' \''.$ID.'\' \''.json_encode($JOB, JSON_NUMERIC_CHECK).'\'';
          $rslt = array();
          exec($cmd, $rslt);
          if(!empty($rslt))
            $this->ALERTS[] = red.implode("\n",$rslt).white;
        }
      }
    }
  /** 
   * CompileJobs
   * -------------------------
   * Compile a good list of jobs, each with array of ips to inject on
   * -------------------------
   */
  public function CompileJobs()
    {
      foreach ($this->Pools as $PoolID => $Pool) {
        $this->Debug('FINAL POOL '.$PoolID, $Pool);
        if (!empty($Pool['ips']))
          if (isset($this->QueuedJobs[$PoolID])) {
            $IPS  = $Pool['ips'];
            $_IPS = [];
            foreach ($IPS as $ip => $v)
              if (!empty($v))
                $_IPS[$ip] = $v;
            foreach ($_IPS as $ip => $v) {
              if (is_array($v)){
                foreach ($v as $T => $val) 
                  if(!empty($T)){
                    if (in_array($PoolID, $this->debugPools))
                      $this->Debug('Add TO LOW QUEUE JOBS', [$this->QueuedJobs[$PoolID][$T], $PoolID, $T]);
                    if (isset($this->QueuedJobs[$PoolID][$T])) {
                      $TJob = $this->QueuedJobs[$PoolID][$T];
                      $this->JOBS[$TJob][$ip] = $val;
                    } else
                      $this->EmptyQueues[$T][$PoolID] = $PoolID;
                  }
              }else
                $this->ALERTS[] = FAIL." ~ No IPs for ".$T." using Pool ".$this->PoolNames[$PoolID];
            }
          } else
            foreach($this->TGETS as $T)
              $this->EmptyQueues[$T][$PoolID] = $PoolID;
        else
          $this->ALERTS[] = FAIL." ~ No IPs in Pool ".$this->PoolNames[$PoolID];
      }
      if (empty($this->JOBS)) {
        $this->ALERTS[] = FAIL." ~ No Jobs to Inject...";
      }
      return true;
    }
  /** 
   * GetQueueJobs
   * -------------------------
   * Find any Queued jobs for the pools currently active on this machine
   * -------------------------
   */
  public function GetQueuedJobs()
    {
      $JOBS = $this->DB->GET('MUP.injector.jobs', ['status' => 'QUEUED', 'pmta' => $this->HOSTID, 'active' => '1', 'ORDER' => ['date' => 'ASC']], ['id', 'target', 'pool'], 100000);
      if (empty($JOBS)) {
        $this->ALERTS[] = FAIL." ~ <<< No Jobs to Inject...";
      }else{
        if (isset($JOBS['id']))
          $JOBS = [$JOBS['id']=> $JOBS];
        foreach ($JOBS as $ID => $JOB) 
          if (!isset($this->QueuedJobs[$JOB['pool']][$JOB['target']])) 
            $this->QueuedJobs[$JOB['pool']][$JOB['target']] = ltrim($ID, '0');
        $this->Debug('QUEUED JOBS', $this->QueuedJobs);
      }
    }
  /** 
   * GetTargets
   * -------------------------
   * Make association array of mailing targets and their domain names
   * -------------------------
   */
  public function GetTargets()
    {
      $Q = $this->DB->GET('MUP.domains.targets', ['active' => '1','mailing' => '1'], '*', 10000);
      foreach ($Q as $i => $q) {
        $this->TDOMS[$q['domain']] = $q['target'];
        $this->TGETS[$q['target']] = $q['target'];
      }
      $this->ALERTS[] = " ~ Targets: (".GRN.count($Q).WHT.")";
    }
  /** 
   * GetActiveIPS
   * -------------------------
   * make total array of all ips/targets that are set to mail
   *  - on this server with current queue level of 0
   * -------------------------
   */
  public function GetActiveIPS()
    {
      $Q = $this->DB->GET('MUP.ipconfig.pool_ips', ['active' => '1', 'pool_id__IN' => "SELECT `id` AS `pool_id` FROM `ipconfig`.`pools` WHERE `server_id`={$this->HOSTID} AND `active`=1"], ['longip' => 'id', 'pool_id', 'longip' => 'ip'], 100000);
      if (empty($Q)) {
        $this->ALERTS[] = FAIL." ~ <<< No Active Pool IPs on Server...";
        echo print_r($this->DB,true);
        return false;
      }
      foreach ($Q as $i => $q) {
        $this->PoolIPs[$q['ip']] = $q['pool_id'];
        $pools[$q['pool_id']]    = $q['pool_id'];
        $this->Pools[$q['pool_id']]['ips'][$q['ip']] = [];
      }
      $PN = $this->DB->GET('MUP.ipconfig.pools', ['id__IN' => $pools], ['id', 'name'], 10000);
      if (empty($PN)) {
        $this->ALERTS[] = FAIL." ~ <<< No Active Pools on Server...";
        return false;
      } elseif (isset($PN['name']))
        $PN = [$PN];
      foreach ($PN as $i => $pn)
        $this->PoolNames[$i] = $pn['name'];
      $this->ALERTS[] = " ~ Pools: (".GRN.count($this->Pools)."".WHT.")";
      $this->ALERTS[] = " ~ PoolIPs: (".GRN.count($this->PoolIPs)."".WHT.")";
      $IPS = $this->DB->GET('MUP.ipconfig.target_config', ['active' => '1', 'ip__IN' => "SELECT `ip` FROM `ipconfig`.`global_config` WHERE `pmta`={$this->HOSTID} AND `active`=1"], ['ip', 'target', 'mailing', 'content'], 100000);
      if (empty($IPS)) {
        $this->ALERTS[] = FAIL." ~ <<< No Active IPs on Server...";
        return false;
      } elseif (isset($IPS['ip']))
        $IPS = [$IPS];
      $this->nsReady = [];
      foreach ($IPS as $IP)
        if (isset($this->PoolIPs[$IP['ip']])){
          $POOL    = $this->PoolIPs[$IP['ip']];
          $DETAILS = [];
          if (!in_array($IP['mailing'], $this->nsReady) && !in_array($IP['content'], $this->nsReady))
            if (in_array($IP['mailing'], $this->SetDomains))
              if (in_array($IP['content'], $this->SetDomains))
                $this->Pools[$POOL]['ips'][$IP['ip']][$IP['target']] = ['qty' => ($this->Fill), 'mdom' => $IP['mailing'], 'cdom' => $IP['content']];
              else {
                unset($this->Pools[$POOL]);
                $this->nsReady[] = $IP['content'];
                $this->ALERTS[] = FAIL." ~ Nameservers Not Set content domain ".$IP['content'].EOL;
              }
            else {
              unset($this->Pools[$POOL]);
              $this->nsReady[] = $IP['mailing'];
              $this->ALERTS[] = FAIL." ~ Nameservers are not ready for mailing domain ".$IP['mailing'].EOL;
            }
        }
      return true;
    }
  /** 
   * GetActiveDomains
   * -------------------------
   * Gets array of all domains that are setup and allowed to mail.
   * -------------------------
   */
  public function GetActiveDomains($ARGS = false)
    {
      $Q = $this->DB->GET('MUP.domains.domains', ['status__IN' => [0, 1, 2, 4], 'nsReady' => 1], ['domain'], 100000);
      foreach ($Q as $i => $q) 
        $this->SetDomains[] = $q['domain'];
    }
  /** 
   * ReconcileQueues
   * -------------------------
   * Read through most recent pmta queue status report and
   * conconcile with our current mailing ips...
   * -------------------------
   */
  public function ReconcileQueues()
    {
      $xml = file_get_contents($this->StatsDir);
      $XML = simplexml_load_string($xml);
      foreach ($XML->data->queue as $i => $q) {
        $NAME                = (string) $q->name[0];
        list($DOMAIN, $VMTA) = explode('/', $NAME);
        $LONGIP              = ip2long(trim($VMTA));
        if (isset($this->TDOMS[$DOMAIN]) && ($DOMAIN!=='*') && ($VMTA!=='*'))
          if (!isset($this->PoolIPs[$LONGIP]))
            $this->ALERTS[] = FAIL." No Queued Jobs for ".$DOMAIN.' on VMTA: '.$VMTA.WHT;
          else {
            $POOL   = $this->PoolIPs[$LONGIP];
            $TARGET = $this->TDOMS[$DOMAIN];
            $SIZE   = (string) $q->rcp[0];
            $MODE   = (string) $q->mode[0];
            $PAUSED = (string) $q->paused[0];
            $RETRY  = empty($q->retryTime)?'00:00:00':(string) $q->retryTime[0];
            $ER     = [];
            foreach ($q->event as $event)
              $ER[] = (string) $event->text[0];
            if ($SIZE >= $this->Low) 
              unset($this->Pools[$POOL]['ips'][$LONGIP][$TARGET]);
          }
      }
      if (empty($this->Pools)) {
        $this->ALERTS[] = FAIL." No low Queues Found";
        return false;
      }
      return $this->CompileJobs();
    }
  /** 
   * GetScriptSettings
   * -------------------------
   * Get script settings from default variables at the top 
   * of this page or from the presets database first in global
   * settings, then for specific server settings.
   * -------------------------
   * @return array // sets all variables in editable settings above
   * -------------------------
   */
  public function GetScriptSettings()
    {
      $PMTA = SERVERNAME;
      if(!$this->ForceSettings){
        $this->ParseSettings('presets');
        $this->ParseSettings($PMTA.'__presets');
      }
      return empty($this->Active)?false:true;
    }
  /** 
   * ParseSettings
   * -------------------------
   * Used by $GetScriptSettings() to fetch and parse database 
   * global settings or settings for a specific server.
   * -------------------------
   * @param string $T // tablename in presets database to fetch settings 
   * @return array // sets the script settings by name
   * -------------------------
   */
  public function ParseSettings($T)
    {
      $N = 'PMTA__QueueFiller__';
        $Q = $this->DB->GET('MUP.presets.'.$T,['name__LIKE'=>$N.'%'],['value','name'],1000);
        $Q = isset($Q['value'])?[$Q]:$Q;
        if(!empty($Q))
          foreach($Q as $q){
            $n = str_ireplace($N,'',$q['name']);
            $v = $q['value'];
            $this->$n = $v;
          }
    }
  /** 
   * SetStyles
   * -------------------------
   * This is only for making the output pretty
   *  - as of now, only in html or shell, but may add
   *  - csv and json as standard output options too.
   * -------------------------
   */
  public function SetStyles($output)
    {
      $this->output = $output;
      if ($output == 'html') {
        define('EOL', '<br>');
        define('RED', '</span><span style="color:red">');
        define('WHT', '</span><span style="color:white">');
        define('GRN', '</span><span style="color:lime">');
        echo START;
        echo "<h1 style=\"display:block\">Queue Filler Cron<i style=\"float:right\">".date('Y-m-d H:i:s')."</i></h1>";
        echo "<span>";
      } else {
        define('EOL', "\n");
        define('RED', "\033[1;31m");
        define('WHT', "\033[0m");
        define('GRN', "\033[0;32m");
        echo EOL;
      }
    }
  /** 
   * Debug
   * -------------------------
   * prints a nice debug message of a title and stuff you want to see
   * @param string $TITLE    // the header of the debug message
   * @param mixed  $DATA     // whatever you put here is vardumped out.
   * -------------------------
   */
  public function Debug($TITLE, $DATA)
    {
      if ($this->debug) {
        echo BR;
        if ($TITLE !== false)
          echo '<h3>'.$TITLE.'</h3>';
        if ($this->output == 'html') 
          echo "<pre>";
        var_dump($DATA);
        if ($this->output == 'html') 
          echo "</pre>";
        echo BR;
      }
    }
  /** 
   * ParseArgs
   * -------------------------
   * translates user pass arguements to scrip variables
   * -------------------------
   */
  public function ParseArgs($argv)
    {
      $debug  = false;
      $pools  = [];
      $output = 'html';
      if (!empty($argv))
        foreach ($argv as $arg)
          if (is_numeric($arg)) {
            $debug = true;
            $pools = stristr($arg, ',')?explode(',', $arg):[$arg];
          } else
            $output = in_array(@$arg, ['html', 'json', 'csv'])?$arg:false;
      return [$output, $debug, $pools];
    }
}
?>
