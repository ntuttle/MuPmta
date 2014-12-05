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
      $this->StartAPI();
      $this->GetEmails();
      $this->SendToList();
    }
  /**                                  
   * SendToList
   * -------------------------
   **/
  public function SendToList()
    {

      foreach($this->EMAILS as $EMAIL){
          $this->GetBodyParts($EMAIL);
      }
      $xml = new SimpleXMLElement('<request/>');
      $this->array_to_xml($this->_,$xml);
      $xml->asXML(__DIR__.'/tmp.xml');
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
                                'domain'   =>$domain] ];
    }
  public function array_to_xml($info, &$xml_info) {
    foreach($info as $key => $value) {
        if(is_array($value)) {
            if(!is_numeric($key)){
                $subnode = $xml_info->addChild("$key");
                $this->array_to_xml($value, $subnode);
            }
            else{
                $subnode = $xml_info->addChild("item$key");
                $this->array_to_xml($value, $subnode);
            }
        }
        else {
            $xml_info->addChild("$key",htmlspecialchars("$value"));
        }
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

    }
}
?>