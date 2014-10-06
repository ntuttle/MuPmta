<?
class config_writer {
  var $DB;
  var $Debug = false;
  public function __construct($DB,$ARGS=false)
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
      $this->Title(true);
      $this->SetPMTA();
      $this->Sources();
      $this->TargetConfigs();
      $this->PatternList();
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
      $Q = $this->DB->GET('MUP.pmta.config__targets',['scope'=>'domain','ORDER'=>'name'],'*',100000);
      if(!empty($Q = isset($Q['id'])?[$Q]:$Q))
        foreach ($Q as $i => $q)
          $this->RawConfig[] = $q;
    }
  /**                                  
   * PatternLists
   * -------------------------
   **/
  public function PatternList()        
    {
      $Q = $this->DB->GET('MUP.pmta.config__pattern_list', ['id__>=' => '0'], '*', 100000);
      if(!empty($Q = isset($Q['id'])?[$Q]:$Q))
        foreach ($Q as $i => $q) {
          $q['scope']        = 'pattern-list';
          $q['name']         = 'list';
          $q['directive']    = $q['type'].' /'.$q['pattern'].'/';
          $q['value']        = $q['action'];
          $this->RawConfig[] = $q;
        }
    }
  /**                                  
   * SMTPPatternLists
   * -------------------------
   **/
  public function SMTPPatternLists()   
    {
      $Q = $this->DB->GET('MUP.pmta.config__smtp_pattern_list', ['id__>=' => '0'], '*', 100000);
      if(!empty($Q = isset($Q['id'])?[$Q]:$Q))
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
      if(!empty($Q = isset($Q['id'])?[$Q]:$Q))
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
      $Q = $this->DB->GET('MUP.pmta.config__global', ['active' => '1'], '*', 1000000);
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
        $this->GetActiveIPs();
        foreach ($this->CONFIG as $S=>$N) 
          $this->FormatScope($S,$N);
        $this->AccessList();
        $this->TargetMacros();
        $this->RelayDomain();
        $this->DKIM();
        $this->VMTA();
      }
      $this->Title(false);
    }
  /**                                  
   * Sources
   * -------------------------
   **/
  public function Sources()            
    {
      $Q = $this->DB->GET('MUP.pmta.config__sources',['scope'=>'source','ORDER'=>['name'=>'DESC']],'*',100000);
      if(!empty($Q = isset($Q['id'])?[$Q]:$Q))
        foreach ($Q as $i => $q)
          $this->RawConfig[] = $q;
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
        if(isset($c)){$this->Breaker();}else{$c=1;}
        $n = $this->ReplacePlaceholders($n);
        $this->Conf[] = TAB."<".$S.(empty($n)?'':' '.$n).">";
        $this->FormatDirectives($D,TAB);
        $this->Conf[] = TAB."</".$S.">";
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
      if(strstr($_v,'[ID]'))
        $_v = str_replace('[ID]',hostID,$_v);
      if(strstr($_v,'[HOST]'))
        $_v = str_replace('[HOST]',hostname,$_v);
      if(strstr($_v,'[IP]'))
        $_v = str_replace('[IP]',$this->PMTA['publicIP'],$_v);
      if(strstr($_v,'[LOGS]'))
        $_v = str_replace('[LOGS]',LOGS,$_v);
      if(strstr($_v,'[APPS]'))
        $_v = str_replace('[APPS]',APPS,$_v);
      if(strstr($_v,'[CONF]'))
        $_v = str_replace('[CONF]',CONF,$_v);
      if(strstr($_v,'[DATA]'))
        $_v = str_replace('[DATA]',DATA,$_v);
      if(strstr($_v,'[DIR]'))
        $_v = str_replace('[DIR]',DIR,$_v);
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
      $Q = $this->DB->GET('MUP.ipconfig.target_config', ['ip__IN' => $IPS, 'active' => '1'], ['ip', 'target', 'mailing', 'content', 'msg_rate','con_rate','msg_con'], 100000);
      $m = '';
      if(!empty($Q = isset($Q['ip'])?[$Q]:$Q)){
        foreach ($Q as $q) {
          $M = $q['mailing'];
          $C = $q['content'];
          $this->DOMAINS[$M] = $M;
          $this->DOMAINS[$C] = $C;
          if(($q['msg_rate']>0) || ($q['con_rate']>0) || ($q['msg_con']>0)){
            $T = $q['target'];
            $IP = $q['ip'];
            if($q['msg_rate']>0)
              $this->VMTAS[$IP][$T]['max-msg-rate'] = $q['msg_rate'];
            if($q['con_rate']>0)
              $this->VMTAS[$IP][$T]['max-connect-rate'] = $q['con_rate'];
            if($q['msg_con']>0)
              $this->VMTAS[$IP][$T]['max-msg-per-connection'] = $q['msg_con'];
          }
        }
        return true;
      }
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
      if(!empty($this->CONFIG['GLOBAL'])){
        foreach ($this->CONFIG['GLOBAL'] as $N => $D)
          $this->FormatDirectives($D);
        unset($this->CONFIG['GLOBAL']);
      }
      $this->Conf[] = '';
      return true;
    }
  /**                                  
   * Title
   * -------------------------
   **/
  public function Title($TITLE=false)  
    {
      if(!$TITLE){
        $TITLE = ' END - PowerMTA config';
        $l = ((100-strlen($TITLE))/2);
        $this->Conf[] = '#'.str_repeat('=', 102).'#';
        $this->Conf[] = '# '.str_repeat(' ',$l).strtoupper($TITLE).str_repeat(' ',$l).' #';
        $this->Conf[] = '#'.str_repeat('#', 102).'#';
        return;
      }elseif($TITLE===true){
        $TITLE = 'Media Universal - PowerMTA config';
        $l = ((100-strlen($TITLE))/2);
        $this->Conf[] = '#'.str_repeat('#', 102).'#';
        $this->Conf[] = '# '.str_repeat(' ',$l).strtoupper($TITLE).str_repeat(' ',$l).' #';
        return;
      }
      $this->Conf[] = '#'.str_repeat('=', 102).'#';
      $this->Conf[] = '# '.strtoupper($TITLE).str_repeat(' ',(100-strlen($TITLE))).' #';
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
      $this->DomainMacros();
    }
  /**                                  
   * DomainMacros
   * -------------------------
   **/
  public function DomainMacros()       
    {
      if(!empty($this->DOMAINS)){
        $this->Conf[] = TAB.'domain-macro'.str_repeat(' ', 8).'domains'.str_repeat(' ', 5).implode(', ', $this->DOMAINS);
        $this->Conf[] = TAB.'<domain $domains>';
        $this->Conf[] = TAB.TAB.'type'.str_repeat(' ', 11).'pipe';
        $this->Conf[] = TAB.TAB.'command'.str_repeat(' ', 8).'"/usr/bin/php '.APPS.'incoming_emails/run.php"';
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
      if(!empty($this->SMTP_Source_Host))
        foreach ($this->SMTP_Source_Host as $LONGIP => $rDNS) {
          $IP = long2ip($LONGIP);
          $this->Conf[] = TAB.'<virtual-mta '.$IP.'>';
          $this->Conf[] = TAB.TAB.'smtp-source-host '.$IP.str_repeat(' ', (20-strlen($IP))).$rDNS;
          if (!empty($this->VMTAS[$LONGIP]))
            foreach ($this->VMTAS[$LONGIP] as $TARGET => $SETTINGS){
              $this->Conf[] = TAB.TAB.'<domain $'.$TARGET.'>';
              foreach($SETTINGS as $D=>$V)
                $this->Conf[] = $this->SetDirectiveValue($D,$V);
              $this->Conf[] = TAB.TAB.'</domain>';
            }
          $this->Conf[] = TAB.'</virtual-mta>';
        }
      $this->Conf[] = '';
      return true;
    }
  /**                                  
   * SetDirectiveValue
   * -------------------------
   **/
  public function SetDirectiveValue($D,$V)
    {
      $pT = ['max-connect-rate','max-msg-rate'];
      if(in_array($D,$pT)){
        if(($V%60)>0)
          $V = $V.'/m';
        else
          $V = ($V>=60)?ceil($V/60).'/m':$V.'/h';
      }
      return TAB.TAB.TAB.$D.' '.$V;
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
      foreach ($Q as $q)
        $POOLS[$q['pool_id']][$POOLNAMES[$q['pool_id']]][] = $q['longip'];
      foreach ($POOLS as $ID   => $POOL)
        foreach ($POOL as $NAME => $IPS) {
          $this->Conf[] = TAB.'<virtual-mta-pool '.$NAME.'>';
          foreach ($IPS as $IP)
            $this->Conf[] = TAB.TAB.'virtual-mta'.str_repeat(' ', 5).long2ip($IP);
          $this->Conf[] = TAB.'</virtual-mta-pool>';
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