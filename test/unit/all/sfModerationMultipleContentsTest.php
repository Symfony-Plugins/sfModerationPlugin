<?php
/**
 * This file is part of the sfModerationPlugin package.
 * 
 * (c) 2007 Francois Zaninotto <francois.zaninotto@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

include dirname(__FILE__).'/../bootstrap.php';

$test_class            = sfConfig::get('app_sfModerationPlugin_testmultiple_class',            'ModerationTest');
$test_title_column     = sfConfig::get('app_sfModerationPlugin_test_title_column',             'Title');
$test_status_column    = sfConfig::get('app_sfModerationPlugin_testmultiple_status_column',    'mod_status');
$test_useremail_column = sfConfig::get('app_sfModerationPlugin_testmultiple_useremail_column', 'UserEmail');

$test_watch_columns = sfConfig::get('app_sfModerationPlugin_testmultiple_watch_columns', array( 'title', 'user_name', 'url'));
if (!$test_contents = sfConfig::get('app_sfModerationPlugin_testmultiple_contents'))
{
  $test_contents = array(
    'title' => array(
      'code'           => 'test-title',
      'content_getter' => 'getTitle'
     ),
    'url' => array(
      'code'           => 'test-url',
      'content_getter' => 'getUrl'
     ),
    'user_name' => array(
      'code'           => 'test-username',
      'content_getter' => 'getUserName'
     ),
   );
}

$test_peer_class = $test_class.'Peer';

// cleanup database
call_user_func(array($test_peer_class, 'doDeleteAll'));

// register behavior on test object
sfPropelBehavior::add($test_class, array(sfPropelModerationBehavior::BEHAVIOR_NAME => array(
  'title_getter'     => 'get'.$test_title_column,
  'status_column'    => $test_status_column,
  'is_multiple'      => 'true',
  'watch_columns'    => $test_watch_columns,
  'contents'         => $test_contents
)));


// Now we can start to test
$t = new lime_test(12, new lime_output_color());

$t->diag('new methods');
$methods = array(
  'tagAsUnsafe',
  'tagAsSafe',
  'getModerationStatus',
  'setModerationStatus',
  'getModerationStatusString',
  'setModerationStatusString',
  'getModerationComment',
  'setModerationComment',
  'getModeratedContentFromObject'
);
foreach ($methods as $method)
{
  $t->ok(is_callable($test_class, $method), sprintf('Behavior adds a new %s() method to the object class', $method));
}

call_user_func(array($test_peer_class, 'doDeleteAll'));

$t->diag('monitoring status methods');
$t->is(sfPropelModerationBehavior::getMonitoredColumns($test_class), array_keys($test_contents));
sfConfig::set('app_sfModerationPlugin_is_monitored', true);
$t->ok(sfPropelModerationBehavior::isMonitored($test_class), 'a multiple class is monitored by default');
sfConfig::set('propel_behavior_moderation_'.$test_class.'_is_monitored', false);
$t->ok(!sfPropelModerationBehavior::isMonitored($test_class), 'you can disable a multiple class on an individual base');
