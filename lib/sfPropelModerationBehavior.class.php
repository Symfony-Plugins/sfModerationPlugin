<?php

class sfPropelModerationBehavior
{
  /**
   * Behavior name
   * @const string
   */
  const BEHAVIOR_NAME = 'moderation';

  /**
   * Moderation statuses
   * @const int
   */
  const TAGGED_SAFE        = 0;
  const NOT_CHECKED        = 1;
  const AUTO_TAGGED_UNSAFE = 2;
  const TAGGED_UNSAFE      = 3;
  
  /**
   * Default moderation status
   * @const int
   */
  const DEFAULT_MODERATION_STATUS = 1;

  
  /**
   * Array of valid moderation statuses. Keys are the 
   * moderation status codes, values are human-readable status strings 
   * 
   * @var array
   */
  protected static $statuses = array(-1 => 'All status',
                                      0 => 'Accepted',
                                      1 => 'Not checked',
                                      2 => 'Auto refused',
                                      3 => 'Refused');
  /**
   * Activation status of the behavior (use enable() or disable() to change)
   * 
   * @var boolean
   */                                      
  protected static $isActivated = true;
  
  /**
   * Retrieves local object behavior parameter
   * 
   * @param  String  Propel object class (e.g. 'Article')
   *
   * @return mixed
   */
  public static function getConfigParam($class, $param_name, $default_value = null)
  {
    return sfConfig::get(sprintf('propel_behavior_%s_%s_%s', self::BEHAVIOR_NAME, $class, $param_name), $default_value);
  }
  
  /**
   * Retrieves the name of the moderation status column for a given class
   * Also capable of returning the moderation comment column
   * 
   * @param  String  Parameter name ('status' or 'comment')
   * @param  String  Propel object class name (e.g. 'Article')
   *
   * @return String  Column name (BasePeer::TYPE_FIELDNAME)
   */  
  protected static function getFieldNameFromClass($name, $class)
  {
    switch ($name)
    {
      case 'status':
        return self::getConfigParam($class, 'status_column', 'moderation_status');
      case 'comment':
        return self::getConfigParam($class, 'comment_column', 'moderation_comment');
    }
  }
  
  /**
   * Retrieves class name from Propel peer class name.
   * Takes care of removing potential 'Base' prefix
   * 
   * @param  String  Peer class name
   *
   * @return String Propel class name
   */
  protected static function getClassFromPeerClass($peerClass)
  {
    if(strpos($peerClass, 'Base') !== false)
    {
      // BaseFooBarPeer
      return substr($peerClass, 4, -4);
    }
    else
    {
      // FooBarPeer
      return substr($peerClass, 0, -4);
    }
  }

  /**
   * Retrieves the name of the moderation status column for a given peer class
   * Also capable of returning the moderation comment column
   * 
   * @param  String  Parameter name ('status' or 'comment')
   * @param  String  Propel Peer class name (e.g. 'ArticlePeer')
   *
   * @return String  Column name (BasePeer::TYPE_COLNAME)
   *
   * @throws PropelException When column name does not exist for the class
   */
  protected static function getColNameFromPeerClass($name, $peerClass)
  {
    $fieldName = self::getFieldNameFromClass($name, self::getClassFromPeerClass($peerClass));
    
    return call_user_func(array($peerClass, 'translateFieldName'), $fieldName, BasePeer::TYPE_FIELDNAME, BasePeer::TYPE_COLNAME);
  }

  /**
    * Gets a criteria selecting all the unsafe elements of a given peer class
    * 
    * @param  String    Propel Peer class name (e.g. 'ArticlePeer')
    * @param  Criteria  Optional Criteria object to augment of new conditions
    * @param  Integer   Optional publication treshold
    *
    * @return Criteria  Criteria object
    */  
  public static function getUnsafeCriteria($peerClass, $c = null, $treshold = self::AUTO_TAGGED_UNSAFE)
  {
    if(!$c instanceof Criteria)
    {
      $c = new Criteria();
    }
    $c->add(self::getColNameFromPeerClass('status', $peerClass), $treshold, Criteria::GREATER_EQUAL);
    
    return $c;
  }
  
  /**
    * Gets an array of all the unsafe Propel objects of a given peer class
    * 
    * @param  String   Propel Peer class name (e.g. 'ArticlePeer')
    * @param  Criteria Optional Criteria object to augment of new conditions
    * @param  Integer  Optional publication treshold
    * @param  String   Optional peer selection method, for joins ('doSelect' by default)
    * @param  Mixed    Optional Connexion object
    *
    * @return Array  Array of Propel objects
    */
  public static function doSelectUnsafe($peerClass, $c = null, $treshold = self::AUTO_TAGGED_UNSAFE, $selectMethod = 'doSelect', $con = null)
  {
    $c = self::getUnsafeCiteria($peerClass, $c, $treshold);
    self::disable();
    $res = call_user_func(array($peerClass, $selectMethod), $c, $con);
    self::enable();
    
    return $res;
  }

  /**
    * Deletes all the unsafe records of a given peer class
    * 
    * @param  String   Propel Peer class name (e.g. 'ArticlePeer')
    * @param  Criteria Optional Criteria object to augment of new conditions
    * @param  Integer  Optional publication treshold
    * @param  Mixed    Optional Connexion object
    *
    * @return Integer  Number of affected rows
    */  
  public static function doDeleteUnsafe($peerClass, $c = null, $treshold = self::AUTO_TAGGED_UNSAFE, $con = null)
  {
    if (self::isMonitored($object_class))
    {
      // start by deleting related sfModeratedContent records
      $c1 = self::getUnsafeCiteria($peerClass, $c, $treshold);
      $c1->add(sfModeratedContentPeer::OBJECT_MODEL, self::getClassFromPeerClass($peerClass));
      // FIXME: make it work for classes with PK different than 'ID'. But how?
      $c1->addJoin(constant($peerClass.'::ID'), sfModeratedContentPeer::OBJECT_ID);
      sfModeratedContentPeer::doDelete($c1, $con);
    }
    $c = self::getUnsafeCiteria($peerClass, $c, $treshold);
    
    return call_user_func(array($peerClass, 'doDelete'), $c, $con);
  }
  
  /**
    * Augment a criteria to get only the safe elements of a given peer class
    * 
    * @param  String    Propel Peer class name (e.g. 'ArticlePeer')
    * @param  Criteria  Criteria object to augment of new conditions
    * @param  Integer   Optional publication treshold
    *
    * @return Criteria  Criteria object
    */
  public static function updateCriteria($peerClass, $myCriteria, $con = null)
  {
    if (self::$isActivated)
    {
      $myCriteria->add(
        self::getColNameFromPeerClass('status', $peerClass),
        sfConfig::get('app_sfModerationPlugin_display_treshold', self::NOT_CHECKED),
        Criteria::LESS_EQUAL
      );
    }
  }
  
  /**
    * Change the moderation status of an object to unsafe and save it
    * 
    * @param  BaseObject Propel object
    * @param  Integer    Optional moderation type
    * @param  String     Optional moderation comment
    *
    * @return Integer  Number of affected rows
    */
  public static function tagAsUnsafe($object, $unsafe_type = self::TAGGED_UNSAFE, $comment = null)
  {
    self::setModerationStatus($object, $unsafe_type);
    if($comment)
    {
      self::setModerationComment($object, $comment);
    }
    self::leaveUpdatedAtUnchanged($object);
    
    return $object->save();
  }

  /**
    * Change the moderation status of an object to safe and save it
    * 
    * @param  BaseObject Propel object
    *
    * @return Integer    Number of affected rows
    */  
  public static function tagAsSafe(BaseObject $object)
  {
    self::setModerationStatus($object, self::TAGGED_SAFE);
    self::leaveUpdatedAtUnchanged($object);
    
    return $object->save();
  }
  
  /**
   * Avoid auto modification of updated_at date on moderation
   *
   * @param BaseObject PropelObject
   */
  public static function leaveUpdatedAtUnchanged(BaseObject $object)
  {
    if(method_exists($object, 'setUpdatedAt'))
    {
      // To avoid symfony from auto updating the updated_at column, it must be declared as already modified
      // Unfortunately, the modified_columns array of propel objects is private
      // The trick consists of modifying the updated_at column to include it in the modified_columns array,
      // Then to modify the updated_at column back to its original value to avoid change
      // (sorry for the hack)
      $object_update_date = $object->getUpdatedAt();
      $object->setUpdatedAt(0);
      $object->setUpdatedAt($object_update_date);
    }
  }
  
  /**
    * Retrieves the moderation status of an object
    * 
    * @param  BaseObject Propel object
    *
    * @return Integer    Moderation status
    */
  public static function getModerationStatus(BaseObject $object)
  {
    return $object->getByName(self::getFieldNameFromClass('status', get_class($object)), BasePeer::TYPE_FIELDNAME);
  }
  
  /**
    * Sets the moderation status of an object
    * 
    * @param  BaseObject Propel object
    * @param  Integer    Moderation status
    */
  public static function setModerationStatus(BaseObject $object, $value)
  {
    $object->setByName(self::getFieldNameFromClass('status', get_class($object)), $value, BasePeer::TYPE_FIELDNAME);
  }
  
  /**
   * Gets moderation status string of an object
   * 
   * @param  BaseObject  Propel object
   *
   * @return String      Moderation status name
   */
  public static function getModerationStatusString(BaseObject $object)
  {
    return self::getStatusStringFromInteger(self::getModerationStatus($object));
  }
  
  /**
   * Sets moderation status string of an object
   * 
   * @param  BaseObject  Propel object
   * @param  String      Moderation status name
   */
  public static function setModerationStatusString(BaseObject $object, $status)
  {
    return self::setModerationStatus($object, self::getStatusIntegerFromString($status));
  }
  
  /**
   * Get int value for status from its string equivalent
   * 
   * @param  string  $status
   * @return int
   */
  public static function getStatusIntegerFromString($status)
  {
    $flip_statuses = array_flip(self::getStatuses());
    if (!isset($flip_statuses[$status]))
    {
      throw new sfException(sprintf('Status "%s" does not exist', $status));
    }
    return $flip_statuses[$status];
  }
  
  /**
   * Get string status value from its int equivalent
   * 
   * @param  int  $status
   * @return string
   */
  public static function getStatusStringFromInteger($status)
  {
    $statuses = sfPropelModerationBehavior::getStatuses();
    
    return $statuses[$status];
  }
  
  /**
   * Get available moderation statuses
   * 
   * @return array Associative array of status codes and names
   */
  public static function getStatuses()
  {
    return sfConfig::get('app_sfModerationPlugin_status_names', self::$statuses);
  }
  
  /**
    * Retrieves the moderation comment of an object
    * 
    * @param  BaseObject Propel object
    *
    * @return Mixed      Moderation comment, or false if the column does not exist
    */
  public static function getModerationComment(BaseObject $object)
  {
    if (isset($object->_moderation_comment))
    {
      return $object->_moderation_comment;
    }
    else
    {
      try
      {
        $class = get_class($object);
        self::getColNameFromPeerClass('comment', $class.'Peer');
      }
      catch (Exception $e)
      {
        return false;
      }
      
      return $object->getByName(self::getFieldNameFromClass('comment', $class), BasePeer::TYPE_FIELDNAME);      
    }
  }
  
  /**
    * Sets the moderation comment of an object
    * 
    * @param  BaseObject Propel object
    * @param  String     Moderation comment
    */
  public static function setModerationComment(BaseObject $object, $comment)
  {
    $class = get_class($object);
    $object->_moderation_comment = $comment;
    try
    {
      self::getColNameFromPeerClass('comment', $class.'Peer');
      $object->setByName(self::getFieldNameFromClass('comment', $class), $comment, BasePeer::TYPE_FIELDNAME);
    }
    catch (Exception $e)
    {
      return false;
    }
  }
  
  /**
   * Check whether moderation monitoring is enabled for a particular class
   *
   * @param String Propel Class (e.G. 'Article')
   *
   * @return Boolean
   */
  public static function isMonitored($class)
  {
    $is_multiple = self::getConfigParam($class, 'is_multiple', false);
    $is_monitored = self::getConfigParam($class, 'is_monitored', true);
    return ($is_monitored || ($is_multiple && $is_monitored))
        && sfConfig::get('app_sfModerationPlugin_is_monitored', false);
  }

  /**
   * Did we modify a watched column ?
   *
   * @param BaseObject $object
   * @return bool
   */
  protected static function areWatchedColumnsModified(BaseObject  $object)
  {
    $class = get_class($object);
    if($columns = self::getWatchedColumns($class))
    {
      foreach($columns as $column)
      {
        if($object->isColumnModified($object->getPeer()->translateFieldName($column, BasePeer::TYPE_FIELDNAME, BasePeer::TYPE_COLNAME)))
        {
          return true;
        }
      }
      
      return false;
    }
    else
    {
      // all object monitored
      return true;
    }
  }
  
  /**
   * Columns to watch for this class
   *
   * @param string $class
   * @return array
   */
  public static function getWatchedColumns($class)
  {
    $config_watch_columns = self::getConfigParam($class, 'watch_columns', false);
    if (!$config_watch_columns)
    {
      return null;
    }
    else
    {
      $status_comment = array(
        self::getFieldNameFromClass('status', $class),
        self::getFieldNameFromClass('comment', $class),
      );  
      $config_watch_columns = array_unique(array_merge($config_watch_columns, $status_comment));
      
      return $config_watch_columns;
    }  
  }
  
  /**
   * Columns monitored (copied to moderation table) for this class
   *
   * @param string $class
   * @return array
   */
  public static function getMonitoredColumns($class)
  {
    if ($contents = self::getConfigParam($class, 'contents'))
    {
      return array_keys($contents);
    }
    
    return array();
  }

  /**
   * Mark if this modification interests moderation
   *
   * @param BaseObject $object
   */
  public function doPreSave(BaseObject $object)
  {
    $object->isModifiedForModeration = self::areWatchedColumnsModified($object);
    
  }
   
  /**
   * Create a sf_moderated_content entry after saving a moderable object
   * 
   * @param  BaseObject  $object
   */
  public function doPostSave(BaseObject $object)
  {
    $object_class = get_class($object);
    if (self::isMonitored($object_class) && isset($object->isModifiedForModeration) && $object->isModifiedForModeration)
    {
      if($object->getModerationStatus() >= sfConfig::get('app_sfModerationPlugin_monitoring_treshold', self::AUTO_TAGGED_UNSAFE))
      {
        // Above monitoring treshold: save to sfModeratedContent table
        $moderatedContent = self::getModeratedContentFromObject($object);
        if(!$moderatedContent)
        {
          $moderatedContent = new sfModeratedContent();
        }
        $moderatedContent->populateFromObject($object);
        
        $ok = $moderatedContent->save();
        
        if (sfConfig::get('sf_logging_enabled'))
        {
          sfContext::getInstance()->getLogger()->info('New moderated entry from object data');
        }
        
        return $ok;
      }
      else
      {
        // Below monitoring treshold: delete related sfModeratedContent record
        if($moderatedContent = self::getModeratedContentFromObject($object))
        {
          $moderatedContent->delete();
        }
      }
    }
  }
  
  /**
   * Executes post deletion cascade (deletes all moderated_content entries related
   * to current object)
   * 
   * @param  BaseObject  $object
   */
  public function doPostDelete(BaseObject $object)
  {
    $object_class = get_class($object);
    if (self::isMonitored($object_class))
    {
      $c = new Criteria();
      $c->add(sfModeratedContentPeer::OBJECT_MODEL, $object_class);
      $c->add(sfModeratedContentPeer::OBJECT_ID, $object->getPrimaryKey());
      
      return sfModeratedContentPeer::doDelete($c);
    }
  }
  
  /**
   * Retrieves the latest sfModeratedContent object related to a Propel Object under moderation and monitoring
   *
   * @param BaseObject Monitored propel object
   *
   * @return Mixed sfModeratedContent object if found, false or null otherwise
   */
  public static function getModeratedContentFromObject(BaseObject $object)
  {
    $object_class = get_class($object);
    if(self::isMonitored($object_class))
    {
      $c = new Criteria();
      $c->add(sfModeratedContentPeer::OBJECT_MODEL, $object_class);
      $c->add(sfModeratedContentPeer::OBJECT_ID, $object->getPrimaryKey());
      
      return sfModeratedContentPeer::doSelectOne($c);
    }
    else
    {
      return false;
    }
  }
  
  /**
    * Enable the behavior globally, for all objects
    */
  static public function enable()
  {
    self::$isActivated = true; 
  }
  
  /**
    * Disable the behavior globally, for all objects
    */
  static public function disable()
  {
    self::$isActivated = false; 
  }
}
