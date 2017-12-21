<?php

Web2All_Manager_Main::loadClass('GoogleSafeBrowsing_Updater_IStorage','PEAR');
Web2All_Manager_Main::loadClass('GoogleSafeBrowsing_Lookup_IStorage','PEAR');

// hex2bin only exists from PHP 5.4 onwards
if ( !function_exists( 'hex2bin' ) ) {
  function hex2bin( $str ) {
    $sbin = "";
    $len = strlen( $str );
    for ( $i = 0; $i < $len; $i += 2 ) {
       $sbin .= pack( "H*", substr( $str, $i, 2 ) );
    }
    return $sbin;
  }
}

/**
 * GoogleSafeBrowsing SQLStorage class
 * 
 * This class im0plements a storage engine for GoogleSafeBrowsing, it stores everything
 * in SQL.
 * 
 * @author Merijn van den Kroonenberg
 * @copyright (c) Copyright 2014 Web2All BV
 * @since 2014-11-19
 */
class Web2All_GoogleSafeBrowsing_SQLStorage_Engine extends Web2All_Manager_Plugin implements GoogleSafeBrowsing_Updater_IStorage, GoogleSafeBrowsing_Lookup_IStorage {
  
  /**
   * The database handle
   *
   * @var ADOConnection
   */
  protected $db;
  
  /**
   * Assoc array with key the list name and value the list id (for fast lookups)
   *
   * @var int[]
   */
  protected $list_ids;
  
  /**
   * Assoc array with key the list name and value the list state
   *
   * @var string[]
   */
  protected $list_states;
  
  /**
   * If verbose is true then debuglogging is on
   *
   * @var boolean
   */
  protected $verbose;
  
  /**
   * constructor
   * 
   * @param Web2All_Manager_Main $web2all
   * @param ADOConnection $db
   */
  public function __construct(Web2All_Manager_Main $web2all, $db, $verbose=true)
  {
    parent::__construct($web2all);
    $this->db=$db;
    $this->updateListIDs();
    $this->verbose=$verbose;
  }
  
  /**
   * Call this method to disable the default vebose mode
   * 
   * @param boolean $quiet
   */
  public function setQuiet($quiet=true)
  {
    $this->verbose=!$quiet;
  }
  
  /**
   * Updates the cached list id's from the database
   * 
   */
  protected function updateListIDs()
  {
    // reset
    $this->list_ids=array();
    $this->list_states=array();
    // get all the lists
    $filter=$this->Web2All->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_List($this->db);
    $lists=$this->Web2All->Plugin->Web2All_Table_ObjectList($filter);
    foreach($lists as $list){
      $this->list_ids[$list->name]=$list->id;
      $this->list_states[$list->name]=$list->state;
    }
  }
  
  /**
   * Log message (debug/informational)
   * 
   * @param string $message
   */
  protected function debugLog($message)
  {
    if($this->verbose){
      $this->Web2All->debugLog('SQLStorage '.$message);
    }
  }
  
  /**
   * Log message (warning/error)
   * 
   * @param string $message
   */
  protected function warningLog($message)
  {
    $this->Web2All->debugLog('SQLStorage '.$message);
  }
  
  // GoogleSafeBrowsing_Updater_IStorage methods
  
  /**
   * Adds one or more hashprefixes
   * 
   * @param string[] $prefixes
   * @param string $list
   */
  public function addHashPrefixes($prefixes, $list)
  {
    $hash=$this->Web2All->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_Hashes4b($this->db);
    // look up list id
    if(!isset($this->list_ids[$list])){
      // unknown list
      trigger_error('Web2All_GoogleSafeBrowsing_SQLStorage_Engine->addHashPrefixes: unknown list "'.$list.'"',E_USER_WARNING);
      return;
    }
    $hash->lst_id=$this->list_ids[$list];
    foreach($prefixes as $prefix){
      if(strlen($prefix)==4){
        $hash->prefix=bin2hex($prefix);
        $hash->long_prefix=0;
      }else{
        $hash->prefix=substr(bin2hex($prefix),0,8);
        // longer prefix, add to hashes_xb table, sort and return sort order
        $longhash=$this->Web2All->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_Hashesxb($this->db);
        $longhash->lst_id=$this->list_ids[$list];
        $longhash->prefix=bin2hex($prefix);
        $longhash->long_prefix=1;
        // insert the long hash without caching
        $longhash->insertIntoDB(true);
        $hash->long_prefix=1;
        // test if multiple long hashes
        $hasxbfilter=$this->Web2All->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_Hashesxb($this->db);
        $hasxbfilter->lst_id=$this->list_ids[$list];
        $hasxbfilter->prefix=$this->Web2All->Plugin->Web2All_Table_SQLOperation("'".$hash->prefix."%'",'LIKE');
        $longhashprefixes=$this->Web2All->Plugin->Web2All_Table_ObjectList($hasxbfilter);
        $longhashprefixes->setExtra(' ORDER BY `hsxb_lst_id` ASC, `hsxb_prefix` ASC');
        if(count($longhashprefixes)>1){
          // okay, there are multiple long hashes for this 4byte hash prefix
          $hash->long_prefix=count($longhashprefixes);
          // re-sort them
          $counter=1;
          foreach($longhashprefixes as $longhashprefix){
            $longhashprefix->long_prefix=$counter;
            $longhashprefix->updateDB();
            $counter++;
          }
        }
      }
      // use caching
      $hash->insertIntoDB(false);
    }
    $hash->flush();
    if(count($prefixes)>30){
      $this->debugLog('addHashPrefixes() added '.count($prefixes).' prefixes [...] in list '.$list);
    }else{
      $this->debugLog('addHashPrefixes() added '.count($prefixes).' prefixes ['.implode(',',array_map('bin2hex',$prefixes)).'] in list '.$list);
    }
  }
  
  /**
   * Remove one or more hashprefixes
   * 
   * @param string[] $prefixes
   * @param string $list
   * @param int $chunk
   */
  public function removeHashPrefixes($prefixes, $list)
  {
    // look up list id
    if(!isset($this->list_ids[$list])){
      // unknown list
      trigger_error('Web2All_GoogleSafeBrowsing_SQLStorage_Engine->removeHashPrefixes: unknown list "'.$list.'"',E_USER_WARNING);
      return;
    }
    
    foreach($prefixes as $prefix){
      $hash=$this->Web2All->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_Hashes4b($this->db);
      if(strlen($prefix)==4){
        if(!$hash->loadFromDB(bin2hex($prefix), $this->list_ids[$list], 0)){
          $this->warningLog('removeHashPrefixes() removing prefix '.bin2hex($prefix).' from list '.$list.', but could not find the prefix');
          // not found so skip to next
          continue;
        }
      }else{
        // longer prefix hash, find in hashes_xb table and get the sort order and remove
        $hashxb=$this->Web2All->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_Hashesxb($this->db);
        if(!$hashxb->loadFromDB(bin2hex($prefix), $this->list_ids[$list])){
          $this->warningLog('removeHashPrefixes() removing prefix '.bin2hex($prefix).' from list '.$list.', but could not find the prefix in hashxb');
          // not found so skip to next
          continue;
        }
        $hashxb->deleteFromDB();
        // reorder long_prefix in hashxb table
        $hasxbfilter=$this->Web2All->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_Hashesxb($this->db);
        $hasxbfilter->lst_id=$this->list_ids[$list];
        $hasxbfilter->prefix=$this->Web2All->Plugin->Web2All_Table_SQLOperation("'".$hash->prefix."%'",'LIKE');
        $longhashprefixes=$this->Web2All->Plugin->Web2All_Table_ObjectList($hasxbfilter);
        $longhashprefixes->setExtra(' ORDER BY `hsxb_lst_id` ASC, `hsxb_prefix` ASC');
        // re-sort them
        $counter=1;
        foreach($longhashprefixes as $longhashprefix){
          $longhashprefix->long_prefix=$counter;
          $longhashprefix->updateDB();
          $counter++;
        }
        // delete the last 4b prefix
        if(!$hash->loadFromDB(bin2hex(substr($prefix,0,4)), $this->list_ids[$list], $counter)){
          $this->warningLog('removeHashPrefixes() removing prefix '.bin2hex($prefix).' from list '.$list.', but could not find the prefix');
          // not found so skip to next
          continue;
        }
      }
      // remove prefix
      $hash->deleteFromDB();
    }
    if(count($prefixes)>30){
      $this->debugLog('removeHashPrefixes() removed '.count($prefixes).' prefixes [...] from list '.$list);
    }else{
      $this->debugLog('removeHashPrefixes() removed '.count($prefixes).' prefixes ['.implode(',',array_map('bin2hex',$prefixes)).'] from list '.$list);
    }
  }
  
  /**
   * Remove all prefixes of this list
   * 
   * @param string $list
   */
  public function removeHashPrefixesFromList($list)
  {
    $this->debugLog('removeHashPrefixesFromList() list '.$list);
    $hashfilter=$this->Web2All->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_Hashes4b($this->db);
    // look up list id
    if(!isset($this->list_ids[$list])){
      // unknown list
      trigger_error('Web2All_GoogleSafeBrowsing_SQLStorage_Engine->removeHashPrefixesFromList: unknown list "'.$list.'"',E_USER_WARNING);
      return;
    }
    $this->db->Execute('DELETE FROM `hashes_4b` WHERE `hs4b_lst_id`=?',array($this->list_ids[$list]));
    $this->db->Execute('DELETE FROM `hashes_xb` WHERE `hsxb_lst_id`=?',array($this->list_ids[$list]));
    // update list restet timestamp
    $list_obj=$this->Web2All->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_List($this->db);
    if($list_obj->loadFromDB($this->list_ids[$list])){
      $list_obj->last_reset=$this->Web2All->Plugin->Web2All_Table_SQLOperation('CURRENT_TIMESTAMP');
      $list_obj->updateDB();
    }
  }
  
  /**
   * Remove one or more hashprefixes
   * 
   * @param int[] $indices
   * @param string $list
   * @return string[]  binary prefixes
   */
  public function getHashPrefixesByIndices($indices, $list)
  {
    $this->debugLog('getHashPrefixesByIndices() list '.$list.' indices '.implode(',',$indices));
    $hashfilter=$this->Web2All->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_Hashes4b($this->db);
    // look up list id
    if(!isset($this->list_ids[$list])){
      // unknown list
      trigger_error('Web2All_GoogleSafeBrowsing_SQLStorage_Engine->removeHashPrefixesFromList: unknown list "'.$list.'"',E_USER_WARNING);
      return array();
    }
    
    $prefixes=array();
    
    $hashfilter->lst_id=$this->list_ids[$list];
    $hashprefixes=$this->Web2All->Plugin->Web2All_Table_ObjectIterator($hashfilter);
    $hashprefixes->setExtra(' ORDER BY `hs4b_lst_id` ASC, `hs4b_prefix` ASC, `hs4b_long_prefix` ASC');
    // there is a tradeoff, if the list of indices is more than a few, then its better to just 
    // iterate (download) the whole sorted list and pick our indices from there
    if(count($indices)<10){
      foreach($indices as $index){
        $hashprefixes->setRange(1,$index);
        $hashprefixes->fetchData();
        foreach($hashprefixes as $hash4b){
          if($hash4b->long_prefix){
            // the prefix is longer than 4 bytes
            $hasxbfilter=$this->Web2All->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_Hashesxb($this->db);
            $hasxbfilter->lst_id=$this->list_ids[$list];
            $hasxbfilter->prefix=$this->Web2All->Plugin->Web2All_Table_SQLOperation("'".$hash4b->prefix."%'",'LIKE');
            $hasxbfilter->long_prefix=$hash4b->long_prefix;
            $longhashprefixes=$this->Web2All->Plugin->Web2All_Table_ObjectList($hasxbfilter);
            $longhashprefixes->setExtra(' ORDER BY `hsxb_lst_id` ASC, `hsxb_prefix` ASC');
            if(count($longhashprefixes)==1){
              $prefixes[]=hex2bin($longhashprefixes[0]->prefix);
            }
          }else{
            $prefixes[]=hex2bin($hash4b->prefix);
          }
        }
      }
    }else{
      sort($indices);
      //todo: could be optimized to start at the first index and stop at last index, using a limit
      $rowcounter=0;
      $next_index=array_shift($indices);
      foreach($hashprefixes as $hash4b){
        if(is_null($next_index)){
          break;
        }
        if($next_index==$rowcounter){
          if($hash4b->long_prefix){
            // the prefix is longer than 4 bytes
            $hasxbfilter=$this->Web2All->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_Hashesxb($this->db);
            $hasxbfilter->lst_id=$this->list_ids[$list];
            $hasxbfilter->prefix=$this->Web2All->Plugin->Web2All_Table_SQLOperation("'".$hash4b->prefix."%'",'LIKE');
            $hasxbfilter->long_prefix=$hash4b->long_prefix;
            $longhashprefixes=$this->Web2All->Plugin->Web2All_Table_ObjectList($hasxbfilter);
            $longhashprefixes->setExtra(' ORDER BY `hsxb_lst_id` ASC, `hsxb_prefix` ASC');
            if(count($longhashprefixes)==1){
              $prefixes[]=hex2bin($longhashprefixes[0]->prefix);
            }
          }else{
            $prefixes[]=hex2bin($hash4b->prefix);
          }
          $next_index=array_shift($indices);
        }
        $rowcounter++;
      }
    }
    return $prefixes;
  }
  
  /**
   * Get all lists and current state from the system
   * 
   * return a assoc array with key the listname and value the state
   * 
   * @return array
   */
  public function getLists()
  {
    $return_lists=array();
    $filter=$this->Web2All->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_List($this->db);
    $lists=$this->Web2All->Plugin->Web2All_Table_ObjectList($filter);
    foreach($lists as $list){
      $return_lists[$list->name]=$list->state;
    }
    return $return_lists;
  }
  
  /**
   * Store the updated state for each list
   * 
   * param is a assoc array with key the listname and value the state
   * 
   * @param array $lists
   */
  public function updateLists($lists)
  {
    $this->debugLog('updateLists() called');
    $filter=$this->Web2All->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_List($this->db);
    $lists_db=$this->Web2All->Plugin->Web2All_Table_ObjectList($filter);
    foreach($lists_db as $list){
      if(isset($lists[$list->name])){
        // ok, existing list, update state
        $list->state=$lists[$list->name];
        $list_update=clone $list;
        $list_update->resetAllPropertiesExcept(array('state'));
        $list_update->updateDB();
        // mark as handled
        unset($lists[$list->name]);
      }else{
        // delete all associated list data (add and sub hashes)
        $this->removeHashPrefixesFromList($list->name);
        // old list list, delete it
        $list->deleteFromDB();
      }
    }
    // all remaining lists must be added
    foreach($lists as $list_name => $list_state){
      $newlist=$this->Web2All->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_List($this->db);
      $newlist->name=$list_name;
      $newlist->state=$list_state;
      $newlist->description='';
      $newlist->last_updated=$this->Web2All->Plugin->Web2All_Table_SQLOperation('CURRENT_TIMESTAMP');
      $newlist->insertIntoDB(true);
    }
    // update our cache
    $this->updateListIDs();
  }
  
  /**
   * Store the nextrun timestamp and errorcount, also clears the full hashes cache
   * 
   * @param int $timestamp  nextrun timestamp
   * @param int $errorcount  how many consecutive errors did we have (if any)
   */
  public function setUpdaterState($timestamp, $errorcount)
  {
    $this->debugLog('setUpdaterState() called ('.$timestamp.', '.$errorcount.')');
    
    $timestamp_obj=$this->Web2All->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_UpdaterState($this->db);
    if(!$timestamp_obj->loadFromDB('nextruntime')){
      // new, add it
      $timestamp_obj->field='nextruntime';
      $timestamp_obj->value=$timestamp;
      $timestamp_obj->insertIntoDB(true);
    }else{
      // update
      $timestamp_obj->value=$timestamp;
      $timestamp_obj->updateDB();
    }
    
    $errorcount_obj=$this->Web2All->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_UpdaterState($this->db);
    if(!$errorcount_obj->loadFromDB('errorcount')){
      // new, add it
      $errorcount_obj->field='errorcount';
      $errorcount_obj->value=$errorcount;
      $errorcount_obj->insertIntoDB(true);
    }else{
      // update
      $errorcount_obj->value=$errorcount;
      $errorcount_obj->updateDB();
    }
    // reset the cached full hashes because this method is mostly called after each update run
    $cache_table=$this->Web2All->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_Hashes32bFull($this->db);
    $cache_table->clearTable();
  }
  
  /**
   * Retrieve the nextrun timestamp
   * 
   * @return int
   */
  public function getNextRunTimestamp()
  {
    $timestamp=$this->Web2All->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_UpdaterState($this->db);
    if(!$timestamp->loadFromDB('nextruntime')){
      return time();
    }
    return (int)$timestamp->value;
  }
  
  /**
   * Retrieve the amount of consecutive errors (0 if no errors)
   * 
   * @return int
   */
  public function getErrorCount()
  {
    $errorcount=$this->Web2All->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_UpdaterState($this->db);
    if(!$errorcount->loadFromDB('errorcount')){
      return 0;
    }
    return (int)$errorcount->value;
  }
  
  /**
   * Calculate checksum
   * 
   * @param string $list
   * @param string $algo (sha256)
   * @return string  base64 encoded hash
   */
  public function getListChecksum($list,$algo)
  {
    $hashfilter=$this->Web2All->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_Hashes4b($this->db);
    // look up list id
    if(!isset($this->list_ids[$list])){
      // unknown list
      trigger_error('Web2All_GoogleSafeBrowsing_SQLStorage_Engine->getListChecksum: unknown list "'.$list.'"',E_USER_WARNING);
      return base64_encode(hash($algo,'',true));
    }
    
    $hashfilter->lst_id=$this->list_ids[$list];
    $hashprefixes=$this->Web2All->Plugin->Web2All_Table_ObjectIterator($hashfilter);
    $hashprefixes->setExtra(' ORDER BY `hs4b_lst_id` ASC, `hs4b_prefix` ASC, `hs4b_long_prefix` ASC');
    $data='';
    foreach($hashprefixes as $prefix){
      if($prefix->long_prefix==0){
        $data.=hex2bin($prefix->prefix);
      }else{
        // the prefix is longer than 4 bytes
        $hasxbfilter=$this->Web2All->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_Hashesxb($this->db);
        $hasxbfilter->lst_id=$this->list_ids[$list];
        $hasxbfilter->prefix=$this->Web2All->Plugin->Web2All_Table_SQLOperation("'".$prefix->prefix."%'",'LIKE');
        $hasxbfilter->long_prefix=$prefix->long_prefix;
        $longhashprefixes=$this->Web2All->Plugin->Web2All_Table_ObjectList($hasxbfilter);
        if(count($longhashprefixes)==1){
          $data.=hex2bin($longhashprefixes[0]->prefix);
        }else{
          trigger_error('Web2All_GoogleSafeBrowsing_SQLStorage_Engine->getListChecksum: long prefixes are corrupted',E_USER_WARNING);
        }
      }
    }
    return base64_encode(hash($algo,$data,true));
  }
  
  // GoogleSafeBrowsing_Lookup_IStorage methods
  
  /**
   * Lookup prefix for url hashes
   * 
   * @param string[] $lookup_hashes
   * @return string[]  hashes with prefix present
   */
  public function hasPrefix($lookup_hashes)
  {
    $found_hashes=array();
    foreach($lookup_hashes as $lookup_hash){
      $hex_lookup_hash=bin2hex($lookup_hash);
      $hash_filter=$this->Web2All->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_Hashes4b($this->db);
      $hash_filter->prefix=substr($hex_lookup_hash,0,8);
      $prefixes_found=$this->Web2All->Plugin->Web2All_Table_ObjectList($hash_filter);
      foreach($prefixes_found as $prefix_found){
        if($prefix_found->long_prefix==0){
          // found
          $found_hashes[]=$lookup_hash;
          // hash found in at least one list so thats enough
          break;
        }else{
          // found, but the prefix is longer than 4 bytes so we need to lookup the longer prefix
          $hasxbfilter=$this->Web2All->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_Hashesxb($this->db);
          $hasxbfilter->prefix=$this->Web2All->Plugin->Web2All_Table_SQLOperation("'".$prefix_found->prefix."%'",'LIKE');
          $long_prefixes_found=$this->Web2All->Plugin->Web2All_Table_ObjectList($hasxbfilter);
          foreach($long_prefixes_found as $long_prefix_found){
            if($long_prefix_found->prefix===substr($hex_lookup_hash,0,strlen($long_prefix_found->prefix))){
              // found
              $found_hashes[]=$lookup_hash;
              // hash found in at least one list so thats enough
              break 2;
            }
          }
        }
      }
    }
    return $found_hashes;
  }
  
  /**
   * Lookup listnames for the given url hash
   * 
   * Only do this for hashes for which a prefix was found!
   * 
   * @param string $lookup_hash
   * @return string[]  list names or null if not cached
   */
  public function isListedInCache($lookup_hash)
  {
    // full hash lookups
    $hexhash=bin2hex($lookup_hash);
    
    // Note: we are not fully compliant with
    //   https://developers.google.com/safe-browsing/v4/caching
    // In order to be compliant, we should check for expired full hash matches
    // and return null when an expired full hash match is found. We should also
    // always store a negative cache (only prefix with expiration) and not remove
    // any expired full hash entries before the negative cache has expired.
    
    // check if cached
    $full_hash_filter=$this->Web2All->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_Hashes32bFull($this->db);
    $full_hash_filter->prefix=substr($hexhash,0,8);
    $full_hash_filter->expire=$this->Web2All->Plugin->Web2All_Table_SQLOperation('CURRENT_TIMESTAMP','>');
    $full_hashes_found=$this->Web2All->Plugin->Web2All_Table_ObjectList($full_hash_filter);
    if(count($full_hashes_found)>0){
      $result = array();
      // if anything found then it means the full length hash request is cached
      $this->debugLog('isListedInCache: full length hash request is cached for prefix '.substr($hexhash,0,8));
      foreach($full_hashes_found as $full_hash){
        $this->debugLog('isListedInCache:   hash '.$full_hash->hash);
        if($full_hash->hash==$hexhash){
          // full length hash match
          $listname=array_search($full_hash->lst_id,$this->list_ids);
          if(!$listname){
            $this->warningLog('isListedInCache: '.$hexhash.' has been blacklisted in UNKNOWN list '.$full_hash->lst_id);
            continue;
          }
          $this->debugLog('isListedInCache: '.$hexhash.' has been blacklisted in '.$listname);
          if(!in_array($listname, $result)){
            $result[]=$listname;
          }
        }
      }
      return $result;
    }else{
      // not cached, return null
      return null;
    }
  }
  
  /**
   * Add a full hash to the cache
   * 
   * @param string $full_hash
   * @param string[] $lists  list names
   * @param string $meta
   * @param int $cache_seconds
   * @return boolean
   */
  public function addHashInCache($full_hash,$lists,$meta,$cache_seconds)
  {
    $cached_hash=$this->Web2All->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_Hashes32bFull($this->db);
    $cached_hash->prefix=substr(bin2hex($full_hash),0,8);
    $cached_hash->hash=bin2hex($full_hash);
    if(!empty($meta)){
      // add meta
      $cached_hash->meta=$meta;
    }
    if($this->db->databaseType=='sqlite3'){
      $cached_hash->expire=$this->Web2All->Plugin->Web2All_Table_SQLOperation('datetime("now", "+'.((int)$cache_seconds).' second")');
    }else{
      $cached_hash->expire=$this->Web2All->Plugin->Web2All_Table_SQLOperation('DATE_ADD(CURRENT_TIMESTAMP, INTERVAL '.((int)$cache_seconds).' SECOND)');
    }
    if(empty($lists)){
      $lists=array();
    }
    $inserted=false;
    foreach($lists as $listname){
      if(!isset($this->list_ids[$listname])){
        // skip list
        $this->warningLog('addHashInCache: UNKNOWN list '.$listname.' cannot added full hash '.$cached_hash->hash.' to cache for this list');
        continue;
      }
      $cached_hash->lst_id=$this->list_ids[$listname];
      $cached_hash->insertIntoDB(true);
      $this->debugLog('addHashInCache: prefix '.$cached_hash->prefix.' added full hash '.$cached_hash->hash.' to cache for '.$listname);
      $inserted=true;
    }
    if(!$inserted){
      // insert empty record for this prefix (negative caching)
      // is this correct? in what situation would we need negative caching, does it mean the prefix db is out of date? Does it actually ever happen?
      // I think to be 100% compliant with google's caching requirements
      // https://developers.google.com/safe-browsing/v4/caching
      // we should be able to identify an expired cache hit. And we should always store 
      // negative caching records because negative and positive caching time can differ.
      $cached_hash->insertIntoDB(true);
      $this->debugLog('addHashInCache: prefix '.$cached_hash->prefix.' added negative caching');
    }
    return true;
  }
  
}

?>