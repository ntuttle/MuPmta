<? 
# Configuration Settings
#---------------------------------------------------
  $SVR = 'sd2'; // force a hostname if running locally
  $_ = stristr(gethostname(),'sd')?[]:['host'=>$SVR];
  $_['debug']   =  true;
  $_['user']    = 'root';
  $_['pass']    = 'ca1ad6dbfd8612f3ca5cff38e4a69837';
  $_['domain']  = 'mu-portal.com';
  $_['wwwPort'] =  2186;
  $_['mtaPort'] =  8080;
# Php.ini - more in /core/conf/ini.php
#---------------------------------------------------
  $I['display_errors']  = 1;
  $I['date.timezone']   = 'America/Los_Angeles';
# Database Connections
#---------------------------------------------------
  $H['LOGS']    = [
      # IncomingEmail,PMTAlogs,metadata
      'public'  =>'207.158.26.6',
      'private' =>'192.168.15.130',
      'port'    => 3307
      ];
  $H['MUP']     = [
      # Offers,Jobs,Presets,Domains,IPs
      'public'  =>'207.158.26.15',
      'private' =>'192.168.15.200',
      'port'    => 3306
      ];
  $H['REDIRECT']= [
      # Job Images & Links,User Actions
      'public'  =>'207.158.26.14',
      'private' =>'192.168.15.150',
      'port'    => 3306
      ];
  $H['EMAILS']  = [
      # Emails&Lists,EmailDetails,Seeds
      'public'  =>'207.158.26.21',
      'private' => false,
      'port'    => 3306
      ];
# DO NOT EDIT BELOW HERE
#---------------------------------------------------
  $_['hosts'] = $H;
  $_['ini']   = $I;
  Req('conf.php',CONF);
?>