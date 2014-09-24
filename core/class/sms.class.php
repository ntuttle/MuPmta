<?
class SMS {

  var $SID;
  var $TKN;
  var $NUM;
  var $DB;
  var $CON;

  public function __construct()
   {
    $this->SID = 'ACcc2a096a4a2b442d99b102f3154ca044';
    $this->TKN = '0b97c1f21b8c93f6fb672ab63d491112';
    $this->NUM = '6193464457';
    $this->DBC();
    $this->CON();
   }
  /**
   * Database Connection
   * Used for accessing stored user phone numbers
   * -------------------------
   */
  public function DBC()
    {
      require_once DIR.'core/dbc.php';
      $DB = new DBC('MUP',MUP,DBUSER,DBPASS);
      if(empty($DB))
        $this->Error('Database Connection Fail!');
      $this->DB = $DB;
    }
  /**
   * Twilio API Connection
   * -------------------------
   */
  public function CON()
    {
      require_once DIR."core/twilio/Services/Twilio.php";
      $CON = new Services_Twilio($this->SID, $this->TKN);
      if(empty($CON))
        $this->Error('Twilio Connection Fail!');
      $this->CON = $CON;
    }
  /**
   * Send SMS Message
   * -------------------------
   * @param mixed $USR username or user id
   * @param string $MSG message to send
   * -------------------------
   */
  public function Send($MSG,$USR)
    {
      if(preg_match('/([0-9]{10})/',$this->ScrubNum($USR),$x))
        $NUM = $x[1];
      else
        {
          $W = ['username'=>$USR];
          // Check for matching username
          $Q = $this->DB->GET('MUP.team.users',$W,['phone'],1);
          if(empty($Q))
            {
              $W = ['id'=>$USR];
              // check for matching user ID
              $Q = $this->DB->GET('MUP.team.users',$W,['phone'],1);
              if(empty($Q))
              $this->Error('Unknown User');
            }
          $NUM = $this->ScrubNum($Q['phone']);
        }
      if(preg_match('/([0-9]{10})/',$NUM,$x))
        {
          $this->CON->account->sms_messages->create($this->NUM,$x[1],$MSG);
          return "SMS Sent\n";
        }
      else
        return "Invalid Phone Number! {$NUM}\n";
    }
  /**
   * Clean Phone Number
   * -------------------------
   * Remove non-numeric characters
   * @param mixed $NUM phone number to clean
   * -------------------------
   */
  public function ScrubNum($NUM)
    {
      $NUM = trim(str_ireplace(array("-",".","(",")","+"," "),"",@$NUM));
      return $NUM;
    }
  /**
   * Exit on error
   * @param string $MSG error message
   */
  public function Error($MSG)
    {
      $MSG = "<span style=\"color:red\">ERROR! </span>".$MSG;
      exit($MSG);
    }
}
?>