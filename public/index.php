<?php
/**
 * Namespaces to use
 */
use \Kima\Application;
use \Kima\Config;
use \Kima\Template;

// Define path to application directory
if (!defined('ROOT_FOLDER')) {
    define('ROOT_FOLDER', realpath(dirname(__FILE__) . '/..'));
}

// Set the library directory to the include path
set_include_path(implode(PATH_SEPARATOR,
    [realpath(ROOT_FOLDER . '/library'), get_include_path()]));

// set the configuration path
$config_path = ROOT_FOLDER . '/application/config/application.ini';

$urls = [
      '/' => 'Index',
      '/index/([A-Za-z0-9]+)/' => 'Index',
      '/cache/' => 'Cache',
      '/mongo/' => 'Test/MongoTest',
      '/paypal/' => 'Paypal',
      '/solr/' => 'Solr',
      '/person/' => 'PersonTest'];

require_once('Kima/Application.php');
$application = Application::get_instance()
    ->set_config($config_path)
        ->run($urls);