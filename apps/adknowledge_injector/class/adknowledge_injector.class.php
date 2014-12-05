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
    }
  /**                                  
   * GetBodyParts
   * -------------------------
   **/
  public function GetBodyParts($EMAIL)
    {
      echo "<pre>";
      echo "\n\n\n"
      list($user,$domain) = explode('@',$EMAIL);
      $_ = ['email' => [md5($EMAIL)=> 'recipient',
                       '1'       => 'list',
                       $domains  => 'domain']];
      $xml = new SimpleXMLElement('<request/>');
      array_walk_recursive($_, array ($xml, 'addChild'));
      print htmlspecialchars( $xml->asXML());
      echo "\n\n\n"
      echo "</pre>";
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