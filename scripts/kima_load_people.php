<?php
/**
 * Namespaces to use
 */
use \Kima\Application;
use \Kima\Database;
use \Kima\Error;

ini_set("memory_limit","2000M");

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

$people_file = ROOT_FOLDER . '/data/file/padron_completo/PADRON_COMPLETO.txt';

if (!is_readable($people_file)) {
    Error::set('kima_load_data:', 'Cannot access file' . $people_file);    
}

$people = file($people_file);
    
foreach ($people as $person) {
    list($id_person, $id_location, $genre, $id_expiration_date, $vote_board, $name, $last_name, $surname) = 
        explode(',', utf8_encode($person));

    $db = Database::get_instance();
    
    $id_person = $db->escape(intval(trim($id_person)));
    $id_location = $db->escape(intval(trim($id_location)));
    $genre = $db->escape(intval(trim($genre)));
    $id_expiration_date = $db->escape(intval(trim($id_expiration_date)));
    $name = $db->escape(mb_convert_case(trim($name), MB_CASE_TITLE, 'UTF-8'));
    $last_name = $db->escape(mb_convert_case(trim($last_name), MB_CASE_TITLE, 'UTF-8'));
    $surname = $db->escape(mb_convert_case(trim($surname), MB_CASE_TITLE, 'UTF-8'));
    
    $query = 'CALL save_person(' . 
        $id_person . ', ' .
        $id_location . ', ' .
        $genre . ', ' .
        $id_expiration_date . ', ' . 
        '"' . $name . '",' .
        '"' . $last_name . '", ' .
        '"' . $surname . '")';

    $db->execute($query, false);
}