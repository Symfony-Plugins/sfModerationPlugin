<?php
/*
 * This file is part of the sfModerationPlugin package.
 * 
 * (c) 2007 Francois Zaninotto <francois.zaninotto@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Unit tests for the sfModerationPlugin plugin.
 *
 * In order to run the tests in your context, you have to copy this file in a symfony test directory
 * and configure it appropriately (see the "configuration" section at the beginning of the file)
 *  
 * @author   Francois Zaninotto <francois.zaninotto@symfony-project.com>
 */

/* NB: If you want to avoid configuration, add the following table to your schema:
moderation_test:
  id:
  title:       varchar(255)
  content:     longvarchar
  url:         longvarchar
  extra:       varchar(255) 
  user_name:   varchar(255)
  user_email:  varchar(50)
  mod_status:  { type: integer, default: 1, index: true }
  mod_comment: varchar(255)
  updated_at:
*/

// configuration
$sf_root_dir = realpath(dirname(__FILE__).'/../../../../');
$apps_dir = glob($sf_root_dir.'/apps/*', GLOB_ONLYDIR);
$app = substr($apps_dir[0], 
              strrpos($apps_dir[0], DIRECTORY_SEPARATOR) + 1, 
              strlen($apps_dir[0]));
if (!$app)
{
  throw new Exception('No app has been detected in this project');
}

// -- path to the symfony project where the plugin resides
$sf_path = dirname(__FILE__).'/../../../..';
 
// functional bootstrap
include($sf_path . '/test/bootstrap/functional.php');
require_once(sfConfig::get('sf_symfony_lib_dir').'/vendor/lime/lime.php'); 


// Now we can start to test
$h = new lime_harness(new lime_output_color());
$h->base_dir = dirname(__FILE__) . '/all';

// register all tests
$finder = sfFinder::type('file')->ignore_version_control()->follow_link()->name('*Test.php');
$h->register($finder->in($h->base_dir));

$h->run();