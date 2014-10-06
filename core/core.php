<?
/**                                    
 * assign
 * -------------------------
 * return the value of a variable, while 
 * destroying the existing variable
 * -------------------------
 * @param mixed $X // variable to unset and return
 * -------------------------
 **/
function assign(&$X)                   
  {
    $x = $X;
    unset($X);
    return $x;
  }
/**                                    
 * RegisterGlobals
 * -------------------------
 *    G = $_GET
 *    P = $_POST
 *    C = $_COOKIE
 *    F = $_FILES
 *    R = $_REQUEST
 *    E = $_ENV
 *    S = $_SERVER
 * -------------------------
 * default = GPC
 * -------------------------
 **/
function RegisterGlobals($o='GPC')     
  {
    $o = str_split(strtolower($o));
    array_map('RegisterArray',$o);
  }
/**                                    
 * RegisterArray
 * -------------------------
 * Set Array into Global variables
 * -------------------------
 **/
function RegisterArray($X)             
  {
    static $__    = [
      'e' => '_ENV',
      'g' => '_GET',
      'p' => '_POST',
      'c' => '_COOKIE',
      'r' => '_REQUEST',
      's' => '_SERVER',
      'f' => '_FILES'];
    global $$__[$X];
    foreach( $$__[$X] as $k=>$v)
      $VARS[$k] = $v;
  }
/**                                    
 * Write
 * -------------------------
 **/
function Quit($MSG)                    
  {
    $MSG = is_array($MSG)?$MSG:[$MSG];
    $ALERT = [];
    $BR = setBR();
    foreach($MSG as $M)
      $ALERT[] = setClR($M,'yellow',['bold','intense']);
    echo $BR.setCLR('EXITING SCRIPT','red').$BR;
    if(!empty($ALERT))
      echo implode($BR,$ALERT).$BR;
    exit();
  }
/**                                    
 * Write
 * -------------------------
 **/
function Write($message = false)       
  {
    return $message.setBR();
  }
/**                                    
 * PASS
 * -------------------------
 **/
function PASS($message = false)        
  {
    return setClr('  OK!  ','green').' ~ '.$message.setBR();
  }
/**                                    
 * FAIL
 * -------------------------
 **/
function FAIL($message = false)        
  {
    return setClr('ERROR! ','red',['bold','intense']).' ~ '.$message.setBR();
  }
/**                                    
 * setBR
 * -------------------------
 **/
function setBR()                       
  {
    $BR = empty(LF)?"<br>":LF;
    return $BR;
  }
/**                                    
 * setClr
 * -------------------------
 **/
function setClr($M,$C=false,$S=false)  
  {
    if(defined('output') && (output == 'html')){
      switch ($C) {
        case 'red'    : $m[] = red; break;
        case 'grey'   : $m[] = grey; break;
        case 'gray'   : $m[] = grey; break;
        case 'green'  : $m[] = green; break;
        case 'yellow' : $m[] = yellow; break;
        default       : $m[] = white;
      }
      if(!empty($S)){
        $S = is_array($S)?$S:[$S];
        foreach($S as $s)
          switch ($s) {
            case 'bold'       : list($m[],$X[]) = ['<b>','</b>']; break;
            case 'underline'  : list($m[],$X[]) = ['<u>','</u>']; break;
            case 'italic'     : list($m[],$X[]) = ['<i>','</i>']; break;
            default           : break;
          }
        rsort($X);
      }
      $m[] = $M;
      if(!empty($X)){foreach($X as $x){$m[] = $x;}}
      $m[] = white;
      $_   = implode('',$m);
    }else
      $_ = BashCLR($C,$S).$M.BashCLR();
    return $_;
  }
/**                                    
 * BashCLR
 * -------------------------
 * translate readable color to bash color code
 * -------------------------
 * @param string $C // Color Name (default = white)
 * @param mixed @S // Optional Styles (default = none)
 * -------------------------
 * $C Opts: black , red , green , yellow , blue , purple , cyan
 * $S Opts: underline , bold , intense , background
 **/
function BashCLR($C=false,$T=false)    
  {
    switch ($C) {
      case 'black':  $_C = 30;
      case 'red':    $_C = 31;
      case 'green':  $_C = 32;
      case 'yellow': $_C = 33;
      case 'blue':   $_C = 34;
      case 'purple': $_C = 35;
      case 'cyan':   $_C = 36;
      case 'white':  $_C = 37;
      default:       $_C = 0;
    }
    $S = is_array($T)?$T:[$T];
    foreach($S as $s)
      switch (strtolower($s)){
        case 'underline': 
          $_S = 4;
        case 'bold': 
          $_S = 1;
        case 'intense': 
          $_S = ($_S!==false)?$_S:0;
          $_C += 30;
        case 'background': 
          $_S = ($_S!==false)?$_S:false;
          $_C += 10;
        default: 
          $_S = false;
      }
    $_S = ($_S===false)?'':$_S.';';
    $_C = ($_C===false)?'':$_C.'m';
    $CLR = "'\e[{$_S}{$_C}'";
    return $CLR;
  }
/**                                    
 * SetDIR
 * -------------------------
 * Set the running directories
 * -------------------------
 **/
function SetDIR()                      
  {
    $DIR = str_ireplace('\\core', '', __DIR__);
    $DIR = str_ireplace('core', '', $DIR);
    $DIR = rtrim($DIR,'/');
    define('DIR', $DIR.'/');
    define('DATA', DIR.'data/');
    define('LOGS', DIR.'logs/');
    define('APPS', DIR.'apps/');
    define('CORE', DIR.'core/');
    define('CONF', CORE.'conf/');
  }
/**                                    
 * Req
 * -------------------------
 * Require supplied files if they exist
 * -------------------------
 * @param mixed $D // array or string of filenames ~( include full paths! )
 * -------------------------
 **/
function Req($D,$P=false)              
  {
    if(!empty($D)){
      $P = empty($P)?false:rtrim($P,'/').'/';
      $D = is_array($D)?$D:[$D];
      foreach($D as $d){
        $d = ltrim($d,'/');
        $F[] = $P.$d;
      }
      $c = 0;
      $t = empty($F)?setCLR('Oops! No Files given...','red',['bold']):count($F);
      if(!is_numeric($t)){return $t;}
      foreach($F as $f)
        if(file_exists($f)){
          require_once($f);
          $c++;
        }else
          $x[$f] = setCLR($f.' does not exist!','red',['bold']);
    }
    if(!empty($x))
      $_ = implode(LF,$x);
    return $c.@$_;
  }
/**                                    
 * ErrorOut
 * -------------------------
 * Custom Error Handler that displays the current
 * filename and function/method
 * -------------------------
 * @param string $MSG // custom message to display
 * -------------------------
 **/
function ErrorOut($errno, $errstr, $errfile, $errline)
  {
    if (!(error_reporting() & $errno))
      return;
    $d = str_ireplace('\\','/',DIR);
    $f = str_ireplace('\\','/',$errfile);
    $file = str_ireplace($d,'',$f);
    $m = setCLR($errstr,'red');
    $MSG = setCLR($m."\n\tError on line $errline in ".$file,'grey','italic');
    switch ($errno) {
      case E_USER_ERROR:$E = setCLR("<b>FATAL ERROR</b>",'red','bold').' ~ '.$MSG;
        echo "<pre>{$E}</pre>";exit(1);break;
      case E_USER_WARNING:$E = setCLR("<b>WARNING!</b>",'red','bold').' ~ '.$MSG;
        break;
      case E_USER_NOTICE:$E = setCLR("<b>NOTICE!</b>",'red','bold').' ~ '.$MSG;
        break;
      default:$E = setCLR("<b>Unknown error!</b>",'red','bold').' ~ '.$MSG;
        break;
    }
    echo "<pre>{$E}</pre>";
    return true;
  }
/**                                    
 * SetErrorHandler
 * -------------------------
 **/
function SetErrorHandler()             
  {
    set_error_handler('ErrorOut');
  }
/**                                    
 * Debug
 **/
function Debug($C,$T=false)            
  {
    $_[] = "<pre>";
    $_[] = "<b>{$T}</b><hr >";
    $C = print_r($C,true);
    $_[] = htmlspecialchars($C);
    //$_[] = $C;
    $_[] = "</pre>";
    return implode(LF,$_);
  }
/**                                    
 * CheckDirs
 **/
function CheckDirs($D=false)           
  {
    $_D[] = LOGS;
    $_D[] = LOGS.'pmta/';
    $_D[] = LOGS.'pmta/smtp/';
    $_D[] = LOGS.'pmta/acct/';
    $_D[] = LOGS.'pmta/trans/';
    $_D[] = LOGS.'apps/';
    $D = empty($D)?$_D:[$D];
    if(!empty($D))
      foreach($D as $d)
        if(!file_exists($d))
          mkdir($d);
  }
/**                                    
 * LineBreak
 * -------------------------
 **/
function LineBreak($string)            
  {
    if(!is_string($string)) return $string;
    $string = explode("\n",str_ireplace("\r",'',$string));
    return $string;
  }
// Run some startup script stuff       
  SetDIR();
  SetErrorHandler();
  CheckDirs();
  Req('class/www.class.php',CORE);
  include CONF.'settings.php';
  $CFG = new CFG($_);
?>