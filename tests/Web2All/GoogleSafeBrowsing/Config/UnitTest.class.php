<?php

class Web2All_GoogleSafeBrowsing_Config_UnitTest extends Web2All_Manager_Config {
  
  protected $Web2All_Manager_Main = array(  
    'debuglevel' => 0,
    'debugmethod' =>'echoplain',
    'debug_add_timestamp' => true,
    'allow_error_suppression'=>true
  );
  
  protected $Web2All_Email_Main = array (
    'send_mail' => false
  );
  
  // Error email
  protected $Web2All_ErrorObserver_Email = array( 
    "codes"   =>  "0"
  );

  // Error log
  protected $Web2All_ErrorObserver_ErrorLog = array(  
    "codes"   =>  "E_ALL & ~E_STRICT"  
  );

  // Error display
  protected $Web2All_ErrorObserver_Display = array( 
    "codes"   =>  "E_ALL & ~E_STRICT",
    "mode"    =>  "VERBOSE"
  );
  
}
?>