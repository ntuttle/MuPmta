<?php
class incoming_emails {
  
  var $To;
  var $From;
  var $Subject;
  var $Date;
  var $ContentType;
  var $ContentTransferEncoding;
  var $boundary;
  var $charset;
  var $partCount = 0;

  var $Headers;
  var $Body;

  var $EMAIL;
  var $DB;

  public function __construct($CFG)
    {
      $this->DB = $CFG->DB;
    }
  /**
   * ReadEmail
   * -------------------------
   **/
  public function ReadEmail($EMAIL)
    {
      $this->EMAIL = $EMAIL;
      $this->ParseEmail();
      //$this->RunExtraScripts();
      //$this->DB_Insert();
    }
  /**
   * ParseEmail
   * -------------------------
   **/
  public function ParseEmail()
    {
      list($headers,$body) = explode("\n\n",str_ireplace("\r",'',$this->EMAIL),2);
      $this->Headers = $this->ParseHeaders($headers);
      $this->ParseBody($body,$this->boundary);
    }
  /**
   * ParseHeaders
   * -------------------------
   **/
  public function ParseHeaders($headers)
    {
      $H = LineBreak($headers);
      $i = 0;
      foreach($H as $h)
        if(preg_match('/^([^ ]+):[ ]?(.*)$/',$h,$x)){
          $_h = ucfirst($x[1]);
          $_v  = $x[2];
          $i = (@$i+1);
          $_H[$i][$_h][] = $_v;
          $l = $_h;
        }elseif($i>0)
          $_H[$i][$l][] = trim($h);
      foreach($_H as $i=>$_h)
        foreach($_h as $h=>$v)
          $_H[$i][$h] = implode("\r\n\t",$v);
      $this->To      = $this->GetHeader($_H,'To');
      $this->From    = $this->GetHeader($_H,'From');
      $this->Subject = $this->GetHeader($_H,'Subject');
      $this->Date    = $this->GetHeader($_H,'Date');
      $C = $this->ParseContentHeaders($_H,false);
      foreach($C as $k=>$v) 
        if(!empty($v)){
          $k = str_ireplace('-','',$k);
          $this->$k = $v;
        }
      return @$_H;
    }
  /**
   * GetHeader
   * -------------------------
   **/
  public function GetHeader($_H,$F)
    {
      if(!empty($_H))
        foreach($_H as $i=>$H)
          foreach($H as $h=>$v)
            if($h==$F){
              if(is_array($v))
                $v = implode("\r\n\t",$v);
              return $v;
            }
      return false;
    }
  /**
   * ParseContentHeaders
   * -------------------------
   * Parse out content-x headers
   * -------------------------
   **/
  public function ParseContentHeaders($H,$return=true)
    {
      $T = $B = $C = $E = $D = false;
      $t = 'Content\-Type[ ]{0,1}:[ \r\n\t]{0,5}([^; ]+)[; \t\r\n]{0,5}';
      $c = 'charset\=["\']{0,1}([^"\' \r\n]+)["\']{0,1}[; \t\r\n]{0,5}';
      $b = 'boundary\=["\']{0,1}([^"\' \r\n]+)["\']{0,1}';
      $R1 = '/^'.$t.$c.$b.'/';
      $R2 = '/^'.$t.$b.'/';
      $R3 = '/^'.$t.$c.'/';
      $R4 = '/^'.$t.'/';
      $R5 = '/^Content\-Transfer\-Encoding[ ]{0,}:[ ]{0,}(.*)$/';
      $R6 = '/^[ \t]+charset\=["\']{0,}([^\'"\n]+)["\']{0,}(.*)$/';
      $R7 = '/^[ \t]+boundary\=["\']{0,}([^\'"\n]+)["\']{0,}(.*)$/';
      if(!is_array($H))
        $H = LineBreak($H);
      foreach($H as $i=>$_h)
        if(!is_array($_h))
          $_H[$i] = $_h;
        else
          foreach($_h as $h=>$v){
            if(is_array($v))
              $v = implode("\r\n\t",$v);
            $_H[$i] = $h.': '.$v;
          }
      $cnt = empty($return) ? count($_H) : 5;
      foreach($_H as $i=>$h){
        if($i>$cnt){break;}
        if(in_array(trim($h),['--'])){
          unset($_H[$i]);
        }else{
          if(empty($T)){
            if(preg_match($R1,$h,$x)){
              list($_,$T,$C,$B) = $x;
              unset($_H[$i]);
            }
            elseif(preg_match($R2,$h,$x)){
              list($_,$T,$B) = $x;
              unset($_H[$i]);
            }
            elseif(preg_match($R3,$h,$x)){
              list($_,$T,$C) = $x;
              unset($_H[$i]);
            }
            elseif(preg_match($R4,$h,$x)){
              list($_,$T) = $x;
              unset($_H[$i]);
            }
          }
          if(empty($E)){
            if(preg_match($R5,$h,$x)){
              list($_,$E) = $x;
              unset($_H[$i]);
            }
          }
          if(empty($C)){
            if(preg_match($R6,$h,$x)){
              list($_,$C) = $x;
              unset($_H[$i]);
            }
          }
          if(empty($B)){
            if(preg_match($R7,$h,$x)){
              list($_,$B) = $x;
              unset($_H[$i]);
            }
          }
        }
      }
      $_H = trim(implode(LF,$_H));
      if($return===true)
        $R = [$T,$E,$C,$B,$_H];
      else
        $R = [$T,$E,$C,$B];
      if(stristr($E,'quoted-printable'))
        $R['decoded'] = quoted_printable_decode($_H);
      elseif(stristr($E,'base64'))
        $R['decoded'] = base64_decode($_H);
      return $R;
    }
  /**
   * ParseBody
   * -------------------------
   **/
  public function ParseBody($B,$b=false,$r=false)
    {
      if(!empty($b))
        $B = explode($b,$B);
      if(!is_array($B))
        $B = [$B];
      foreach($B as $i=>$_b){
        $this->partCount++;
        $C = $this->ParseContentHeaders($_b);
        list($ContentType,$ContentTransferEncoding,$charset,$boundary,$parts) = $C;
        if($boundary)
          $parts = $this->ParseBody($parts,$boundary,true);
        if(!empty($parts)){
          $_B[] = $parts;
          $this->Body[$this->partCount] = [
            'ContentType' => $ContentType,
            'ContentTransferEncoding' => $ContentTransferEncoding,
            'charset' => $charset,
            'parts' => $parts
            ];
        }
      }
      return $_B;
    }
  /**
   * RunExtraScripts
   * -------------------------
   **/
  public function RunExtraScripts()
    {

    }
  /**
   * DB_Insert
   * -------------------------
   **/
  public function DB_Insert()
    {
      $F = ['date','headers','body','server'];
      $V = [$this->Date,$this->Headers,$this->Body,hostID];
      $this->DB->PUT('LOGS.emails.archive',$F,[$V],'DELAYED');
    }
}

?>
