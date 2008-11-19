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

// -- the model class and columns the tests should use
// 
$test_class          = sfConfig::get('app_sfModerationPlugin_test_class',       'ModerationTest');
$test_status_column  = sfConfig::get('app_sfModerationPlugin_test_status_column',  'mod_status');
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
  'title_getter'       => 'get'.$test_title_column,
  'content_getter'     => 'get'.$test_content_column,
  'url_getter'         => 'get'.$test_url_column,
  'username_getter'    => 'get'.$test_username_column,
  'useremail_getter'   => 'get'.$test_useremail_column
)));


// Now we can start to test
$t = new lime_test(4, new lime_output_color());

$t->diag('getModerationComment(), setModerationComment() and comments in sfModeratedContent');
$item1 = new $test_class();
// Object must be modified to be saved
$item1->setByName($test_title_column, 'foo');
$item1->save();

$t->is($item1->getModerationComment(), '', 'getModerationComment() returns the current moderation comment (defaults to empty string)');
$t->is($item1->setModerationComment('Moderated because foo bar'), null, 'setModerationComment() returns null if the moderation comment column exists');
$t->is($item1->getModerationComment(), 'Moderated because foo bar', 'getModerationComment() returns the current moderation comment');

sfConfig::set('app_sfModerationPlugin_is_monitored', true);

$item1->setModerationComment('This is foo');
$item1->tagAsUnsafe();
$moderated_content = $item1->getModeratedContentFromObject();
$t->is($moderated_content->getComment(), 'This is foo', 'sfModeratedContent object gets the Propel object moderation comment');