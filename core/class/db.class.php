<?php
class DBC {
	var $H; // HOST
	var $D; // DATABASE
	var $T; // TABLE
	var $N; // CURRENT HOST
	var $S   = [];  // DB STREAMS
	var $Q   = [];  // SUCCESSFULL QUERIES
	var $E   = [];  // ERRORS
	var $R   = [];  // CURRENT RESULT
	var $nR  = 0;		// NUMBER OF ROW
	var $aR  = 0;		// AFFECTED ROWS
	var $rID = 0;		// ROW ID
	var $lID = 0;		// LAST ID
	var $Key = ['id'=>false]; // Result Row Identifier 'name'=> true|false
	public function __construct($N, $H = 'localhost', $U = 'root', $P = false, $p = true)
		{
			if (!$P) 
				$P = PASS;
			$this->C($N, $H, $U, $P, $p);
			$this->N = $N;
		}
	/**
	 * C
	 * -------------------------
	 **/
	public function C($N, $H, $U, $P, $p=false)
		{
			if($p)
				$S = mysql_pconnect($H, $U, $P, MYSQL_CLIENT_COMPRESS) or die('No Database Connection! '.$H.'|'.$U.'|'.$P.'|'.mysql_error());
			else
				$S = mysql_connect($H, $U, $P, false, MYSQL_CLIENT_COMPRESS) or die('No Database Connection! '.$H.'|'.$U.'|'.$P.'|'.mysql_error());
			if ($S) 
				$this->S[$N] = $S;
			else 
				$this->E("Could Not Connect to {$H}");
		}
	/**
	 * E
	 * -------------------------
	 **/
	public function E($M = false)
		{
			$S         = $this->S[$this->H];
			$this->R   = false;
			$this->E[] = "Database Error: (".$M.") - ".mysql_error($S);
		}
	/**
	 * W
	 * -------------------------
	 **/
	public function W($vA = false, $M = false)
		{
			if ($M == 'S') {
				if (is_array($vA)) {
					foreach ($vA as $k => $v) {$v = $this->xQ($v); $a[] = "`$k`=\"{$v}\"";}
					$vA                           = implode(',', $a);
				}
			} elseif ($M == 'V') {
				foreach ($vA as $k => $v)
				if (!is_array($v)) {
					$a[] = "'".(string) $this->xQ($v)."'";
				} else {
					$a[] = "(".$this->W($v, $M).")";
				}
				$vA = implode(',', $a);
			} elseif ($M == 'F' && is_array($vA)) {
				foreach ($vA as $k => $v) {
					$t                         = $v;
					if (!is_numeric($k)) {$t   = $k;
						if (stristr($k, '(')) {$v = $t." AS `".$this->xQ($v)."`";} else { $v = "`".$t."` AS `".$this->xQ($v)."`";}} elseif (str_ireplace(['`', '(', ')', '.'], '', $v) !== $v) {$v = $this->xQ($v);} else { $v = "`".$this->xQ($v)."`";}
					$a[]                       = $v;
				}
				$vA = implode(',', $a);
			} elseif ($M == 'P' && is_array($vA)) {
				foreach ($vA as $k => $v) {
					$t                       = $v;
					if (!is_numeric($k)) {$t = $k;}
					$a[]                     = "`".$this->xQ($t)."`";
				}
				$vA = "(".implode(',', $a).")";
			} elseif ($M == 'G') {
				if (is_array($vA)) {
					foreach ($vA as $k => $v) {
						$v = $this->xQ($v);
						unset($eq);
						if (stristr($k, '__')) {list($k, $eq) = explode('__', $k, 2);}
						$eq                                   = emptY($eq)?' = ':str_pad($eq, (strlen($eq)+2), ' ', STR_PAD_BOTH);
						if (stristr($eq, 'IN')) {
							if (!is_array($v)) {$v = [$v];}
							foreach ($v as &$val)
							if (!is_numeric($val) && (str_ireplace(['`', '(', ')'], '', $val) == $val)) {$val = "'".$val."'";}
							$v                                                                                = '('.implode(', ', $v).')';
						} elseif (str_ireplace(['>=', '>', '<', '<=', '!='], '', $eq) != $eq) {
							if (!is_numeric($v)) {$v = "'".$v."'";}
						} elseif (stristr($eq, 'BETWEEN')) {
							$_beg = is_numeric(key($v))?key($v):"'".key($v)."'";
							$_end = is_numeric($v[$_beg])?$v[$_beg]:"'".$v[$_beg]."'";
							$v    = $_beg." AND ".$_end;
						} elseif (stristr($k, 'ORDER')) {
							$eq = ' BY ';
							if (!is_array($v)) {
								$v = [$v => 'ASC'];
							}
							foreach ($v as $f => $ord) {
								if (stristr($f, '`') || stristr($f, '(')) {
									$F[] = $f;
								} else {
									$F[] = "`".$f."` ".$ord;
								}
							}
							$v = implode(', ', $F);
						} elseif (strstr($k, 'GROUP')) {
							$eq                         = ' BY ';
							if (!is_array($v)) {$v      = [$v];}
							foreach ($v as &$val) {$val = "`".$val."`";}
							$v                          = implode(', ', $v);
						} elseif (stristr($k, 'JOIN')) {
							$eq   = ' ON ';
							$DB   = $this->D;
							$TBL1 = $this->T;
							$TBL2 = key($v);
							$KEYS = $v[$TBL2];
							$K1   = key($KEYS);
							$K2   = $KEYS[$K1];
							$k    = ' INNER JOIN `'.$DB.'`.`'.$TBL2.'`';
							$v    = '`'.$TBL1.'`.`'.$K1.'`=`'.$TBL2.'`.`'.$K2.'`';

							$this->JOIN = $k.$eq.$v;
						} elseif (stristr($k, 'LIKE')) {
							if (!stristr($v, '%')) {
								$v = '%'.$v.'%';
							}
						} elseif (!is_numeric($v)) {
							$v = "'".$v."'";
						}
						if (str_ireplace(['`', '(', ')', 'ORDER', 'GROUP', 'LIKE'], '', $k) == $k) {
							$k = "`".$k."`";
						}
						if (!stristr($k, 'INNER JOIN')) {
							$a[] = $k.$eq.$v;
						}
					}
					$vA = implode(' AND ', $a);
					$vA = str_ireplace('AND GROUP', 'GROUP', $vA);
					$vA = str_ireplace('AND ORDER', 'ORDER', $vA);
				}
			} elseif ($M == 'W') {
				if (is_array($vA)) {
					foreach ($vA as $k => $v) {
						if (is_array($v)) {
							if (is_numeric($v)) {
								foreach ($v as &$_v) {
									$_v = $this->xQ($_v);
								}
							} else {
								foreach ($v as &$_v) {
									$_v = "'".$this->xQ($_v)."'";
								}
							}
							$_V  = implode(',', $v);
							$a[] = "`{$k}` IN ({$_V})";
						} else {
							$v   = $this->xQ($v);
							$a[] = "`{$k}`='{$v}'";
						}
					}
					$vA = implode(' AND ', $a);
				}}
			return $vA;
		}
	/**
	 * xQ
	 * -------------------------
	 **/
	public function xQ($vS) 
		{
			if (is_string($vS)) 
				if (!stristr($vS, '`') && !stristr($vS, '(')) 
					$vS = is_string($vS)?mysql_real_escape_string($vS):$vS;
			return $vS;
		}
	/**
	 * SET
	 * -------------------------
	 **/
	public function SET($C, $F, $Q = false, $L = 1) 
		{
			$F = $this->W($F, 'S');
			$Q = $this->W($Q, 'W');

			if ($L) 
				$L = "LIMIT $L";
			if ($this->pC($C)) {
				$D = $this->D;
				$T = $this->T;
				$H = $this->H;
				$S = $this->S[$H];
				$this->lID   = 0;
				$this->aR    = 0;
				if (!$Q) 
					$Q = 1;
				$Q = "UPDATE `{$D}`.`{$T}` SET {$F} WHERE {$Q} {$L}";
				$this->R = false;
				if (mysql_query($Q, $S)) {
					$this->Q[] = $Q;
					$this->aR  = mysql_affected_rows($S);
					$this->lID = mysql_insert_id($S);
				} else 
				  $this->E($Q);
			} else 
			  echo "PARSE FAIL!";
		}
	/**
	 * PUT
	 * -------------------------
	 **/
	public function PUT($C, $F, $V, $e = false) 
		{
			$u = false;
			if (stristr($e, 'update')) {
				$u = " ON DUPLICATE KEY ".$e;
				$e = false;
			}
			$this->R = false;
			$F       = $this->W($F, 'P');
			$V       = $this->W($V, 'V');
			if ($this->pC($C)) {
				$D = $this->D;
				$T = $this->T;
				$H = $this->H;
				$S = $this->S[$H];
				$this->lID = 0;
				$this->aR  = 0;
				$Q = "INSERT {$e} INTO `{$D}`.`{$T}` {$F} VALUES {$V} {$u}";
				if (mysql_query($Q, $S)) {
					$this->Q[] = $Q;
					$this->aR  = mysql_affected_rows($S);
					$this->lID = mysql_insert_id($S);
				} else 
				  $this->E($Q);
			}
		}
	/**
	 * DEL
	 * -------------------------
	 **/
	public function DEL($C, $Q, $L = false)
		{
			if ($this->pC($C) !== false) {
				$D = $this->D;
				$T = $this->T;
				$H = $this->H;
				$S = $this->S[$H];
				$this->lID   = 0;
				$this->aR    = 0;
				if (!$L) 
					$L = 1;
				$L = "LIMIT {$L}";
				$Q           = $this->W($Q, 'W');
				$Q           = "DELETE FROM `{$D}`.`{$T}` WHERE {$Q} {$L}";
				if ($R = mysql_query($Q, $S)) {
					$this->Q[] = $Q;
					$R         = mysql_affected_rows($S);
					$this->aR  = $R;
					$this->R   = $R;
				} else 
				  $this->E($Q);
			}
		}
	/**
	 * Q
	 * -------------------------
	 **/
	public function Q($C, $Q)
		{
			if ($this->pC($C) !== false) {
				$D = $this->D;
				$T = $this->T;
				$H = $this->H;
				$S = $this->S[$H];
				$this->lID = 0;
				$this->aR  = 0;
				if ($X = mysql_query($Q, $S)) {
					$this->Q[] = $Q;
					$i         = 0;
					while ($r = @mysql_fetch_assoc($X)) {
						$ID                                   = $i;
						if (isset($r['id'])) {$ID             = $r['id'];unset($r['id']);}
						foreach ($r as $k => $v) {$R[$ID][$k] = $v;}$i++;
					}
					$this->lID      = mysql_insert_id($S);
					$this->aR       = mysql_affected_rows($S);
					$this->nR       = count($R);
					return $this->R = $R;
				} else
				  $this->E($Q);
			}
		}
	/**
	 * GET
	 * -------------------------
	 **/
	public function GET($C, $Q = false, $F = "*", $L = 1000)
		{
			$R = false;
			$this->pC($C);
			$D = $this->D;
			$T = $this->T;
			$H = $this->H;
			$S = $this->S[$H];
			$this->lID  = 0;
			$this->aR   = 0;
			$F = $this->W($F, 'F');
			if ($L) 
				$L = "LIMIT $L";

			if ($Q) {
				if (is_array($Q))
					$Q = $this->W($Q, 'G');
				$Q = "WHERE {$Q}";
			}
			if (!empty($this->JOIN)) {
				$J = $this->JOIN;
				unset($this->JOIN);
			} else 
				$J = false;
			$id = key($this->Key);
			$Q  = "SELECT {$F} FROM `{$D}`.`{$T}`{$J} {$Q} {$L}";
			if ($X = mysql_query($Q, $S)) {
				$i = 0;
				$this->Q[] = $Q;
				while ($r = mysql_fetch_assoc($X)) {
					$ID = $i;
					if (isset($r[$id])) {
						$ID = $r[$id];
						if ($this->Key[$id] === false)
							unset($r[$id]);
					}
					foreach ($r as $k => $v) 
						$R[$ID][$k] = $v;
					$i++;
				}
				$this->Key         = ['id' => false];
				if (empty($R)) 
					$R = false;
				else {
					$this->nR         = count($R);
					if ($this->nR == 1) {
						$ID      = key($R);
						$R       = $R[$ID];
						$R['id'] = $ID;
					}
					$this->R = $R;
				}
			} else
			  $this->E($Q);
			return $R;
		}
	/**
	 * pC
	 * -------------------------
	 **/
	public function pC($C) 
		{
			$HDBT = '/^([a-zA-Z0-9_]+)[\.]{1}([a-zA-Z0-9_]+)[\.]{1}([a-zA-Z0-9_\.]+)$/';
			$DBT  = '/^([a-zA-Z0-9_]{3,})[\.]{1}([a-zA-Z0-9_]{3,})$/';
			$DB   = '/^([a-zA-Z0-9_]{3,})$/';
			$H    = @$this->N;
			$D    = @$this->D;
			$T    = @$this->T;
			if (preg_match($HDBT, $C, $x)) {
				$H = $x[1];
				$D = $x[2];
				$T = $x[3];
			} elseif (preg_match($DBT, $C, $x)) {
				$D = $x[1];
				$T = $x[2];
			} elseif (preg_match($DB, $C, $x))
				$T = $x[1];
			else {
				$this->E('Malformed Request');
				return false;
			}
			$this->H = $H;
			$this->D = $D;
			$this->T = $T;
			return true;
		}
}
?>