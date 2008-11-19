<?php

/**
 * Subclass for representing a row from the 'sf_moderated_content' table.
 *
 * 
 *
 * @package plugins.sfModerationPlugin.lib.model
 */ 
class PluginsfModeratedContent extends BasesfModeratedContent
{
  /**
    * Related object
    * @var  BaseObject  Related object
    */
   protected $related_object;
   
   /**
    * Retrieves moderated content code name
    * 
    * @return string
    */
   public function getType()
   {
     return sfModeratedContentPeer::getTypeFromClass($this->getObjectModel());
   }
   
   /**
    * Retrieves moderated content status name
    * 
    * @return string
    */
   public function getStatusString()
   {
     return sfPropelModerationBehavior::getStatusStringFromInteger($this->getStatus());
   }
   
   /**
    * Retrieves related moderable object instance
    * 
    * @return BaseObject The moderable object instance
    */
   public function getRelatedObject()
   {
     if (is_null($this->related_object)) 
     {
       $object_model = $this->getObjectModel();
       $object_id    = $this->getObjectId();
       $this->related_object = call_user_func(array($object_model.'Peer', 'retrieveByPK'), $object_id);
       if (!$this->related_object)
       {
         throw new sfException(sprintf('Unable to retrieve related "%s" moderable object with id=%d', $object_model, $object_id));
       }
     }
     
     return $this->related_object;
   }
   
   /**
    * Populate from a Propel object
    * 
    * @param BaseObject The object to introspect for data
    *
    * @return sfModeratedContent The current object
    */
   public function populateFromObject($object)
   {
     $object_class = get_class($object);
     if (!sfPropelModerationBehavior::isMonitored($object_class))
     {
       throw new sfException(sprintf('Object of class "%s" must be declared as monitored', $object_class));
     }
     
     // Object type and reference setting
     $this->setObjectModel($object_class);
     $this->setObjectId($object->getPrimaryKey());
     
     // Object content
     $this->setTitle(sfModeratedContentPeer::getOneOf($object, array(sfPropelModerationBehavior::getConfigParam($object_class, 'title_getter'), 'getTitle')));
     $this->setContent(sfModeratedContentPeer::getOneOf($object, array(sfPropelModerationBehavior::getConfigParam($object_class, 'content_getter'), 'getHtmlBody', 'getBody', 'getContent', 'getDescription')));
     $this->setUrl(sfModeratedContentPeer::getOneOf($object, array(sfPropelModerationBehavior::getConfigParam($object_class, 'url_getter'), 'getSlug', 'getUrl', 'getLink')));
     
     // User attributes
     $author = sfModeratedContentPeer::getAuthor($object);
     if(method_exists($author, 'getProfile'))
     {
       $author = $author->getProfile();
     }
     
     $username = sfModeratedContentPeer::getOneOf($object, array(sfPropelModerationBehavior::getConfigParam($object_class, 'username_getter'), 'getUserName'));
     if (!$username && $author)
     {
       $username = sfModeratedContentPeer::getOneOf($author, array('__toString', 'getName', 'getLastName'));
     }
     $this->setUserName($username);
     
     $useremail = sfModeratedContentPeer::getOneOf($object, array(sfPropelModerationBehavior::getConfigParam($object_class, 'useremail_getter'), 'getUserMail', 'getUserEmail'));
     if (!$useremail && $author)
     {
       $useremail = sfModeratedContentPeer::getOneOf($author, array('getMail', 'getEmail'));
     }
     $this->setUserEmail($useremail);
     
     // Date of last modification
     $date_modified = null;
     if (method_exists($object, 'getUpdatedAt') && $object->getUpdatedAt())
     {
       $date_modified = $object->getUpdatedAt();
     }
     elseif (method_exists($object, 'getCreatedAt') && $object->getCreatedAt())
     {
       $date_modified = $object->getCreatedAt();
     }
     $this->setObjectUpdatedAt($date_modified);
     $this->setModeratedAt(time());
     
     // Moderation status and comment
     $this->setStatus(sfPropelModerationBehavior::getModerationStatus($object));
     $this->setComment(sfPropelModerationBehavior::getModerationComment($object));
     
     return $this;
   }
}
