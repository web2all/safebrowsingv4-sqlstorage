<?php

Web2All_Manager_Main::loadClass('Web2All_Table_SaveObject');

Web2All_Manager_Main::loadClass('Web2All_Table_IListableObject');

/**
 * Web2All GoogleSafeBrowsing SQLStorage UpdaterState class
 *
 * This class is for setting and retrieving updater state fields.
 *
 * @author Merijn van den Kroonenberg 
 * @copyright (c) Copyright 2014 Web2All BV
 * @since 2014-12-24 
 */
class Web2All_GoogleSafeBrowsing_SQLStorage_UpdaterState extends Web2All_Table_SaveObject implements Web2All_Table_IListableObject {
  
  /**
   * The field name 
   *
   * @var string 
   */
  public $field;
  
  /**
   * the fields value 
   *
   * @var int 
   */
  public $value;
  
  
  /**
   * constructor
   *
   * @param Web2All_Manager_Main $web2all
   * @param ADOConnection $db
   */
  public function __construct(Web2All_Manager_Main $web2all,$db) {
    parent::__construct($web2all,$db);
    
    $this->tablename='updater_state';
    
    $this->obj_to_db_trans=array(
      'field' => 'upst_field',
      'value' => 'upst_value',
 
    );
    
    $this->key_properties=array('field');
    
  }
  
  /**
   * Load this object from the database by its (primary) key
   *
   * @return boolean
   */
  public function loadFromDB($field)
  {
    return $this->loadFromTable(array('upst_field' => $field));
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