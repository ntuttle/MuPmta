<?
class config_writer {

  var $DB;
  var $Debug = false;

  public function __construct($DB)
    {
      $this->DB   = $DB;
      $this->SetVariables();
    }
  /**
   * SetVariables
   * -------------------------
   **/
  public function SetVariables()
    {
      define('TAB',str_repeat(' ',5));
      $this->Title();
      $this->SetPMTA();
      $this->TargetConfigs();
      $this->SMTPPatternLists();
      $this->BounceCategories();
      $this->BuildConfig();
    }
  /**
   * SetPMTA
   * -------------------------
   * get all server vaiables for this hostID
   * -------------------------
   **/
  public function SetPMTA()
    {
      $this->PMTA = $this->DB->GET('MUP.hardware.servers', ['id' => hostID], '*', 1);
      if (!$this->PMTA)
        trigger_error('Invalid PMTA!');
    }
  /**
   * TargetConfigs
   * -------------------------
   **/
  public function TargetConfigs()
    {
      $Q = $this->DB->GET('MUP.pmta.config__targets',['scope'=>'domain'],'*',100000);
      if(!empty($Q = isset($Q['id'])?[$Q]:$Q))
        foreach ($Q as $i => $q)
          $this->RawConfig[] = $q;
    }
  /**
   * SMTPPatternLists
   * -------------------------
   **/
  public function SMTPPatternLists()
    {
      $Q = $this->DB->GET('MUP.pmta.config__smtp_pattern_list', ['id__>=' => '0'], '*', 100000);
      $Q = isset($Q['id'])?[$Q]:$Q;
      foreach ($Q as $i => $q) {
        $q['scope']        = 'smtp-pattern-list';
        $q['name']         = $q['target'];
        $q['directive']    = 'reply /'.$q['pattern'].'/';
        $q['value']        = $q['action'];
        $this->RawConfig[] = $q;
      }
    }
  /**
   * BounceCategories
   * -------------------------
   **/
  public function BounceCategories()
    {
      $Q                       = $this->DB->GET('MUP.pmta.config__bounce_categories', ['id__>=' => '0'], '*', 100000);
      if (isset($Q['id'])) {$Q = [$Q];}
      foreach ($Q as $i => $q) {
        $q['scope']        = 'bounce-category-patterns';
        $q['name']         = '';
        $q['directive']    = '/'.$q['pattern'].'/';
        $q['value']        = $q['category'];
        $this->RawConfig[] = $q;
      }
    }
  /**
   * GetRawConfig
   * -------------------------
   * Get the configuration settings currently in the database
   * -------------------------
   **/
  public function GetRawConfig()
    {
      $Q = $this->DB->GET('MUP.pmta.config', ['active' => '1'], '*', 1000000);
      if(!empty($Q = isset($Q['id'])?[$Q]:$Q))
        foreach ($Q as $i => $q)
          $this->RawConfig[] = $q;
      $this->GetRawServerConfig();
      if(!empty($this->RawConfig))
        if($this->ParseRawConfig())
          return true;
      return false;
    }
  /**
   * GetRawServerConfig
   * -------------------------
   * Get the configuration settings for this specific
   * server currently in the database
   * -------------------------
   **/
  public function GetRawServerConfig()
    {
      $Q = $this->DB->GET('MUP.pmta.config__'.$this->PMTA['name'], ['active' => '1'], '*', 1000000);
      if(!empty($Q = isset($Q['id'])?[$Q]:$Q))
        foreach ($Q as $i => $q)
          $this->RawConfig[] = $q;
    }
  /**
   * Space
   * -------------------------
   * Create a space size based on the length of the 
   * given content to format the config output nicely
   * -------------------------
   * @param string $D // the directive to determine space length from
   * -------------------------
   **/
  public function Space($D,$s=32)
    {
      $L = is_string($D)?strlen($D):1;
      $S = ($s-$L);
      $l = $S<=0?1:$S;
      $_ = str_repeat(' ',$l);
      return $_;
    }
  /**
   * BuildConfig
   * -------------------------
   * Reconcile Server & Global Configuration
   * -------------------------
   **/
  public function BuildConfig()
    {
      if ($this->GetRawConfig()){
        $this->PMTAGlobals();
        $this->AccessList();
        foreach ($this->CONFIG as $S=>$N) 
          $this->FormatScope($S,$N);
        $this->TargetMacros();
        $this->GetActiveIPs();
        $this->DomainMacros();
        $this->RelayDomain();
        $this->VMTA();
        $this->DKIM();
      }
    }
  /**
   * FormatScope
   * -------------------------
   * create a formatted scope for pmta configuration 
   * with the provided Scope & Directives Array & 
   * append to the Working Conf[]
   * -------------------------
   * @param string $S // scope type
   * @param array $D // array of [scopename=>[directive=>value],...]
   * -------------------------
   **/
  public function FormatScope($S,$N)
    {
      $this->Title($S.'s');
      foreach ($N as $n => $D) {
        $this->Conf[] = TAB."<".$S.(empty($n)?'':' '.$n).">";
        $this->FormatDirectives($D,TAB);
        $this->Conf[] = TAB."</".$S.">";
        $this->Breaker();
      }
      $this->Conf[] = '';
    }
  /**
   * FormatDirectives
   * -------------------------
   * Create a formatted directive and value entry
   * -------------------------
   * @param string $D // array of [directive=>value,...]
   * @param array $TAB // append this value to every insert
   * -------------------------
   **/
  public function FormatDirectives($D,$TAB=false)
    {
      foreach ($D as $d)
        foreach ($d as $k=>$v){
          $v = $this->ReplacePlaceholders($v);
          $this->Conf[] = TAB.$TAB.$k.$this->Space($k).$v;
        }
    }
  /**
   * ReplacePlaceholders
   * -------------------------
   * replace placeholders with server specific details
   * -------------------------
   **/
  public function ReplacePlaceholders(&$v)
    {
      $_v = trim($v);
      if(stristr($_v,'[__hostID__]'))
        $_v = str_ireplace('[__hostID__]',hostID,$_v);
      if(stristr($_v,'[__hostname__]'))
        $_v = str_ireplace('[__hostname__]',hostname,$_v);
      if(stristr($_v,'[__ipaddr__]'))
        $_v = str_ireplace('[__ipaddr__]',$this->PMTA['publicIP'],$_v);
      if(stristr($_v,'[__ipaddr__]'))
        $_v = str_ireplace('[__ipaddr__]',$this->PMTA['publicIP'],$_v);
      return $_v;
    }
  /**
   * GetActiveIPs
   * -------------------------
   **/
  public function GetActiveIPs()
    {
      $Q = $this->DB->GET('MUP.ipconfig.global_config', ['active' => '1', 'pmta' => $this->PMTA['id']], ['ip', 'rdns'], 10000);
      if(!empty($Q = isset($Q['ip'])?[$Q]:$Q))
        foreach ($Q as $q) {
          $IPS[$q['ip']] = $q['ip'];
          $this->SMTP_Source_Host[$q['ip']] = $q['rdns'];
        }
      $Q = $this->DB->GET('MUP.ipconfig.target_config', ['ip__IN' => $IPS, 'active' => '1'], ['ip', 'target', 'mailing', 'content', 'rate'], 100000);
      if(!empty($Q = isset($Q['ip'])?[$Q]:$Q))
        foreach ($Q as $q) {
          $this->DOMAINS[$q['mailing']]        = $q['mailing'];
          $this->DOMAINS[$q['content']]        = $q['content'];
          if($q['rate']>0)
            $this->VMTAS[$q['ip']][$q['target']] = $q['rate'];
        }
      return true;
    }
  /**
   * ParseRawConfig
   * -------------------------
   **/
  public function ParseRawConfig()
    {
      if(!empty($this->RawConfig)){
        foreach ($this->RawConfig as $i => $c) {
          $S = empty($c['scope'])?'GLOBAL':$c['scope'];
          $N = $c['name'];
          $D = $c['directive'];
          $V = $c['value'];
          $this->CONFIG[$S][$N][$D] = [$D=>$V];
      } }
      if(!empty($this->CONFIG))
        return true;
      return false;
    }
  /**
   * PMTAGlobals
   * -------------------------
   **/
  public function PMTAGlobals()
    {
      $this->Title(strtoupper($this->PMTA['name']).' - PMTA Configuration');
      foreach ($this->CONFIG['GLOBAL'] as $N => $D)
        $this->FormatDirectives($D);
      $this->Conf[] = '';
      unset($this->CONFIG['GLOBAL']);
      return true;
    }
  /**
   * Title
   * -------------------------
   **/
  public function Title($TITLE=false)
    {
      if(!$TITLE){
        $TITLE = 'MediaUniversal - PowerMTA CONFIGURATION ';
        $l = ((100-strlen($TITLE))/2);
        $this->Conf[] = '#'.str_repeat('#', 102).'#';
        $this->Conf[] = '# '.str_repeat(' ',$l).$TITLE.str_repeat(' ',$l).' #';
        return;
      }
      $this->Conf[] = '#'.str_repeat('=', 102).'#';
      $this->Conf[] = '# '.$TITLE.str_repeat(' ',(100-strlen($TITLE))).' #';
      $this->Conf[] = '#'.str_repeat('-', 102).'#';
    }
  /**
   * Breaker
   * -------------------------
   **/
  public function Breaker()
    {
      $this->Conf[] = TAB.'#'.str_repeat('  --- ', 16).' #';
    }
  /**
   * AccessList
   * -------------------------
   **/
  public function AccessList()
    {
      $this->Title('Access List');
      $ACL = $this->DB->GET('MUP.presets.access');
      $len = (35-strlen('http-access'));
      $len = ($len <= 0)?1:$len;
      $space = str_repeat(' ', $len);
      $this->Conf[] = TAB.'http-access'.$space.'127.0.0.1'.str_repeat(' ', 11).'admin # this machine';
      foreach ($ACL as $acl) {
        $ip = long2ip($acl['ip']);
        $len = (20-strlen($ip));
        $len = str_repeat(' ', $len);
        $this->Conf[] = TAB.'http-access'.$space.$ip.$len.'admin # '.$acl['note'];
      }
      $this->Conf[] = '';
      return true;
    }
  /**
   * TargetMacros
   * -------------------------
   **/
  public function TargetMacros()
    {
      $this->Title('Target Domain Macros');
      $Q = $this->DB->GET('MUP.domains.targets', ['active' => '1'], '*', 100000);
      foreach ($Q as $q)
      $TARGETS[$q['target']][] = $q['domain'];
      foreach ($TARGETS as $target => $domains)
      $this->Conf[] = TAB.'domain-macro'.str_repeat(' ', 8).$target.str_repeat(' ', (15-strlen($target))).implode(', ', $domains);
      $this->Conf[] = '';
    }
  /**
   * DomainMacros
   * -------------------------
   **/
  public function DomainMacros()
    {
      $this->Title('Mailing & Content Domain Macros');
      if(!empty($this->DOMAINS)){
        $this->Conf[] = TAB.'domain-macro'.str_repeat(' ', 8).'AllDomains'.str_repeat(' ', 5).implode(', ', $this->DOMAINS);
        $this->Conf[] = '';
        $this->Conf[] = TAB.'<domain $AllDomains>';
        $this->Conf[] = TAB.TAB.'type'.str_repeat(' ', 11).'pipe';
        $this->Conf[] = TAB.TAB.'command'.str_repeat(' ', 8).'"/usr/bin/php /etc/pmta/scripts/IncomingEmails.php"';
        $this->Conf[] = TAB.'</domain>';
      }
      $this->Conf[] = '';
      return true;
    }
  /**
   * RelayDomain
   * -------------------------
   **/
  public function RelayDomain()
    {
      $this->Title('RELAY OUR DOMAINS');
      if(!empty($this->DOMAINS))
        foreach ($this->DOMAINS as $DOMAIN)
          $this->Conf[] = TAB.'relay-domain'.str_repeat(' ', 8).'[*.]'.$DOMAIN;
      $this->Conf[] = '';
      return true;
    }
  /**
   * DKIM
   * -------------------------
   **/
  public function DKIM()
    {
      $this->Title('DKIM / DOMAIN KEYS');
      if(!empty($this->DOMAINS))
        foreach ($this->DOMAINS as $DOMAIN)
          $this->Conf[] = TAB.'domain-key'.str_repeat(' ', 10).'key1,'.$DOMAIN.',/etc/pmta/keys/key1.'.$DOMAIN.'.pem';
      $this->Conf[] = '';
      return true;
    }
  /**
   * VMTA
   * -------------------------
   **/
  public function VMTA()
    {
      $this->Title('VIRTUAL MTAS');
      if(!empty($this->SMTP_Source_Host)){
        foreach ($this->SMTP_Source_Host as $LONGIP => $rDNS) {
          $IP           = long2ip($LONGIP);
          $this->Conf[] = TAB.'<virtual-mta '.$IP.'>';
          $this->Conf[] = TAB.TAB.'smtp-source-host '.$IP.str_repeat(' ', (20-strlen($IP))).$rDNS;
          if (!empty($this->VMTAS[$LONGIP])) {
            foreach ($this->VMTAS[$LONGIP] as $TARGET => $RATE) {
              $this->Conf[] = TAB.TAB.'<domain $'.$TARGET.'>';
              $this->Conf[] = TAB.TAB.TAB.'max-msg-rate '.$RATE.'/h';
              $this->Conf[] = TAB.TAB.'</domain>';
            }}
          $this->Conf[] = TAB.'</virtual-mta>';
        }
      }
      $this->Conf[] = '';
      return true;
    }
  /**
   * VMTAPool
   * -------------------------
   **/
  public function VMTAPool()
    {
      $Q = $this->DB->GET('MUP.ipconfig.pools', ['server_id' => $this->PMTA['id'], 'active' => '1'], ['id', 'name'], 100000);
      $this->Title('VIRTUAL MTA POOLS');
      foreach ($Q as $i => $q) {
        $POOL_IDS[]    = $i;
        $POOLNAMES[$i] = $q['name'];
      }
      $Q = $this->DB->GET('MUP.ipconfig.pool_ips', ['pool_id__IN' => $POOL_IDS, 'active' => '1'], ['longip', 'pool_id'], 100000);
      foreach ($Q as $q) {
        $POOLS[$q['pool_id']][$POOLNAMES[$q['pool_id']]][] = $q['longip'];
      }
      foreach ($POOLS as $ID   => $POOL) {
        foreach ($POOL as $NAME => $IPS) {
          $this->Conf[] = TAB.'<virtual-mta-pool '.$NAME.'>';
          foreach ($IPS as $IP)
          $this->Conf[] = TAB.TAB.'virtual-mta'.str_repeat(' ', 5).long2ip($IP);
          $this->Conf[] = TAB.'</virtual-mta-pool>';
        }
      }
      $this->Conf[] = '';
      return true;
    }
  /**
   * Debug
   * -------------------------
   **/
  public function Debug($VAL)
    {
      if ($this->Debug) {X::Debug($VAL);}
      return true;
    }
}
?>