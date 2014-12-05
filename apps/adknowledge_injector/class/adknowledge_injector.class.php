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
      array_walk_recursive($this->_, array ($xml, 'addChild'));
      echo "<pre>";
      print htmlspecialchars( $xml->asXML());
      echo "</pre>";
    }
  /**                                  
   * GetBodyParts
   * -------------------------
   **/
  public function GetBodyParts($EMAIL)
    {
      list($user,$domain) = explode('@',$EMAIL);
      $this->_[] = ['email' => [md5($EMAIL)=> 'recipient',
                        '1'        => 'list',
                        $domain    => 'domain']];
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