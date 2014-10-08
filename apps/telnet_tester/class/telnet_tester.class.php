<?php

class TelNetTest {

	var $IP;
	var $EHLO;
	var $MAILFROM;
	var $TO;
	var $FROM;
	var $SUBJECT;
	var $HEADERS;
	var $CONTENTTYPE;
	var $ENCODING;
	var $BODY;

	var $Debug = true;

	public function __construct($ARGS) {
		if (!defined(EOL)) {
			define(EOL, "\r\n");
		}
		$IP = is_numeric($ARGS['ip'])?$IP = long2ip($ARGS['ip']):$ARGS['ip'];
		if (preg_match('/([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})/', $IP, $x)) {
			$IP = $x[1];
		} else {
			exit(FAIL.'Invalid IP! '.$IP);
		}
		if (empty($ARGS['ehlo'])) {
			$ARGS['ehlo'] = gethostbyaddr($IP);
			if (($ARGS['ehlo'] == $IP) || ($ARGS['ehlo'] === false)) {
				exit(FAIL.'No rDNS found for '.$IP);
			}
		}
		$this->IP   = $IP;
		$this->EHLO = $ARGS['ehlo'];
		$this->MailFrom(@$ARGS['mailfrom']);
		$this->To(@$ARGS['to']);
		$this->From(@$ARGS['from']);
		$this->Subect(@$ARGS['subject']);
		$this->MoreHeaders($ARGS['headers']);
		$this->Body(@$ARGS['body']);
	}

	public function SendMail() {
		echo "<h1>Sending Test</h1>";
		if ($this->Connect()) {
			if ($this->SayHi()) {
				if ($this->MFrom()) {
					if ($this->RcptTo()) {
						if ($this->DATA()) {
							$this->Write(green.'Email has been sent! :)'.EOL);
						}else{
							$this->Write(red.'Email failed to send! :('.EOL);
						}
					}
				}
			}
		}
	}
	public function DATA() {
		$this->Exec('DATA', $OUT);

		foreach($this->HEADERS as $H=>$V){
			$EMAIL[] = $H.': '.$V;
		}
		$EMAIL[] = EOL;
		$EMAIL[] = $this->BODY;
		$EMAIL[] = EOL;
		$EMAIL[] = '.';
		$EMAIL = implode(EOL,$EMAIL);
		$this->Exec($EMAIL, $OUT);
		if (!strstr($OUT, '250')) {
			$this->Write(FAIL.'Bad Data!'.EOL);
			return false;
		} else {
			$this->Write(PASS.EOL);
			return true;
		}
	}
	public function RcptTo() {
		$this->Exec('RCPT TO: '.$this->TO, $OUT);
		if (!strstr($OUT, '250')) {
			$this->Write(FAIL.'Bad RCPT TO '.EOL);
			$this->SendMail();
		} else {
			$this->Write(PASS.EOL);
			return true;
		}
	}
	public function MFrom() {
		$this->Exec('MAIL FROM: '.$this->MAILFROM, $OUT);
		if (!strstr($OUT, '250')) {
			$this->Write(FAIL.'Bad MAIL FROM'.EOL);
			$this->SendMail();
		} else {
			$this->Write(PASS.EOL);
			return true;
		}
	}
	public function SayHi() {
		$this->Exec('EHLO '.$this->EHLO, $OUT);
		if (!strstr($OUT, '250')) {
			$this->Write(FAIL.'Bad EHLO'.EOL);
			exit();
			$this->SendMail();
		} else {
			$this->Write(PASS.EOL);
			return true;
		}
	}
	public function Connect() {
		$this->Write(aqua.'CONNECT FROM '.$this->IP.EOL.white);
		$socket_context = stream_context_create(array('socket' => array('bindto' => $this->IP.':0')));
		$this->SOCK     = stream_socket_client($this->MX.':25', $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $socket_context);
		if ($this->SOCK !== false) {
			$OUT = fread($this->SOCK, 1024);
			$this->Write($OUT);
			if (!strstr($OUT, '220')) {
				$this->Write(FAIL.'Bad Connection'.EOL);
				return $this->Connect();
			} else {
				$this->Write(PASS.EOL);
				return true;
			}
		}
		exit('Failed to create stream...');
	}
	public function Write($DATA) {
		if ($this->Debug) {
			print_r($DATA);
		}
	}
	public function Exec($CMD, &$OUT) {
		$this->Write(aqua.htmlspecialchars($CMD).white.EOL);
		fputs($this->SOCK, $CMD.EOL);
		$OUT = fread($this->SOCK, 1024);
		$this->Write($OUT);
	}
	public function MoreHeaders($HEADERS) {
		if (!empty($HEADERS)) {
			$HEADERS = stristr($HEADERS, "\n")?explode("\n", $HEADERS):[$HEADERS];
			foreach ($HEADERS as $HEADER) {
				if (preg_match('/^([a-zA-Z0-9\-]+):[ ]{0,}(.*)$/', $HEADER, $x)) {
					$this->HEADERS[$x[1]] = $x[2];
					$last                 = $x[1];
				} else {
					if (isset($last)) {
						$this->HEADERS[$last] .= $HEADER;
					}
				}
			}

		}
	}
	public function To($TO) {
		if (is_array($TO)) {
			$TO = "\"".key($TO)."\" <".$TO[key($TO)].">";
		}
		if (empty($TO) || !is_string($TO)) {
			exit(FAIL.'Invalid To: header ');
		}
		$this->TO            = "<".$TO.">";
		list($User, $TARGET) = explode('@', $TO, 2);
		getmxrr($TARGET, $MX);
		if (!empty($MX)) {
			$MXHOST   = $MX[array_rand($MX)];
			$this->MX = gethostbyname($MXHOST);
		}
		$this->HEADERS['to'] = "<".$TO.">";
	}
	public function From($FROM) {
		if (is_array($FROM)) {
			$FROM = "\"".key($FROM)."\" <".$FROM[key($FROM)].">";
		}
		if (empty($FROM) || !is_string($FROM)) {
			exit(FAIL.'Invalid From: header ');
		}
		$this->FROM            = "<".$FROM.">";
		$this->HEADERS['from'] = "<".$FROM.">";
	}
	public function MailFrom($FROM) {
		if (is_array($FROM)) {
			$FROM = "\"".key($FROM)."\" <".$FROM[key($FROM)].">";
		}
		if (empty($FROM) || !is_string($FROM)) {
			exit(FAIL.'Invalid MailFrom ');
		}
		$this->MAILFROM        = "<".$FROM.">";
		$this->HEADERS['from'] = "<".$FROM.">";
	}
	public function Subect($SUBJECT) {
		if (empty($SUBJECT) || !is_string($SUBJECT)) {
			exit(FAIL.'Invalid Subject ');
		}
		$this->SUBJECT            = $SUBJECT;
		$this->HEADERS['Subject'] = $SUBJECT;
	}
	public function Body($BODY) {
		if (empty($BODY) || !is_string($BODY)) {
			exit(FAIL.'Invalid BODY ');
		}
		$this->BODY = $BODY;
		if (isset($this->HEADERS['MIME-Version'])) {
			unset($this->HEADERS['MIME-Version']);
		}
		$this->HEADERS['MIME-Version'] = '1.0';
		if (!isset($this->HEADERS['content-type'])) {
			$this->CONTENTTYPE = 'text/html';
		} else {
			$this->CONTENTTYPE = $this->HEADERS['content-type'];
			unset($this->HEADERS['content-Type']);
		}
		$this->HEADERS['Content-type'] = $this->CONTENTTYPE;
		if (!isset($this->HEADERS['content-transfer-encoding'])) {
			$this->ENCODING = '8bit';
		} else {
			$this->ENCODING = $this->HEADERS['content-transfer-encoding'];
			unset($this->HEADERS['content-transfer-encoding']);
		}
		$this->HEADERS['Content-transfer-encoding'] = $this->ENCODING;
	}

}
?>