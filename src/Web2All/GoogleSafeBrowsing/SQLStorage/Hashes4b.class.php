<?php

Web2All_Manager_Main::loadClass('Web2All_Table_SaveObject');

Web2All_Manager_Main::loadClass('Web2All_Table_IListableObject');

/**
 * Web2All GoogleSafeBrowsing SQLStorage Hashes4b class
 *
 * This class is for storing and retrieving 4 byte hash prefixes in the database.
 *
 * @author Merijn van den Kroonenberg 
 * @copyright (c) Copyright 2014 Web2All BV
 * @since 2014-11-20 
 */
class Web2All_GoogleSafeBrowsing_SQLStorage_Hashes4b extends Web2All_Table_SaveObject implements Web2All_Table_IListableObject {
  
  /**
   * Hash prefix, hexadecimal notation of the first 4 bytes 
   *
   * @var string 
   */
  public $prefix;
  
  /**
   * Foreign key to list table 
   *
   * @var int 
   */
  public $lst_id;
  
  /**
   * For 4b hashes value is 0. If nonzero defines sort order and lookup in hashes_xb is needed.
   *
   * @var int 
   */
  public $long_prefix;
  
  
  /**
   * constructor
   *
   * @param Web2All_Manager_Main $web2all
   * @param ADOConnection $db
   */
  public function __construct(Web2All_Manager_Main $web2all,$db) {
    parent::__construct($web2all,$db);
    
    $this->tablename='hashes_4b';
    
    $this->obj_to_db_trans=array(
      'prefix' => 'hs4b_prefix',
      'lst_id' => 'hs4b_lst_id',
      'long_prefix' => 'hs4b_long_prefix',
 
    );
    
    $this->key_properties=array('prefix','lst_id','long_prefix');
    
  }
  
  /**
   * Load this object from the database by its (primary) key
   *
   * @param string $prefix  8 char hex notation of 4 byte prefix
   * @param int $lst_id  list id (foreighn key)
   * @param int $long_prefix 
   * @return boolean
   */
  public function loadFromDB($prefix,$lst_id,$long_prefix)
  {
    return $this->loadFromTable(array('hs4b_prefix' => $prefix, 'hs4b_lst_id' => $lst_id, 'hs4b_long_prefix' => $long_prefix));
  }
  
  /**
   * Check if this object has been successfully loaded from the
   * database. (we assume this is, when all key properties are set)
   *
   * @return boolean
   */
  public function isValid()
  {
    return parent::isValid();
  }
  

  /**
   * Initializes the Item object (properties) based on a assoc array with as keys the
   * database fields.
   *
   * @param array $db_fields
   */
  public function loadFromDBArray($db_fields)
  {
    parent::loadFromDBArray($db_fields);
  }
  
  /**
   * Method to query the table, based on the current values of the
   * objects properties.
   * 
   * This method is required public when implementing
   * the Web2All_Table_IListableObject interface.
   *
   * @param string $extra  extra sql to append to query (eg order by)
   * @param integer $limit  max amount of results (or -1 for no limit)
   * @param integer $offset  start from position (or -1 if from start)
   * @return ADORecordSet
   */
  public function getRecordsetFromObjectQuery($extra='',$limit=-1,$offset=-1)
  {
    return parent::getRecordsetFromObjectQuery($extra,$limit,$offset);
  }
  
  /**
   * get the adodb handle used by this object
   *
   * @return ADOConnection
   */
  public function getDB()
  {
    return parent::getDB();
  }
}

?>