<?
class adknowledge_injector {
  var $DB;
  var $Debug = false;
  public function __construct($DB,$ARGS=false)
    {
      $this->DB   = $DB;
      $this->SetVariables();
    }
  /**
   * SetVariables
   * -------------------------
   **/
  public function SetVariables()       
    {
      $this->GetEmails();
      $this->SendToList();
      $this->GetIPDetails();
      $this->StartAPI();
    }
  /**
   * GetIPDetails
   * -------------------------
   **/
  public function GetIPDetails()
    {
      $HostID = hostID;
      $Q = $this->DB->GET('MUP.ipconfig.global_config',['pmta'=>$HostID,'active'=>1]);
      $IP = $Q[array_rand($Q)];
      $RDNS = $IP['rdns'];
      $Q = $this->DB->GET('MUP.ipconfig.target_config',['ip'=>$IP['ip'],'active'=>1]);
      if(empty($Q)){
        echo 'No Active IPs on this Server';
        return false;
      }
    }
  /**                                  
   * SendToList
   * -------------------------
   **/
  public function SendToList()
    {

      foreach($this->EMAILS as $EMAIL)
        $this->GetBodyParts($EMAIL);
      $xml = new SimpleXMLElement('<request/>');
      $this->array_to_xml($this->_,$xml);
      $xml->asXML(__DIR__.'/tmp.xml');
      $XML = file_get_contents( __DIR__.'/tmp.xml');
      $this->REQUEST = urlencode( $XML);
    }
  /**                                  
   * GetBodyParts
   * -------------------------
   **/
  public function GetBodyParts($EMAIL)
    {
      list($user,$domain) = explode('@',$EMAIL);
      $this->_[] = ['email' => ['recipient'=>md5($EMAIL),
                                'list'     =>'1',
                                'domain'   =>$domain,
                                'test'     =>'1'] ];
    }
  /**                                  
   * array_to_xml
   * -------------------------
   **/
  public function array_to_xml($info, &$xml_info)
    {
      foreach($info as $key => $value) {
        if(is_array($value))
          if(!is_numeric($key)){
            $subnode = $xml_info->addChild("$key");
            $this->array_to_xml($value, $subnode);
          }else{
            $subnode = $xml_info->addChild("item$key");
            $this->array_to_xml($value, $subnode);
          }
        else
          $xml_info->addChild("$key",htmlspecialchars("$value"));
      }
    }

  /**                                  
   * GetEmails
   * -------------------------
   **/
  public function GetEmails()
    {
      $this->EMAILS[] = 'a_hammar@aol.com';
      $this->EMAILS[] = 'nick@mediauniversal.com';
    }
  public function StartAPI()
    {
      // MAKE API CALL HERE TO ADKNOWLEDGE WITH COMPILED PARTS
    }
}
?>