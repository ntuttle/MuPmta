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
      file_put_contents(LOGS.'apps/incoming_emails/last.msg',$EMAIL);
      $this->EMAIL = $EMAIL;
      $this->ParseEmail();
      $this->DB_Insert();
    }
  /**
   * ParseEmail
   * -------------------------
   **/
  public function ParseEmail()
    {
      list($headers,$body) = explode("\n\n",str_ireplace("\r",'',$this->EMAIL),2);
      $this->Headers = $this->ParseHeaders($headers);
      $this->ParseBody($body,$this->boundary,true);
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
      $this->ContentType = $C[0];
      $this->ContentTransferEncoding = $C[1];
      $this->charset = $C[2];
      $this->boundary = $C[3];
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
      $T = $B = $C = $Enc = $D = false;
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
      $cnt = empty($return) ? count($_H) : 10;
      foreach($_H as $i=>$h){
        if($i>$cnt){break;}
        if(in_array(trim($h),['--',''])){
          unset($_H[$i]);
          break;
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
          if(preg_match($R5,$h,$x)){
            if(empty($Enc)){
              list($_,$Enc) = $x;
              unset($_H[$i]);
            }
          }
          if(preg_match($R6,$h,$x)){
            if(empty($C)){
              list($_,$C) = $x;
              unset($_H[$i]);
            }
          }
          if(preg_match($R7,$h,$x)){
            if(empty($B)){
              list($_,$B) = $x;
              unset($_H[$i]);
            }
          }
        }
      }
      $_H = trim(implode('',$_H));
      if($return===true)
        $R = [$T,$Enc,$C,$B,$_H];
      else
        $R = [$T,$Enc,$C,$B];
      if(stristr($Enc,'quoted-printable'))
        $R['decoded'] = quoted_printable_decode($_H);
      elseif(stristr($Enc,'base64')){
        $_H = LineBreak($_H);
        foreach($_H as $h)
          if(!empty($h)){
            if(preg_match('/^Content\-ID[ ]?:[ <]{0,}([^>]+)[ >]{0,}/',$h,$x))
              $rid = $x[1];
            elseif(!preg_match('/^Content.*/',$h,$x))
              if(!preg_match('/^  .*/',$h,$x))
                $__H[] = $h;
          }
        if(!empty($__H))
          if(stristr($T,'image')){
            $img = 'data:image/jpg;base64,'.implode('',$__H).'';
            $this->Replace['cid:'.$rid] = $img;
            $R['decoded'] = $img;
            return false;
          }
      }
      return $R;
    }
  /**
   * ShowHeaders
   * -------------------------
   **/
  public function ShowHeaders()
    {
      if(!empty($this->Headers))
        foreach($this->Headers as $i=>$header)
          foreach($header as $k=>$v){
            if(is_array($v)){$v = implode(LF,$v);}
            $_HEADERS[] = ['header'=>$k,'value'=>htmlspecialchars($v)];
          }
      return $_HEADERS;
    }
  /**
   * ShowParts
   * -------------------------
   **/
  public function ShowParts()
    {
      foreach($this->Body as $i=>$b){
        if(is_array($b['parts']))
          foreach($b['parts'] as $c=>$_b){
            $part = empty($_b['decoded']) ? $_b['parts'] : $_b['decoded'] ;
            $HTML[] = Debug($part,'Part#'.$c.' ~ '.$_b['ContentType'].' ~ '.$_b['ContentTransferEncoding']);
          }
        else{
          $part = empty($b['decoded']) ? $b['parts'] : $b['decoded'] ;
          $HTML[] = Debug($part,'Part#'.$i.' ~ '.$b['ContentType'].' ~ '.$b['ContentTransferEncoding']);
        }
      }
      return implode(LF,$HTML);
    }
  /**
   * MakeReplace
   * -------------------------
   **/
  public function MakeReplace($C)
    {
      if(!empty($this->Replace))
        foreach($this->Replace as $k=>$v)
          $C = str_ireplace($k,$v,$C);
      return $C;
    }
  /**
   * ParseBody
   * -------------------------
   **/
  public function ParseBody($B,$b=false,$set=false)
    {
      if(!empty($b))
        $B = explode($b,$B);
      if(!is_array($B))
        $B = [$B];
      $_B = [];
      foreach($B as $i=>$_b){
        if(is_array($_b))
          foreach($_b as &$__b)
            $__b = trim($__b," \r\n\t-");
        else
          $_b = trim($_b," \r\n\t-");
        $C = $this->ParseContentHeaders($_b);
        list($ContentType,$ContentTransferEncoding,$charset,$boundary,$parts) = $C;
        if($boundary)
          $parts = $this->ParseBody($parts,$boundary);
        if(!empty($parts)){
          $_B[$i] = [
            'ContentType' => $ContentType,
            'ContentTransferEncoding' => $ContentTransferEncoding,
            'charset' => $charset,
            'parts' => $parts
            ];
          if(!empty($C['decoded']))
            $_B[$i]['decoded'] = $C['decoded'];
        }
      }
      if($set)
        foreach($_B as $b){
          if(is_array($b['parts']))
            foreach($b['parts'] as &$p){
              $p['parts'] = $this->MakeReplace(@$p['parts']);
              $p['decoded'] = $this->MakeReplace(@$p['decoded']);
            }
          $this->Body[$this->partCount] = [
            'ContentType' => $b['ContentType'],
            'ContentTransferEncoding' => $b['ContentTransferEncoding'],
            'charset' => $b['charset'],
            'parts' => $this->MakeReplace($b['parts']),
            'decoded' => $this->MakeReplace(@$b['decoded'])
            ];
          $this->partCount++;
        }
      return $_B;
    }
  /**
   * DB_Insert
   * -------------------------
   **/
  public function DB_Insert()
    {
      $F = ['date','headers','body','server'];
      $V = [$this->Date,json_encode($this->Headers,JSON_PRETTY_PRINT),json_encode($this->Body,JSON_PRETTY_PRINT),hostID];
      $this->DB->PUT('LOGS.emails.archive',$F,[$V],'DELAYED');
      echo Debug($this->DB);
    }
}

?>
