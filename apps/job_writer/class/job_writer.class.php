<?php
class job_writer {
  
  var $DB;
  var $Limit = 1;
  var $ID;
  var $JOB;
  var $ALERTS;

  public function __construct($CFG)
    {
      $this->DB = $CFG->DB;
      $this->CheckLineup();
    }
  /**
   * CheckLineup
   * -------------------------
   **/
  public function CheckLineup()
    {
      $Q = $this->DB->GET('MUP.jobs.lineup__'.strtoupper(hostname),['status'=>'PENDING','send_date__<='=>'NOW()','active'=>1],['id','listID','json_params'],$this->Limit);
      if(!empty($Q)){
        $Q = isset($Q['id'])?[$Q['id']=>$Q]:$Q;
        foreach($Q as $i=>$q){
          $IDs[$i] = $i;
          $this->Lineup[$i] = $q;
        }
        $this->ClaimLineup($IDs);
        $this->BuildJobs();
      }
    }
  /**
   * ClaimLineup
   * -------------------------
   **/
  public function ClaimLineup($IDs)
    {
      $count = count($IDs);
      $this->DB->SET('MUP.jobs.lineup__'.strtoupper(hostname),['status'=>'BUILDING'],['id'=>$IDs],$count);
      if($this->DB->aR < $count)
        $this->ALERTS[] = FAIL('Problem with claiming lineup ids!'.Debug($this->DB));
    }
  /**
   * BuildJobs
   * -------------------------
   **/
  public function BuildJobs()
    {
      foreach($this->Lineup as $i=>$l){
        $j = json_decode($l['json_params']);
        $_ = [];
        $_['Status'] = $S = $this->GetStatus($j->s);
        $_['Offer']  = $O = $this->GetOffer($j->o);
        $_['List']   = $this->GetList($l['listID'],$S,$O['suppression']);
        $_['Pool']   = $this->GetPool($j->p);
        $this->JOB[$i] = $_;
      }
    }
  /**
   * GetPool
   * -------------------------
   **/
  public function GetPool($ID)
    {
      $W = is_numeric($ID)?['id'=>$ID]:['name'=>$ID];
      $W['active'] = 1;
      $Q = $this->DB->GET('MUP.ipconfig.pools',$W,'*',1);
      if(!empty($Q)){
        $Pool = $Q;
        $W = ['pool_id'=>$ID,'active'=>1];
        $IPs = $this->DB->GET('MUP.ipconfig.pool_ips',$W,'*',10000);
        if(!empty($IPs)){
          $IPs = isset($IPs['longip'])?[$IPs]:$IPs;
          foreach($IPs as $IP)
            $_IPs[$IP['longip']] = $IP['longip'];
          $W = ['ip__IN'=>$_IPs,'active'=>1,'pmta'=>hostID];
          $F = ['ip','rdns'];
          $IPd = $this->DB->GET('MUP.ipconfig.global_config',$W,$F,count($_IPs));
          $IP_Config = [];
          foreach($IPd as $_d)
            $IP_Config[$_d['ip']] = ['ip'=>long2ip($_d['ip']),'rdns'=>$_d['rdns']];
          $W = ['ip__IN'=>$_IPs,'active'=>1,'target'=>$this->Target];
          $F = ['ip'=>'id','ip','mailing','content','rate','threshold','speedLimit'];
          $IPd = $this->DB->GET('MUP.ipconfig.target_config',$W,$F,count($_IPs));
          foreach($IPd as $_d){
            if(isset($IP_Config[$_d['ip']]['ip'])){
              $IP_Config[$_d['ip']]['mailing']    = $_d['mailing'];
              $IP_Config[$_d['ip']]['content']    = $_d['content'];
              $IP_Config[$_d['ip']]['rate']       = $_d['rate'];
              $IP_Config[$_d['ip']]['threshold']  = $_d['threshold'];
              $IP_Config[$_d['ip']]['speedLimit'] = $_d['speedLimit'];
            }
          }
          $Pool['ips'] = $IP_Config;
        }
      }
      if(!empty($Pool))
        return $Pool;
      $this->ALERTS[] = FAIL('Pool Not Found! ~ '.$ID.Debug($this->DB));
      return false;
    }
  /**
   * GetStatus
   * -------------------------
   **/
  public function GetStatus($ID)
    {
      $W = ['group_id'=>$ID];
      $Q = $this->DB->GET('EMAILS.lists.list_dyn_group_status',$W,['status_id'=>'id','status_id'=>'status']);
      if(!empty($Q)){
        $Q = isset($Q['id'])?[$Q]:$Q;
        foreach($Q as $q)
          $_S[$q['status']] = $q['status'];
      }
      if(!empty($_S))
        return $_S;
      $this->ALERTS[] = FAIL('Status Not Found! ~ '.$ID.Debug($this->DB));
      return false;
    }
  /**
   * GetOffer
   * -------------------------
   **/
  public function GetOffer($ID)
    {
      $W = is_numeric($ID)?['id'=>$ID]:['name'=>$ID];
      $Offer = $this->DB->GET('MUP.offers.offers',$W,'*',1);
      if(!empty($Q)){
        $Offer['elements'] = $this->GetOfferElements($ID);
        return $Offer;
      }
      $this->ALERTS[] = FAIL('Offer Not Found! ~ '.$ID.Debug($this->DB));
      return false;
    }
  /**
   * GetOfferElements
   * -------------------------
   **/
  public function GetOfferElements($ID)
    {

    }
  /**
   * GetList
   * -------------------------
   **/
  public function GetList($ID,$S,$s)
    {
      $W = is_numeric($ID)?['id'=>$ID]:['name'=>$ID];
      $W['active'] = 1;
      $List = $this->DB->GET('EMAILS.lists.lists',$W,'*',1);
      if(!empty($List)){
        $this->Target = $T = $List['target'];
        $W = ['list'=>$ID];
        $e = $this->DB->GET('EMAILS.emails.'.$T.'__emails',$W,['md5'=>'id','email','status'],100000);
        $List['size']['total'] = count($e);
        if(!empty($e)){
          $e = isset($e['id'])?[$e]:$e;
          foreach($e as $md5=>$email){
            if(in_array($email['status'],$S)){
              $_E[$md5] = $email['email'];
              $MD5[$md5] = $md5; 
            }
          }
          $List['size']['status'] = count($_E);
          $MD5 = array_chunk($MD5,5000);
          $c = 0;
          foreach($MD5 as $chunck){
            $sup = $this->DB->GET('MUP.suppression.sublists__'.$s,['md5__IN'=>$chunck],['md5'=>'id','md5'],count($chunck));
            if(!empty($sup)){
              $sup = isset($sup['id'])?[$sup]:$sup;
              foreach($sup as $_sup){
                unset($_E[$_sup['md5']]);
                $c++;
              }
            }
          }
          $this->ALERTS[] = PASS($c.' emails suppressed');
        }
        if(!empty($_E)){
          $List['size']['suppressed'] = count($_E);
          $List['emails'] = $_E;
          return $List;
        }
      }
      $this->ALERTS[] = FAIL('List Not Found! ~ '.$ID.Debug($this->DB));
      return false;
    }
}
?>