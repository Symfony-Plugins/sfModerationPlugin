<?php

/**
 * sfModeration actions.
 *
 * @package    forum
 * @subpackage sfModeration
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 2288 2006-10-02 15:22:13Z fabien $
 */
 
// autoloading for plugin lib actions is broken as at symfony-1.0.2
require_once(sfConfig::get('sf_plugins_dir'). '/sfModerationPlugin/modules/sfModeration/lib/BasesfModerationActions.class.php');

class sfModerationActions extends BasesfModerationActions
{
}
