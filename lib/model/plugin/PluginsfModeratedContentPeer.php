<?php

/**
 * Subclass for performing query and update operations on the 'sf_moderated_content' table.
 *
 * 
 *
 * @package plugins.sfModerationPlugin.lib.model
 */ 
class PluginsfModeratedContentPeer extends BasesfModeratedContentPeer
{
  protected static $types       = array();
  
  /**
    * Retrieves moderated contents from a date start, a date end,
    * and optionaly a type and a status
    * 
    * @param  integer Unix timestamp of start date
    * @param  integer Unix timestamp of end date
    * @param  string  Type of content (optional)
    * @param  mixed   Moderation status, can be integer or string (optional)
    * @return array   Array of Propel Objects
    */
   public static function get($start, $end, $type = null, $status = null)
   {
     // Type and status synonyms
     if ($type === 'all') $type = null;
     if ($status === 'all') $status = null;
     
     $datetime_start = date('Y-m-d H:i:s', $start);
     $datetime_end = date('Y-m-d H:i:s', $end);
     
     $c = new Criteria();

     // Date
     $c->add(self::CREATED_AT, $datetime_start, Criteria::GREATER_EQUAL);
     $c->add(self::CREATED_AT, $datetime_end, Criteria::LESS_EQUAL);
     
     // Type
     if (!is_null($type))
     {
       if (!sfPropelModerationBehavior::typeExists($type))
       {
         throw new sfException(sprintf('Type "%s" is not a valid moderable type', $type));
       }
       $c->add(self::OBJECT_MODEL, sfPropelModerationBehavior::getClassFromType($type));
     }
     
     // Status
     if (is_string($status))
     {
       $c->add(self::STATUS, sfPropelModerationBehavior::getStatusIntegerFromString($status));
     }
     else if (is_int($status))
     {
       $statuses = sfPropelModerationBehavior::getStatuses();
       if (!array_key_exists($status, $statuses))
       {
         throw new sfException(sprintf('status code "%d" does not exist', $status));
       }
       $c->add(self::STATUS, $status);
     }
     elseif (!is_null($status))
     {
       throw new sfException('Status must be integer or string');
     }
     
     // Sort order
     $c->addDescendingOrderByColumn(self::CREATED_AT);
     
     return self::doSelect($c);
   }
   
   /**
    * Retrieves a value from an object by testing several accessor methods
    *
    * @param BaseObject  Propel object to introspect
    * @param Array       Array of method names to test
    *
    * @return mixed      Value of the object property
    */
   public static function getOneOf($object, $methods = array())
   {
     foreach ($methods as $method)
     {
       if (method_exists($object, $method))
       {
         return call_user_func(array($object, $method));
       }
     }
     
     return null;
   }
   
   public static function getClasses()
   {
     $c = new Criteria();
     $c->clearSelectColumns();
     $c->addSelectColumn(self::OBJECT_MODEL);
     $c->setDistinct();
     $res = self::doSelectRs($c);
     $classes = array();
     while($res->next())
     {
       $classes[] = $res->getString(1);
     }
     
     return $classes;
   }

   /**
    * Returns available moderation behavior enabled types
    * 
    * @return Array Associative array $class => $type
    */
   public static function getTypes()
   {
     if(!self::$types)
     {       
       if(!$types = sfConfig::get('app_sfModerationPlugin_type_names', array()))
       {
         $classes = self::getClasses();
         foreach ($classes as $class)
         {
           $types[$class] = sfPropelModerationBehavior::getConfigParam($class, 'type', $class);
         }         
       }
       self::$types = $types;
     }

     return self::$types;
   }

   /**
    * Checks if a type exists (if an object is declared as moderable)
    * 
    * @param  string
    *
    * @return boolean
    */
   public static function typeExists($type)
   {
     $types = self::getTypes();

     return in_array($type, $types);
   }
   
   /**
    * Retrieves class of moderated content from its type
    *
    * @param String moderated content type
    *
    * @return Mixed Class name if it exists, or null otherwise
    */
   public static function getClassFromType($type)
   {
     $types = self::getTypes();
     
     if(self::typeExists($type))
     {
       return array_search($type, $types);
     }
     else
     {
       return null;
     }
   }
   
   /**
    * Retrieves type of moderated content from its class
    *
    * @param String moderated content type
    *
    * @return Mixed Class name if it exists, or null otherwise
    */
   public static function getTypeFromClass($class)
   {
     $types = self::getTypes();
     
     if(array_key_exists($class, $types))
     {
       return $types[$class];
     }
     else
     {
       return null;
     }
   }
   
   /**
    * Retrieves a user object from a Propel object by testing several accessor methods
    *
    * @param BaseObject  Propel object to introspect
    * @param Array       Array of method names to test
    *
    * @return BaseObject User object
    */
   public static function getAuthor($object, $methods = array())
   {
     if(!$methods)
     {
       $methods = array('getsfGuardUser', 'getAuthor', 'getUser', 'getPerson', 'getMember');
     }
     
     foreach ($methods as $methodName)
     {
       if (method_exists($object, $methodName) && is_object($object->$methodName()))
       {
         return $object->$methodName();
       }
     }

     return null;
   }
}
