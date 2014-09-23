<?
class TBL {
	var $DEBUG = false;
  /**
   * Start
   * -------------------------
   **/
  public function Start($T = false, $ID = false, $W = false, $C = false)
    {
      if ($T)
        $T = "<h1>{$T}</h1>";
      if ($ID) 
        $ID = " id=\"{$ID}\"";
      else
        $ID = '';
      if ($W) {
        if (is_numeric($W)) 
          $W .= 'px';
        $W = " style=\"max-width:{$W};width:{$W}\"";
      }
      if ($C) 
        $C = " class=\"{$C}\"";
      $this->R('<div class="widget-body">'.$T."<table".$ID.$W.$C.">");
  	}
	/**
   * Button
   * -------------------------
   **/
  public function Button($T, $L = false, $C = false, $N = false, $H = false)
    {
      $S=" class=\"button {$C}\"";
      if(stristr($link, '/')) 
        $L = " href=\"".WEB."\"{$L}\"";
      elseif($L)
        $L = " id=\"{$L}\"";
      if($H)
        $H = " title=\"{$H}\"";
      if($N)
        $N = " name=\"{$N}\"";
      $this->R("<a".$L.$S.$H.$N.">".$T."</a>");
    }
	/**
   * Row
   * -------------------------
   **/
  public function Row($C = false, $ID = false, $H = false)
    {
      $this->eH = true;
      if($C)
        $C = " class=\"{$C}\"";
      if($ID)
        $ID = " id=\"{$ID}\"";
      if($H)
        $H = "<thead>";
      else
        $this->eH = false;
      $this->R($H."<tr".$C.$ID.">");
    }
	/**
   * Cell
   * -------------------------
   **/
  public function Cell($V=false,$W=false,$ID=false,$C=false,$a=false,$cols=false,$rows=false,$T=false)
    {
  		$TD = 'td';
  		if ($W) {$W = " style=\"width:{$W};max-width:{$W};min-width:{$W};\"";}
  		if (in_array($a, ['left', 'right'])) {$a = " style=\"text-align:{$a};\"";}
  		if($V !== false)
  			if($ID)
          $ID = " id=\"{$ID}\"";
        else
          $ID = " id=\"{$V}\"";
  		if($cols)
        $cols = " colspan=\"{$cols}\"";
  		if($rows)
        $rows = " rowspan=\"{$rows}\"";
  		if($T)
        $TD = 'th';
      elseif($this->eH)
        $TD = 'th';
  		if($TD == 'th') {
  			$C .= " sorting";
  			$V = $V;
  			$W .= " role=\"columnheader\" controls=\"dt_basic\"";
  		}
  		if($C)
        $C = " class=\"{$C}\"";
  		$this->R("<".$TD.$W.$a.$ID.$C.$cols.$rows.">".$V."</".$TD.">");
  	}
	/**
   * ToolCell
   * -------------------------
   **/
  public function ToolCell($I, $L, $N = false, $T = false)
    {
      if(stristr($L, '/'))
        $L = " href=\"".WEB.$L."\"";
      elseif($L)
        $L = " id=\"{$L}\"";
      if($N)
        $N = " name=\"{$N}\"";
      if($T)
        $T = " title=\"{$T}\"";
      if(isset($I['icon']))
        $I = $this->Icon($I['icon']);
      $B = "<a".$L.$N.$T.">".$I."</a>";
      $this->R("<td class=\"toolbar\" style=\"max-width:25px;width:25px;\">{$B}</td>");
    }
	/**
   * Icon
   * -------------------------
   **/
  public function Icon($icon)
    {
      return "<i class=\"fa fa-{$icon}\"></i>";
    }
	/**
   * RowEnd
   * -------------------------
   **/
  public function RowEnd()
    {
      $R = "</tr>";
      if($this->eH)
        $R .= '</thead>';
      $this->R($R);
    }
	/**
   * End
   * -------------------------
   **/
  public function End()
    {
  		$this->R("</table></div>");
  		$_ = $this->_R;
  		unset($this->_R);
  		return implode("\n", $_);
  	}
	/**
   * tBody
   * -------------------------
   **/
  public function tBody($end = false)
    {
  		if($end)
        $end = '/';
  		$this->_R[] = "<{$end}tbody>";
  	}
	/**
   * R
   * -------------------------
   **/
  public function R($V)
    {
  		$this->_R[] = $V;
  	}
	/**
   * Make
   * -------------------------
   **/
  public function Make($A, $T = false, $W = false, $C = 'tbl', $TBLID = false, $F = false)
    {
  		if(empty($A))
        return false;
  		foreach ($A as $ID => $r) {
  			if($this->DEBUG)
          X::Debug($r);
  			if(empty($c)){
  				$c = 0;
  				if(isset($T['title']))
            $TITLE = $T['title'];
          else
            $TITLE = false;
  				$this->Start($TITLE, $TBLID, $W, $C);
  				$this->Row('header', false, true);
  				if(isset($T['left']))
  					foreach($T['left'] as $t){
  						if(isset($t['check']))
  					 		$this->Cell($t['check'],'check','check');
  						else
  							$this->Cell();
  					}
  				if ($this->DEBUG)
            echo "<PRE>";
  				foreach ($r as $k => $v) {
  					if($this->DEBUG){
              echo 'KEY: '.$k.' - VAL: ';
              print_r($v);
              echo "\n";
            }
  					if(isset($v['CLASS']))
              $this->Cell($k, false, false, $v['CLASS']);
            elseif(!in_array($k,['CLASS','ID']) || empty($k))
              $this->Cell($k);
  				}
  				if ($this->DEBUG)
            echo "</PRE>";
  				if (isset($T['right']))
            foreach ($T['right'] as $T)
              $this->Cell();
  				$this->RowEnd();
  				$this->tBody();
  			}
  			$clr = 'white';
  			if (isset($r['CLASS'])) {$clr = $r['CLASS'];}
  			if (isset($r['ID'])) {$ID = $r[$r['ID']];}
  			$this->Row($clr, $ID);
  			if (isset($T['left']))
  				foreach ($T['left'] as $t) {
  					if(stristr($t['id'], '/'))
              $IDnt = $t['id'].'/'.$r[$t['name']];
            else
              $IDnt = $t['id'];
  					if($IDnt == '...')
              $this->Cell(' ');
  					elseif(isset($t['html']))
              $this->Cell($t['html'], 'class', 'id');
  					else {
  						if($t['name'] == 'id')
                $this->ToolCell(['icon' => $t['icon']], $IDnt, $ID);
              else
                $this->ToolCell(['icon' => $t['icon']], $IDnt, $r[$t['name']]);
  					}
          }
  			foreach ($r as $k => $v){
  				$A = false;
  				$W = false;
  				if(is_array($v)){
  					if (isset($v['CLASS']))
              $this->Cell($v['VALUE'], $W, $k, $v['CLASS'], $A);
            else
              $this->Cell(stripslashes(implode("<br>", $v)), $W, $k, false, $A);
  				}elseif(!in_array($k, ['CLASS', 'ID']) || empty($k))
            $this->Cell(stripslashes($v), $W, $k, false, $A);
  			}
  			if(isset($T['right']))
  				foreach($T['right'] as $t){
  					if(stristr($t['id'],'/'))
              $IDnt = $t['id'].'/'.$r[$t['name']];
            else
              $IDnt = $t['id'];
  					if($IDnt == '...')
              $this->Cell(' ');
            else{
  						if($t['name'] == 'id')
                $this->ToolCell(['icon' => $t['icon']], $IDnt, $ID);
              else
                $this->ToolCell(['icon' => $t['icon']], $IDnt, $r[$t['name']]);
  					}
          }
  			$this->RowEnd();
  			$c++;
  		}
  		$this->tBody(1);
  		if($F != false){
  			$this->_R[] = "<tfoot>";
  			$this->_R[] = "<tr class='header'>";
  			foreach ($F as $val)
          $this->_R[] = "<td>$val</td>";
  			$this->_R[] = "</tr>";
  			$this->_R[] = "</tfoot>";
  		}
  		return $this->End();
  	}
}
?>
