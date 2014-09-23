<?php
class www {
  var $HTML;
  public function __construct($CFG)
    {
      $this->CFG = $CFG;
      $this->DB = $CFG->DB;
      $this->Head();
      $this->Info();
      $this->Navbar();
      $this->Frame();
      $this->Foot();
    }
  /**
   * Btns
   * -------------------------
   **/
  static function Btns($Btns)
    {
      if(empty($Btns)){return;}
      $B = is_array($Btns)?$Btns:[$Btns];
      foreach($B as $b){$_[] = self::Btn($b);}
      $_ = implode('',$_);
      return '<div class="btn">'.$_.'</div>';
    }
  /**
   * Btn
   * -------------------------
   **/
  static function Btn($b)
    {
      if(!empty($b)){
        $ID = str_ireplace(" ",'',$b);
        $_ = '<button id="'.$ID.'">'.$b.'</button>';
        return $_;
      }
    }
  /**
   * Info
   * -------------------------
   **/
  public function Info()
    {
      require_once APPS.'server_stats/class/server_stats.class.php';
      $STATS = new STATS($this->DB);
      $HTML[] = '<td class="i" colspan="2">';
      $HTML[] = '<span class="info pmta"><h1>'.strtoupper(hostname).'</h1></span>';
      $HTML[] = '<span class="info">'.date('Y-m-d H:i:s').'</span> ';
      $HTML[] = '<span class="info">'.$STATS->MemStats().'</span> ';
      $HTML[] = '<span class="info">'.$STATS->NetStats().'</span> ';
      $HTML[] = '<span class="info">'.$STATS->HddStats().'</span> ';
      $HTML[] = '</td>';
      $HTML[] = '</tr><tr>';
      $this->HTML($HTML);
    }
  /**
   * Navbar
   * -------------------------
   **/
  public function Navbar()
    {
      $HTML[] = '<td class="nb">';
      $HTML[] = '<h2>Scripts</h2>';
      $HTML[] = $this->GetFiles('apps',true);
      $HTML[] = '<h2>Logs</h2>';
      $HTML[] = $this->GetFiles('logs');
      $HTML[] = '<h2>Output</h2>';
      $HTML[] = $this->GetFiles('data');
      $HTML[] = '</td>';
      $this->HTML($HTML);
    }
  /**
   * HTML
   * -------------------------
   **/
  public function HTML($html)
    {
      $HTML = empty($this->HTML)?[]:$this->HTML;
      $this->HTML = array_merge($HTML,$html);
    }
  /**
   * GetFiles
   * -------------------------
   **/
  public function GetFiles($p,$check=false)
    {
      $DIR = str_ireplace('\\','/',DIR.$p).'/';
      $DH = opendir($DIR);
      while($FN = readdir($DH))
        if(!in_array($FN,['.','..'])){
          if($check)
            CheckDirs(LOGS.'apps/'.$FN.'/');
          $x = ucwords(str_replace('_',' ',$FN));
          $li[] = '<li><a href="/'.$p.'/'.$FN.'" target="Frame">'.$x.'</a></li>';
        }
      closedir($DH);
      $li = empty($li) ? '<li style="color:red;">no files in '.$p.'/</li>' : implode(LF,$li) ;
      $ul = '<ul>'.$li.'</ul>';
      return $ul;
    }
  /**
   * JS
   * -------------------------
   **/
  public static function JS()
    {
      $JS[]  = 'http://mu-portal.com/libs/jquery.js';
      foreach($JS as $js)
        $_JS[] = '<script type="text/javascript" src="'.$js.'"></script>';
      return implode(LF,$_JS);
    }
  /**
   * CSS
   * -------------------------
   **/
  public static function CSS()
    {
      $S = [];     // CSS Styles
      $S['*']             = [
        'font-family'     => 'courier',
        'color'           => 'white',
        'border'          => 'none',
        'text-decoration' => 'none',
        'padding'         => '0px',
        'margin'          => '0px',
        'border-collapse' => 'collapse',
        'text-shadow'     => '0px 0px 3px black'
        ];
      $S['.body']         = [
        'background'      => 'black'
        ];
      $S['.out']          = [
        'background'      => '#222222',
        'padding'         => '10px 25px'
        ];
      $S['.out h3']       = [
        'border-bottom'   => '1px solid #ffbb00',
        'margin'          => '0px -25px',
        'padding'         => '5px 25px'
        ];
      $S['.body, .out']   = [
        'position'        => 'absolute',
        'top'             => 0,
        'bottom'          => 0,
        'left'            => 0,
        'right'           => 0
        ];
      $S['table.body']    = [
        'height'          => '100%',
        'width'           => '100%'
        ];
      $S['a']             = [
        'display'         => 'block',
        'white-space'     => 'nowrap',
        'font-size'       => '14px',
        'font-weight'     => 'bold'
        ];
      $S['ul']            = [
        'list-style'      => 'none'
        ];
      $S['li']            = [
        'font-size'       => '10px',
        'margin'          => '0px'
        ];
      $S['li a']          = [
        'padding'         => '2px 10px',
        'color'           => 'grey'
        ];
      $S['li a:hover']    = [
        'color'           => 'white',
        'font-style'      => 'italic'
        ];
      $S['.nb']           = [
        'padding'         => '10px',
        'box-shadow'      => 'inset 0px 0px 10px black'
        ];
      $S['td']            = [
        'vertical-align'  => 'top'
        ];
      $S['td.i']          = [
        'height'          => '50px'
        ];
      $S['span.info']     = [
        'display'         => 'inline',
        'font-style'      => 'italic',
        'padding'         => '5px 15px',
        'margin'          => '10px 5px',
        'float'           => 'right'
        ];
      $S['span.pmta']     = [
        'padding'         => '2px 10px',
        'margin'          => '0px',
        'float'           => 'left'
        ];
      $S['span.pmta h1']  = [
        'font'            => '42px impact',
        'color'           => 'white'
        ];
      $S['.f']            = [
        'display'         => 'inline-block',
        'postition'       => 'absolute',
        'top'             => 0,
        'bottom'          => 0,
        'left'            => 0,
        'right'           => 0,
        'width'           => '100%',
        'height'          => '99%',
        'background'      => '#222222',
        'color'           => 'white'
        ];
      $S['h2']            = [
        'border-bottom'   => '1px solid #ffbb00'
        ];
      $S['h1,h2,h3']      = [
        'color'           => '#ffbb00',
        'font-family'     => 'impact'
        ];
      $S['h1,h2']         = [
        'margin'          => '0px -10px',
        'padding'         => '5px 20px'
        ];
      $S['h2,h3']         = [
        'font-size'       => '18px'
        ];
      $S['button']        = [
        'padding'         => '2px 10px',
        'color'           => 'black',
        'text-shadow'     => 'none',
        'margin'          => '0px 2px',
        'font-size'       => '14px',
        'font-weight'     => 'bold',
        'border-radius'   => '3px',
        'box-shadow'      => 'inset 0px 0px 5px black'
        ];
      $S['button:hover']  = [
        'cursor'          => 'pointer',
        'background'      => 'lime',
        'box-shadow'      => '0px 0px 5px white'
        ];
      $S['div.btn']       = [
        'padding'         => '10px',
        'background'      => 'black',
        'margin'          => '0px -25px',
        'border-bottom'   => '1px dashed white',
        'text-align'      => 'right',
        'width'           => '100%'
        ];
      $S['div.alt']       = [
        'background'        => 'white',
        'color'             => 'grey',
        'font-size'         => '12px',
        'text-shadow'       => 'none',
        'margin'            => '0px -25px',
        'padding'           => '10px 25px',
        'box-shadow'        => 'inset 0px 0px 5px black'
        ];
      $S['div.alt *']     = [
        'color'           => 'grey',
        'text-shadow'     => 'none'
        ];
      $S['div.alt pre']   = [
        'border'          => '1px dashed grey',
        'padding'         => '5px 10px',
        'background'      => '#FFF3D1'
        ];
      $S['div.alt hr']    = [
        'background'      => 'grey',
        'height'          => '1px'
        ];
      $S['.tbl tr td']    = [
        'padding'           => '2px 5px'
        ];
      $S['.tbl tr:hover td'] = [
        'background'      => '#cccccc',
        'color'           => 'black',
        'cursor'          => 'pointer'
        ];
      $S['.tbl']          = [
        'box-shadow'      => '0px 0px 5px black',
        'background'      => '#ececec',
        'width'           => '100%'
        ];
      $S['tr.header th']  = [
        'background'      => 'black',
        'color'           => 'white',
        'padding'         => '3px',
        'text-align'      => 'center'
        ];
      foreach($S as $e=>$s){
        $C = [$e.'{'];
        foreach($s as $k=>$v)
          $C[] = "  ".$k.':'.$v.';';
        $C[] = '}';
        $_CSS[] = implode(LF,$C);
        }
      $_CSS = implode(LF,$_CSS);
      return '<style type="text/css">'.$_CSS.'</style>';
    }
  /**
   * Alt
   * -------------------------
   * wrap content in black on white styles
   * -------------------------
   * @param string $C // content to stylize
   * -------------------------
   **/
  static function Alt($C)
    {
      return '<div class="alt">'.$C.'</div>';
    }
  /**
   * Script Head
   * -------------------------
   **/
  static function ScriptHead($Title=false)
    {
      $HTML[] = '<!DOCTYPE html>';
      $HTML[] = '<html>';
      $HTML[] = '<head>';
      $HTML[] = self::JS();
      $HTML[] = self::CSS();
      $HTML[] = '</head>';
      $HTML[] = '<body class="out">';
      $HTML[] = '<pre>';
      $HTML[] = '<h3>'.$Title.'</h3>';
      return implode(LF,$HTML);
    }
  /**
   * Head
   * -------------------------
   **/
  public function Head()
    {
      $HTML[] = '<!DOCTYPE html>';
      $HTML[] = '<html>';
      $HTML[] = '<head>';
      $HTML[] = $this->JS();
      $HTML[] = $this->CSS();
      $HTML[] = '</head>';
      $HTML[] = '<body class="body">';
      $HTML[] = '<table class="body">';
      $HTML[] = '<tr>';
      $this->HTML($HTML);
    }
  /**
   * Foot
   * -------------------------
   **/
  public function Foot()
    {
      $HTML[] = '</tr>';
      $HTML[] = '</table';
      $HTML[] = '</body>';
      $HTML[] = '</html>';
      $this->HTML($HTML);
    }
  /**
   * Frame
   * -------------------------
   **/
  public function Frame()
    {
      $HTML[] = '<td width="100%">';
      $URL = 'http://'.$this->CFG->hostname.'.'.$this->CFG->domain.':'.$this->CFG->mtaPort;
      $WEB = file_get_contents($URL);
      $WEB = str_ireplace('href="','href="'.$URL.'/',$WEB);
      $WEB = str_ireplace('src="logo.gif"','src="'.$URL.'/logo.gif"',$WEB);
      $WEB = str_ireplace("\n",'',$WEB);
      $WEB = str_ireplace("   ",'',$WEB);
      $WEB = www::ScriptHead('PowerMTA Status').'<div class="alt">'.$WEB.'</div>';
      file_put_contents( DATA.'status',$WEB);
      $HTML[] = '<iframe class="f" name="Frame" id="Frame" src="/data/status"></iframe>';
      $HTML[] = '</td>';
      $this->HTML($HTML);
    }
}
?>