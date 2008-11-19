<?php
/*
 * This file is part of the sfModerationPlugin package.
 * 
 * (c) 2007 Francois Zaninotto <francois.zaninotto@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

include dirname(__FILE__).'/../bootstrap.php';

// -- the model class and columns the tests should use
// 
$test_class          = sfConfig::get('app_sfModerationPlugin_test_class',       'ModerationTest');
$test_status_column  = sfConfig::get('app_sfModerationPlugin_test_status_column',  'mod_status');
$test_comment_column = sfConfig::get('app_sfModerationPlugin_test_comment_column', 'mod_comment');
$test_title_column   = sfConfig::get('app_sfModerationPlugin_test_title_column',   'Title');
$test_content_column = sfConfig::get('app_sfModerationPlugin_test_content_column', 'Content');
$test_url_column = sfConfig::get('app_sfModerationPlugin_test_url_column', 'Url');
$test_username_column = sfConfig::get('app_sfModerationPlugin_test_username_column', 'UserName');
$test_useremail_column = sfConfig::get('app_sfModerationPlugin_test_useremail_column', 'UserEmail');
$test_watch_columns = sfConfig::get('app_sfModerationPlugin_test_watch_columns', array( 'title', 'content', 'user_name', 'url'));

$test_peer_class = $test_class.'Peer';

// cleanup database
call_user_func(array($test_peer_class, 'doDeleteAll'));

// register behavior on test object
sfPropelBehavior::add($test_class, array(sfPropelModerationBehavior::BEHAVIOR_NAME => array(
  'status_column'      => $test_status_column,
  'comment_column'     => $test_comment_column,
  'title_getter'       => 'get'.$test_title_column,
  'content_getter'     => 'get'.$test_content_column,
  'url_getter'         => 'get'.$test_url_column,
  'username_getter'    => 'get'.$test_username_column,
  'useremail_getter'   => 'get'.$test_useremail_column,
  'watch_columns'      => $test_watch_columns,
)));


function remove_config_key($code)
{
  $whole_conf = sfConfig::getAll();
  sfConfig::clear();
  if (isset($whole_conf[$code]))
  {
    unset($whole_conf[$code]);
  }
  foreach ($whole_conf as $conf_key => $conf_value)
  {
    sfConfig::set($conf_key, $conf_value);
  }
}

// Now we can start to test
$extra_tests = (constant($test_peer_class.'::EXTRA')) ? 1 : 0;
$t = new lime_test(60 + $extra_tests, new lime_output_color());

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

$t->diag('getModerationStatus(), setModerationStatus(), getModerationComment(), setModerationComment()');
$item1 = new $test_class();
// Object must be modified to be saved
$item1->setByName($test_title_column, 'foo');
$item1->save();
$t->is($item1->getModerationStatus(), 1, 'getModerationStatus() returns the current moderation status (defaults to 1 = not checked)');
$item1->setModerationStatus(0);
$t->is($item1->getModerationStatus(), 0, 'setModerationStatus() changes the moderation status');
$t->diag('getModerationComment() and setModerationComment()');
$t->is($item1->getModerationComment(), '', 'getModerationComment() returns the current moderation comment (defaults to empty string)');
$t->is($item1->setModerationComment('Moderated because foo bar'), null, 'setModerationComment() returns null if the moderation comment column exists');
$t->is($item1->getModerationComment(), 'Moderated because foo bar', 'getModerationComment() returns the current moderation comment');


$t->diag('modifications of selection and count methods');
call_user_func(array($test_peer_class, 'doDeleteAll'));
$item1 = new $test_class();
$item1->setModerationStatus(sfPropelModerationBehavior::NOT_CHECKED);
$item1->save();
$id1 = $item1->getId();
$item2 = new $test_class();
$item2->setModerationStatus(sfPropelModerationBehavior::TAGGED_UNSAFE);
$item2->save();
$id2 = $item2->getId();
$item3 = new $test_class();
$item3->setModerationStatus(sfPropelModerationBehavior::TAGGED_SAFE);
$item3->save();
$id3 = $item3->getId();
$t->isa_ok(call_user_func(array($test_peer_class, 'retrieveByPk'), $id1), $test_class, 'retrieveByPk() finds unmarked records');
$t->isa_ok(call_user_func(array($test_peer_class, 'retrieveByPk'), $id2), 'NULL', 'retrieveByPk() doesn\'t find records marked as spam');
$t->isa_ok(call_user_func(array($test_peer_class, 'retrieveByPk'), $id3), $test_class, 'retrieveByPk() finds records marked as safe');
$t->is(call_user_func(array($test_peer_class, 'doCount'), new Criteria()), 2, 'doCount() ignores records marked as spam');
$t->is(count(call_user_func(array($test_peer_class, 'doSelect'), new Criteria())), 2, 'doSelect() ignores records marked as spam');

$t->diag('Publish/unpublish messages by default via app.yml');
sfConfig::set('app_sfModerationPlugin_display_treshold', sfPropelModerationBehavior::TAGGED_SAFE);
$t->isa_ok(call_user_func(array($test_peer_class, 'retrieveByPk'), $id1), 'NULL', 'Setting `app_sfModerationPlugin_display_treshold` to 0 ignores unchecked records from normal selection');
$t->is(call_user_func(array($test_peer_class, 'doCount'), new Criteria()), 1, 'Setting `app_sfModerationPlugin_display_treshold` to 0 ignores unchecked records from normal selection');
sfConfig::set('app_sfModerationPlugin_display_treshold', sfPropelModerationBehavior::NOT_CHECKED);
$t->isa_ok(call_user_func(array($test_peer_class, 'retrieveByPk'), $id1), $test_class, 'Setting `app_sfModerationPlugin_display_treshold` to 1 revelas unchecked records from normal selection');
$t->is(call_user_func(array($test_peer_class, 'doCount'), new Criteria()), 2, 'Setting `app_sfModerationPlugin_display_treshold` to 1 reveals unchecked records from normal selection');
sfConfig::set('app_sfModerationPlugin_display_treshold', sfPropelModerationBehavior::TAGGED_UNSAFE);
$t->isa_ok(call_user_func(array($test_peer_class, 'retrieveByPk'), $id1), $test_class, 'Setting `app_sfModerationPlugin_display_treshold` to 3 lets all records through');
$t->is(call_user_func(array($test_peer_class, 'doCount'), new Criteria()), 3, 'Setting `app_sfModerationPlugin_display_treshold` to 3 lets all records through');
sfConfig::set('app_sfModerationPlugin_display_treshold', sfPropelModerationBehavior::NOT_CHECKED);

$t->diag('tagAsUnsafe() and tagAsSafe()');
call_user_func(array($test_peer_class, 'doDeleteAll'));
$item1 = new $test_class();
// Object must be modified to be saved
$item1->setByName($test_title_column, 'foo');
$item1->save();
$id1 = $item1->getId();
$item1->tagAsUnsafe();
$t->isa_ok(call_user_func(array($test_peer_class, 'retrieveByPk'), $id1), 'NULL', 'tagAsUnsafe() changes the status to spam and saves the record');
$t->is($item1->getModerationStatus(), sfPropelModerationBehavior::TAGGED_UNSAFE, 'tagAsUnsafe() changes the status to spam and saves the record');
$item1->tagAsUnsafe(sfPropelModerationBehavior::AUTO_TAGGED_UNSAFE);
$t->is($item1->getModerationStatus(), sfPropelModerationBehavior::AUTO_TAGGED_UNSAFE, 'tagAsUnsafe(sfPropelModerationBehavior::AUTO_TAGGED_UNSAFE) changes the status to auto_spam and saves the record');
$item1->tagAsUnsafe(12);
$t->is($item1->getModerationStatus(), 12, 'tagAsUnsafe() accepts custom status codes');
$item2 = new $test_class();
// Object must be modified to be saved
$item2->setByName($test_title_column, 'foo');
$item2->save();
$id2 = $item2->getId();
$item2->tagAsSafe();
$t->isa_ok(call_user_func(array($test_peer_class, 'retrieveByPk'), $id2), $test_class, 'tagAsSafe() changes the status to safe and saves the record');
$t->is($item2->getModerationStatus(), sfPropelModerationBehavior::TAGGED_SAFE, 'tagAsSafe() changes the status to safe and saves the record');

$t->diag('sfPropelModerationBehavior::enable() and disable()');
call_user_func(array($test_peer_class, 'doDeleteAll'));
$item1 = new $test_class();
$item1->setModerationStatus(sfPropelModerationBehavior::NOT_CHECKED);
$item1->save();
$item2 = new $test_class();
$item2->setModerationStatus(sfPropelModerationBehavior::TAGGED_UNSAFE);
$item2->save();
$item3 = new $test_class();
$item3->setModerationStatus(sfPropelModerationBehavior::TAGGED_SAFE);
$item3->save();
$t->is(call_user_func(array($test_peer_class, 'doCount'), new Criteria()), 2, 'Spam check is enabled by default in selections');
sfPropelModerationBehavior::disable();
$t->is(call_user_func(array($test_peer_class, 'doCount'), new Criteria()), 3, 'Setting sfPropelModerationBehavior::disable() disables the spam check in selections');
sfPropelModerationBehavior::enable();
$t->is(call_user_func(array($test_peer_class, 'doCount'), new Criteria()), 2, 'Setting sfPropelModerationBehavior::enable() enables the spam check in selections');

$t->diag('Monitoring and sfModeratedContent');
remove_config_key('app_sfModerationPlugin_is_monitored');
sfConfig::set('app_sfModerationPlugin_monitoring_treshold', sfPropelModerationBehavior::TAGGED_SAFE);
$item1 = new $test_class();
$item1->setByName($test_title_column, 'foo');
$item1->setByName($test_content_column, 'bar');
$item1->setByName($test_url_column, 'http://foo');
$item1->setByName($test_username_column, 'mister bar');
$item1->setByName($test_useremail_column, 'foo@bar.com');
$item1->save();
$t->is($item1->getModeratedContentFromObject(), null, 'monitoring is not enabled by default and getModeratedContentFromObject() returns null');
sfConfig::set('app_sfModerationPlugin_is_monitored', true);
// Object must be modified to be saved
$item1->setByName($test_title_column, 'bar');
$item1->save();
$t->isa_ok($item1->getModeratedContentFromObject(), 'sfModeratedContent', 'per class monitoring is enabled by default');
sfConfig::set('propel_behavior_moderation_'.$test_class.'_is_monitored', false);
$t->is($item1->getModeratedContentFromObject(), null, 'monitoring can be disabled on a per class basis and getModeratedContentFromObject() returns null');
sfConfig::set('propel_behavior_moderation_'.$test_class.'_is_monitored', true);
// Object must be modified to be saved
$item1->setByName($test_title_column, 'foo');
$item1->save();
$moderated_content = $item1->getModeratedContentFromObject();
$t->isa_ok($moderated_content, 'sfModeratedContent', 'enabling monitoring both globally and on an object activates auto creation of an sfModeratedContent object each time a monitored object is saved');
$t->is($moderated_content->getObjectId(), $item1->getId(), 'sfModeratedContent object gets the Propel object id');
$t->is($moderated_content->getObjectModel(), $test_class, 'sfModeratedContent object gets the Propel object class');
$t->is($moderated_content->getTitle(), 'foo', 'sfModeratedContent object gets the Propel object title');
$t->is($moderated_content->getContent(), 'bar', 'sfModeratedContent object gets the Propel object content');
$t->is($moderated_content->getUrl(), 'http://foo', 'sfModeratedContent object gets the Propel object url');
$t->is($moderated_content->getUsername(), 'mister bar', 'sfModeratedContent object gets the Propel object user name');
$t->is($moderated_content->getUserEmail(), 'foo@bar.com', 'sfModeratedContent object gets the Propel object user email');
$item1->setModerationComment('This is foo');
$item1->tagAsUnsafe();
$moderated_content = $item1->getModeratedContentFromObject();
$t->is($moderated_content->getStatus(), sfPropelModerationBehavior::TAGGED_UNSAFE, 'sfModeratedContent object gets the Propel object moderation status');
$t->is($moderated_content->getComment(), 'This is foo', 'sfModeratedContent object gets the Propel object moderation comment');
$moderated_content_id = $moderated_content->getId();
$item1->save();
$moderated_content = $item1->getModeratedContentFromObject();
$t->is($moderated_content->getId(), $moderated_content_id, 'updating a moderated object updates the related sfModeratedContent and does not create a new one');
$item1->delete();
$moderated_content = $item1->getModeratedContentFromObject();
$t->is($item1->getModeratedContentFromObject(), null, 'deleting a monitored object also deletes its related sfModeratedContent');

$t->diag('Watched columns');
$watch_columns = sfPropelModerationBehavior::getWatchedColumns($test_class);
$common_columns = array_intersect($watch_columns, $test_watch_columns);
$t->is(count($common_columns), count($test_watch_columns), 'getWatchedColumns() includes all config watched columns');
$t->ok(in_array($test_status_column, $watch_columns), 'getWatchedColumns() includes config status column');
$t->ok(in_array($test_comment_column, $watch_columns), 'getWatchedColumns() includes config comment column');
if (constant($test_peer_class.'::EXTRA'))
{
  // We have extra non-moderated column
  sfConfig::set('app_sfModerationPlugin_is_monitored', true);
  sfConfig::set('propel_behavior_moderation_'.$test_class.'_is_monitored', true);
  $test_extra_column = 'Extra';
  $item1 = new $test_class();
  $item1->setByName($test_extra_column, 'foo');
  $item1->save();
  $t->is($item1->getModeratedContentFromObject(), null, 'Modifying non-watched columns doesn\'t trigger moderation and getModeratedContentFromObject() returns null');
}

$t->diag('sfModeratedContent dates');
$item1 = new $test_class();
// Object must be modified to be saved
$item1->setByName($test_title_column, 'foo');
$item1->save();
$item1_updated_at = $item1->getUpdatedAt('U');
$moderated_content = $item1->getModeratedContentFromObject();
$t->is($moderated_content->getObjectUpdatedAt('U'), $item1_updated_at, 'sfModeratedContent object gets the Propel object update date in object_updated_at');
$t->is($moderated_content->getModeratedAt('U'), $item1_updated_at, 'sfModeratedContent knows when it was last moderated in moderated_at');
sleep(1);
$item1->tagAsSafe();
$moderated_content = $item1->getModeratedContentFromObject();
$t->is($moderated_content->getObjectUpdatedAt('U'), $item1_updated_at, 'Tagging a propel object doesn\'t change its update date');
$t->isnt($moderated_content->getModeratedAt('U'), $item1_updated_at, 'Tagging a propel object changes the related sfModeratedContent moderated_at date');

$t->diag('Monitoring treshold');
sfConfig::set('app_sfModerationPlugin_monitoring_treshold', sfPropelModerationBehavior::AUTO_TAGGED_UNSAFE);
$item1 = new $test_class();
// Object must be modified to be saved
$item1->setByName($test_title_column, 'foo');
$item1->save();
$moderated_content = $item1->getModeratedContentFromObject();
$t->isa_ok($moderated_content, 'NULL', 'Saving an object with a moderation status below the treshold doesn\'t save a sfModeratedContent');
$item1->tagAsUnsafe();
$moderated_content = $item1->getModeratedContentFromObject();
$t->isa_ok($moderated_content, 'sfModeratedContent', 'Saving an object with a moderation status greater than the treshold saves a sfModeratedContent');
$item1->tagAsSafe();
$moderated_content = $item1->getModeratedContentFromObject();
$t->isa_ok($moderated_content, 'NULL', 'Declaring an object as safe removes the related sfModeratedContent with the default monitoring treshold');

$t->diag('Utility methods');
class myClass
{
  public function foo2()
  {
    return 'bar';
  }
  public function foo3()
  {
    return 'bar3';
  }

}
$myobject = new myClass();
$t->is(sfModeratedContentPeer::getOneOf($myobject, array('foo0', 'foo1', 'foo2', 'foo3')), 'bar', 'sfModeratedContentPeer::getOneOf() tests several methods on an object and returns the result of the first matching one');
