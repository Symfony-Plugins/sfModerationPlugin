<?php

sfPropelBehavior::registerHooks(sfPropelModerationBehavior::BEHAVIOR_NAME, 
  array(
    'Peer:doSelectRS'             => array('sfPropelModerationBehavior', 'updateCriteria'),
    'Peer:doSelectJoin'           => array('sfPropelModerationBehavior', 'updateCriteria'),
    'Peer:doSelectJoinAll'        => array('sfPropelModerationBehavior', 'updateCriteria'),    
    'Peer:doSelectJoinAllExcept'  => array('sfPropelModerationBehavior', 'updateCriteria'),
    ':save:pre'                   => array('sfPropelModerationBehavior', 'doPreSave'),
    ':save:post'                  => array('sfPropelModerationBehavior', 'doPostSave'),
    ':delete:post'                => array('sfPropelModerationBehavior', 'doPostDelete')
  )
);

sfPropelBehavior::registerMethods(sfPropelModerationBehavior::BEHAVIOR_NAME,  array(
  array('sfPropelModerationBehavior', 'tagAsUnsafe'),
  array('sfPropelModerationBehavior', 'tagAsSafe'),
  array('sfPropelModerationBehavior', 'getModerationStatus'),
  array('sfPropelModerationBehavior', 'setModerationStatus'),
  array('sfPropelModerationBehavior', 'getModerationStatusString'),
  array('sfPropelModerationBehavior', 'setModerationStatusString'),
  array('sfPropelModerationBehavior', 'getModerationComment'),
  array('sfPropelModerationBehavior', 'setModerationComment'),
  array('sfPropelModerationBehavior', 'getModeratedContentFromObject')
));

$moderated_classes = sfConfig::get('app_sfModerationPlugin_moderated_classes', false);
if(sfConfig::get('app_sfModerationPlugin_auto_register_behavior', true) && $moderated_classes)
{
  if(isset($moderated_classes[0]))
  {
    // simple array
    foreach($moderated_classes as $model)
    {
      sfPropelBehavior::add($model, array(sfPropelModerationBehavior::BEHAVIOR_NAME));
    }
  }
  else
  {
    // associative array
    foreach($moderated_classes as $model => $params)
    {
      sfPropelBehavior::add($model, array(sfPropelModerationBehavior::BEHAVIOR_NAME => $params));
    }
  }
}

