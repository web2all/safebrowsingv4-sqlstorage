<?php

class Web2All_GoogleSafeBrowsing_SQLStorageLookupTest extends GoogleSafeBrowsing_AnyStorageLookupTestAbstract
{
  /**
   * @var  Web2All_Manager_Main
   */
  private static $web2all;

  /**
   * @var  ADOConnection
   */
  private $db;

  /**
   * @var  ADOConnection
   */
  private $storage;

  /**
   * set up test environmemt
   */
  public static function setUpBeforeClass()
  {
    $config = new Web2All_GoogleSafeBrowsing_Config_UnitTest();
    self::$web2all  = new Web2All_Manager_Main($config);
  }

  /**
   * Test instantiation
   * 
   * @return Web2All_GoogleSafeBrowsing_SQLStorage_Engine
   */
  public function testStorageCreate()
  {
    // empty database/host means create temp db on disk (as of PHP 7.0) and
    // :memory: means a fully in memory temp database
    $db_config=array(
      'type'              => 'sqlite3',
      'host'              => '',
      'database'          => ':memory:',
      'debug_queries'     => false,
      'debug_override'    => true
    );
    $this->db = self::$web2all->Plugin->Web2All_ADODB_Connection->connect($db_config);
    
    // set up db (fixture)
    $db_structure=file_get_contents(dirname(__FILE__) . '/../../resources/sqllite_dump.db');
    foreach(explode(";\n",$db_structure) as $query){
      if(!$query){
        continue;
      }
      $this->db->Execute($query);
    }
    
    $this->storage = self::$web2all->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_Engine($this->db,false);
    
    $this->assertEquals($this->storage->getLists(), array(), '$storage->getLists() does not erturn empty array');
    
    return $this->storage;
  }

}
?>