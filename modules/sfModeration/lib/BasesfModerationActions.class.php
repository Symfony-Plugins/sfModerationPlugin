<?php

/**
 * sfModeration actions.
 *
 * @package    forum
 * @subpackage sfModeration
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 2288 2006-10-02 15:22:13Z fabien $
 */
class BasesfModerationActions extends autosfModerationActions
{
  public function executeListMarkAsSafe()
  {
    $sf_moderated_content = sfModeratedContentPeer::retrieveByPk($this->getRequestParameter('id'));
    $this->forward404Unless($sf_moderated_content);
    
    sfPropelModerationBehavior::disable();
    $sf_moderated_content->getRelatedObject()->tagAsSafe();
    sfPropelModerationBehavior::enable();
    
    $this->redirect('sfModeration/list');
  }
  
  public function executeListSeeOriginalPost()
  {
    $sf_moderated_content = sfModeratedContentPeer::retrieveByPk($this->getRequestParameter('id'));
    $this->forward404Unless($sf_moderated_content);
    
    $this->redirect($sf_moderated_content->getUrl());
  }
  
  protected function addFiltersCriteria($c)
  {
    if (isset($this->filters['object_model']) && $this->filters['object_model'] !== '')
    {
      $c->add(sfModeratedContentPeer::OBJECT_MODEL, $this->filters['object_model']);
    }
    if (isset($this->filters['status']) && $this->filters['status'] !== '-1')
    {
      $c->add(sfModeratedContentPeer::STATUS, $this->filters['status']);
    }
    parent::addFiltersCriteria($c);

  }
}
