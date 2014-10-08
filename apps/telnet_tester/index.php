<?php
  define('output','html');
  require_once __DIR__.'/../../core/core.php';
  Req('class/telnet_tester.class.php',__DIR__);
  echo www::ScriptHead('Telnet Tester');

if (!isset($_POST['ip'])) {
	$F = new FORMS('TelNetTest', 'Telnet Email Testing', false, 500);
	$F->Text('ip',['IP Address'=>false],false);
	$F->Text('ehlo',['EHLO'=>false],false);
	$F->Text('mailfrom',['Mail From'=>false],false);
	$F->Text('to',['To'=>false],false);
	$F->Text('from',['From'=>false],false);
	$F->Text('subject',['Subject Line'=>false],false);
  $F->write('<b style="padding:25px 0px 0px;">Additional Headers</b>');
	$F->Textarea('headers', false, false, 460, 100);
  $F->write('<b style="padding:25px 0px 0px;">Body</b>');
	$F->Textarea('body', false, false, 460, 250);
	$F->Button('TelNetTest', 'send test');
	$F->JS("$('button#TelNetTest').click(function(){
    var ip = $('#ip').val();
    var ehlo = $('#ehlo').val();
    var from = $('#from').val();
    var to = $('#to').val();
    var mailfrom = $('#mailfrom').val();
    var headers = $('#headers').val();
    var subject = $('#subject').val();
    var body = $('#body').val();
    $('td#results').html('<h3>Working... Please Wait...</h3>');
    $.post(window.location.href,{ip:ip,ehlo:ehlo,from:from,to:to,mailfrom:mailfrom,headers:headers,body:body,subject:subject},function(data){
      $('td#results').html(data);
    });
  });");
	$F = $F->PrintForm();
  echo www::Alt("
    <table>
      <tr>
        <td>".$F."</td>
        <td id=\"results\"></td>
	    </tr>
    </table>");
} else {
	$TNT = new TelNetTest($_POST);
	$TNT->SendMail();

}
?>