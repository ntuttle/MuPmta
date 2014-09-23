<?php
class FORMS {
	var $FORM = array();
	var $title;
	var $id;
	public function __construct($id, $title = false, $class = false, $width = false)
		{
			$this->StartForm($id, $class, $width);
			if ($title) {
				$this->FORM[$id][] = "<div class=\"header\">";
				$this->Header($title);
				$this->FORM[$id][] = "</div>";
			}
		}
	/**
	 * StartForm
	 * -------------------------
	 **/
	public function StartForm($id, $class, $width = false)
		{
			$this->id = $id;
			$id = "id=\"$id\"";
			if ($class === false)
				$class   = 'col-xs-12';
			if (is_numeric($width))
				$width = " style=\"width:{$width}px\"";
			$this->FORM[$this->id][] = "<div class=\"$class\"><form method=\"POST\" $id class=\"smart-form col-xs-12\" {$width}>";
		}
	/**
	 * Div
	 * -------------------------
	 **/
	public function Div($ID, $x = false, $C = false)
		{
			$OG_id = $this->id;
			$this->SelectForm($ID);
			$this->Write('<div class="row '.$C.'">'.$x.'</div>');
			$DIV = $this->PrintPart($id);
			$this->SelectForm($OG_id);
			$this->FORM[$this->id][] = $DIV;
		}
	/**
	 * SelectForm
	 * -------------------------
	 **/
	public function SelectForm($ID)
		{
			$this->id = $ID;
		}
	/**
	 * FooterEnd
	 * -------------------------
	 **/
	public function FooterEnd()
		{
			$this->FORM[$this->id][] = "</div>";
		}
	/**
	 * Footer
	 * -------------------------
	 **/
	public function Footer($content = false)
		{
			$this->FORM[$this->id][] = "<div class=\"footer\">";
			$this->FORM[$this->id][] = $content;
			$this->FORM[$this->id][] = "</div>";
		}
	/**
	 * Br
	 * -------------------------
	 **/
	public function Br($S = false)
		{
			if (is_numeric($S)) {$S  = " style=\"height:{$S}px;\"";} elseif ($S) {$S  = " style=\"{$S}\"";}
			$this->FORM[$this->id][] = "<hr{$S} />";
		}
	/**
	 * Header
	 * -------------------------
	 **/
	public function Header($T, $B = false)
		{
			$this->FORM[$this->id][] = "<h1>".$T;
			if (is_array($B)) {
				if (is_array($B[0]))
					foreach ($B as $b) 
						$this->Button($b[0], $b[1], @$b[2]);
				else
					$this->Button($B[0], $B[1], @$B[2]);
			} elseif ($B)
				$this->FORM[$this->id][] = $B;
			$this->FORM[$this->id][] = "</h1>";
		}
	/**
	 * Span
	 * -------------------------
	 **/
	public function Span($ID, $Display = true, $Content = false)
		{
			$D = empty($D)?" style=\"display:none;\"":'';
			$__ = "<span".$ID.$D.">";
			if ($Content)
				$__ .= $C."</span>";
			$this->FORM[$this->id][] = $__;
		}
	/**
	 * Write
	 * -------------------------
	 **/
	public function Write($C, $S = false)
		{
			$ID                = $this->id;
			$this->FORM[$ID][] = $C;
		}
	/**
	 * EndSpan
	 * -------------------------
	 **/
	public function EndSpan()
		{
			$this->FORM[$this->id][] = "</span>";
		}
	/**
	 * JS
	 * -------------------------
	 **/
	public function JS($javascript)
		{
			if (preg_match('/^http:\/\//', $javascript, $x))
				$this->FORM[$this->id][] = "<script type=\"text/javascript\" src=\"{$javascript}\"></script>";
			else
				$this->FORM[$this->id][] = "<script type=\"text/javascript\">$(document).ready(function(){ {$javascript}});</script>";
		}
	/**
	 * CSS
	 * -------------------------
	 **/
	public function CSS($css)
		{
			$this->FORM[$this->id][] = "<style type=\"text/css\">{$css}</style>";
		}
	/**
	 * Select
	 * -------------------------
	 **/
	public function Select($name, $options, $value = false, $label = true, $null = true, $width = false, $MULTI = false, $id = false)
		{
			if ($label === true)
				$this->Label($name);
			elseif ($label)
				$this->Label($label);
			if ($value === false)
				$value = @$_POST[$name];
			if ($MULTI !== false){
				$SIZE = " size=\"".$MULTI."\" ";
				$MULTI = 'multiple';
			} else 
				$SIZE = false;
			if (is_numeric($width))
				$width = "style=\"width:{$width}px\"";
			elseif ($width){
				$CLASS = $width; 
				$width = " class=\"form-control col-xs-12\" ";
			}
			$id = empty($id)?$name:$id;
			$this->FORM[$this->id][] = "<label class=\"input {$CLASS}\">";
			$this->FORM[$this->id][] = "<select {$SIZE}{$width}name=\"$name\" id=\"$id\" $MULTI>";
			if ($null)
				$this->FORM[$this->id][] = "<option></option>";
			foreach ($options as $k => $v) {
				$check = in_array($value, [$k, $v])?$check = 'selected':$check = false;
				$this->FORM[$this->id][] = "<option $check value=\"$k\">$v</option>";
			}
			$this->FORM[$this->id][] = "</select>";
			$this->FORM[$this->id][] = "</label>";
		}
	/**
	 * Textarea
	 * -------------------------
	 **/
	public function Textarea($name, $value = false, $label = true, $width = false, $height = false)
		{
			if ($label === true) 
				$this->Label($name);
			elseif ($label)
				$this->Label($label);
			if ($value === false)
				$value = @$_POST[$name];
			if (is_numeric($width)) {
				$CLASS = false;
				if ($height !== false)
					$height = "height:{$height}px;";
				$width = "style=\"width:{$width}px ; {$height}\"";
			} elseif ($width) {
				$CLASS = $width;
				$width = " class=\"form-control col-xs-12\"";
			}
			$this->FORM[$this->id][] = "<label class=\"input {$CLASS}\">";
			$id = str_replace('[]', '', $name);
			$this->FORM[$this->id][] = "<textarea {$width} {$height} name=\"$name\" id=\"$id\" >$value</textarea>";
			$this->FORM[$this->id][] = "</label>";
		}
	/**
	 * Text
	 * -------------------------
	 **/
	public function Text($N, $V = false, $L = true, $W = false, $ToolTip = false, $mid = false)
		{
			$PH = '';
			if (is_array($V)) {$PH = key($V); $V = $V[$PH]; $PH = " placeholder=\"{$PH}\"";}
			if ($L === true) {$this->Label($N);} elseif ($L) {$this->Label($L);}
			if (!$V) 
				$V = @$_POST[$N];
			$CLASS = '';
			if (is_numeric($W))
				$W = " style=\"width:{$W}px\"";
			elseif ($W) {
				$CLASS = $W; 
				$W = " class=\"form-control col-xs-12\"";
			}
			$mid = empty($mid)?$N:$mid;
			$ID = " id=\"{$mid}\"";
			$T = " type=\"text\"";
			if (in_array($N, ['p'])) 
				$T = " type=\"password\"";
			$N = " name=\"{$N}\"";
			$V = " value=\"{$V}\"";
			$_ = " <label class=\"input {$CLASS}\">";
			$_ .= "<input".$T.$PH.$W.$N.$ID.$V."/>";
			if ($ToolTip) 
				$_ .= "<b class=\"tooltip tooltip-top-left\">".$ToolTip."</b>";
			$_ .= "</label>";
			$this->FORM[$this->id][] = $_;
			return $_;
		}
	/**
	 * Section
	 * -------------------------
	 **/
	public function Section($end = false)
		{
			$this->FORM[$this->id][] = empty($end)?"<fieldset><section>":"</section></fieldset>";
			return "<{$end}section>";
		}
	/**
	 * Hidden
	 * -------------------------
	 **/
	public function Hidden($N, $V = false)
		{
			$V = ($V)?$V:@$_POST[$N];
			$V = " value=\"{$V}\"";
			$ID = " id=\"{$N}\"";
			$T = " type=\"hidden\"";
			$N = " name=\"{$N}\"";
			$_ = "<input".$T.$N.$ID.$V."/>";
			$this->FORM[$this->id][] = $_;
			return $_;
		}
	/**
	 * Label
	 * -------------------------
	 **/
	public function Label($T, $C = false)
		{
			$_ = "<label class=\"title input col-xs-3 col-sm-2 col-md-2 col-lg-2\">".ucfirst($T).": ".$C."</label>";
			$this->FORM[$this->id][] = $_;
			return $_;
		}
	/**
	 * Button
	 * -------------------------
	 **/
	public function Button($id, $value = false, $class = false)
		{
			$V = ($value)?$value:$id;
			$C = ($class)?' class="'.$class.'"':' class="btn btn-default"';
			$ID = " id=\"{$id}\"";
			$N = " name=\"{$id}\"";
			$T = " type=\"button\"";
			$_ = "<button".$T.$N.$ID.$C.">".$V."</button>";
			$this->FORM[$this->id][] = $_;
			return $_;
		}
	/**
	 * Submit
	 * -------------------------
	 **/
	public function Submit($N, $V = false, $C = false, $e = false) 
		{
			$e = ($e)?" style=\"float:right;margin:-28px -8px;box-shadow:inset 0px 0px 5px black;padding:2px 10px;border:1px solid #19649C;\"":'';
			$V = ($V)?$V:$N;
			$V = " value=\"$V\"";
			$C = ($C)?' class="'.$C.'"':'';
			$ID = " id=\"{$N}\"";
			$N = " name=\"{$N}\"";
			$T = " type=\"submit\"";
			$_ = "<input".$T.$N.$ID.$V.$C.$e." />";
			$this->FORM[$this->id][] = $_;
			return $_;
		}
	/**
	 * Buttons
	 * -------------------------
	 **/
	public function Buttons($button) 
		{
			if (is_array($button)) {
				if (isset($button['id']))
					$id = "id=\"".$button['id']."\"";
				else 
					$id = false;
				if (isset($button['icon'])) {
					$title = X::Icon($button['icon']);
					if (isset($button['name']))
						$name = "name=\"{$button['name']}\"";
					else 
						$name = "name=\"".@$button['icon']."\"";
					if (isset($button['color'])) 
						$class = "class=\"{$button['color']}\"";
					$BUTTON = "<a $id $name>$title</a>";
				} else {
					$title = "value=\"{$button['title']}\"";
					if (isset($button['name']))
						$name   = "name=\"{$button['name']}\"";
					else 
						$name   = "name=\"".@$button['id']."\"";
					if (isset($button['color']))
						$class = "class=\"{$button['color']}\"";
					else 
						$class = false;
					$BUTTON = "<input type=\"button\" $class $title $name $id />";
				}
				$this->FORM[$this->id][]                              = $BUTTON;
			} elseif ($button !== false)
				$this->FORM[$this->id][] = $button;
		}
	/**
	 * PrintForm
	 * -------------------------
	 **/
	public function PrintForm($id = false)
		{
			$this->id = empty($id)?$this->id:$id;
			$this->FORM[$this->id][] = "</form></div>";
			$form = implode(LF, $this->FORM[$this->id]);
			return $form;
		}
	/**
	 * PrintPart
	 * -------------------------
	 **/
	public function PrintPart($id = false)
		{
			if ($id)
				$this->id = $id;
			$form = implode(LF, $this->FORM[$this->id]);
			return $form;
		}
}
?>
