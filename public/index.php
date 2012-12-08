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
    array(realpath(ROOT_FOLDER . '/library'), get_include_path())));

// set the configuration path
$config_path = ROOT_FOLDER . '/application/config/application.ini';

$urls = array(
      '/' => 'index',
      '/index/([A-Za-z0-9]+)/' => 'index',
      '/cache/' => 'cache',
      '/paypal/' => 'paypal');

require_once('Kima/Application.php');
$application = Application::get_instance()
    ->set_config($config_path)
        ->run($urls);