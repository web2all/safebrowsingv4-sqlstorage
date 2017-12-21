<?php

Web2All_Manager_Main::loadClass('Web2All_Table_SaveObject');

Web2All_Manager_Main::loadClass('Web2All_Table_IListableObject');

/**
 * Web2All GoogleSafeBrowsing SQLStorage Hashes32bFull class
 *
 * This class is for storing and retrieving full 32 byte hashes in the database.
 *
 * @author Merijn van den Kroonenberg 
 * @copyright (c) Copyright 2015-2017 Web2All BV
 * @since 2015-01-06 
 */
class Web2All_GoogleSafeBrowsing_SQLStorage_Hashes32bFull extends Web2All_Table_SaveObject implements Web2All_Table_IListableObject {
  
  /**
   * The 4 bye hash prefix in hex notation 
   *
   * @var string 
   */
  public $prefix;
  
  /**
   * Full length hash in hex notation or null if no hashes for this prefix 
   *
   * @var string 
   */
  public $hash;
  
  /**
   * The list in which the hash was found (if found) 
   *
   * @var int 
   */
  public $lst_id;
  
  /**
   * Optional protocal buffer encoded meta data 
   *
   * @var string 
   */
  public $meta;
  
  /**
   * Till when is this data valid 
   *
   * @var string 
   */
  public $expire;
  
  
  /**
   * constructor
   *
   * @param Web2All_Manager_Main $web2all
   * @param ADOConnection $db
   */
  public function __construct(Web2All_Manager_Main $web2all,$db) {
    parent::__construct($web2all,$db);
    
    $this->tablename='hashes_32b_full';
    
    $this->obj_to_db_trans=array(
      'prefix' => 'hs32f_prefix',
      'hash' => 'hs32f_hash',
      'lst_id' => 'hs32f_lst_id',
      'meta' => 'hs32f_meta',
      'expire' => 'hs32f_expire',
 
    );
    
    $this->key_properties=array();
    
  }
  
  /**
   * Load this object from the database by its (primary) key
   *
   * @return boolean
   */
  public function loadFromDB()
  {
    return $this->loadFromTable(array());
  }
  
  /**
   * Check if this object has been successfully loaded from the
   * database. (we assume this is, when all key properties are set)
   *
   * @return boolean
   */
  public function isValid()
  {
    throw new Exception('Table has no primary index, cannot use isValid() !');
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
  
  /**
   * Completely truncate the table, all content will be removed
   * 
   */
  public function clearTable()
  {
    if($this->getDB()->databaseType=='sqlite3'){
      $this->getDB()->Execute('DELETE FROM `'.$this->tablename.'`;');
      $this->getDB()->Execute('VACUUM;');
    }else{
      $this->getDB()->Execute('TRUNCATE TABLE `'.$this->tablename.'`;');
    }
  }
}

?>