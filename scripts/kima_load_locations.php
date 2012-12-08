<?php
/**
 * Namespaces to use
 */
use \Kima\Application;
use \Kima\Database;
use \Kima\Error;

ini_set("memory_limit","1000M");

// Define path to application directory
if (!defined('ROOT_FOLDER')) {
    define('ROOT_FOLDER', realpath(dirname(__FILE__) . '/..'));
}

// Set the library directory to the include path
set_include_path(implode(PATH_SEPARATOR, 
    array(realpath(ROOT_FOLDER . '/library'), get_include_path())));
    
// set the configuration path
$config_path = ROOT_FOLDER . '/application/config/application.ini';

require_once('Kima/Application.php');
$application = Application::get_instance()
    ->set_config($config_path);

$location_file = ROOT_FOLDER . '/data/file/padron_completo/Distelec.txt';

if (!is_readable($location_file)) {
    Error::set('kima_load_data:', 'Cannot access file' . $location_file);    
}

$locations = file($location_file);
    
foreach ($locations as $location) {
    list($id_location, $state_name, $city_name, $location_name) = explode(',', utf8_encode($location));
    $country_name = 'Costa Rica';
    
    if ($state_name=='CONSULADO') {
        $country_name = $city_name;
        $state_name = $city_name = $location_name;
        $location_name = 'CONSULADO';
    }

    $db = Database::get_instance();
    
    $id_location = $db->escape(intval(trim($id_location)));
    $country_name = $db->escape(mb_convert_case(trim($country_name), MB_CASE_TITLE, 'UTF-8'));
    $state_name = $db->escape(mb_convert_case(trim($state_name), MB_CASE_TITLE, 'UTF-8'));
    $city_name = $db->escape(mb_convert_case(trim($city_name), MB_CASE_TITLE, 'UTF-8'));
    $location_name = $db->escape(mb_convert_case(trim($location_name), MB_CASE_TITLE, 'UTF-8'));
    
    $query = 'CALL save_location(' . 
        $id_location . ', ' . 
        '"' . $country_name . '",' .
        '"' . $state_name . '", ' .
        '"' . $city_name . '", ' .
        '"' . $location_name . '")';

    $db->execute($query, false);
}