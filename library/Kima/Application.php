<?php
/**
 * Namespace Kima
 */
namespace Kima;

/**
 * Namespaces to use
 */
use \Kima\Action;
use \Kima\Config;
use \Kima\Http\Request;

/**
 * Application
 *
 * Framework Application
 * @package Kima
 */
class Application
{

    /**
     * instance
     * @var Kima_Application
     */
    private static $_instance;

    /**
     * config
     * @var array
     */
    private static $_config;

    /**
     * module
     */
    private static $_module;

    /**
     * controller
     * @var array
     */
    private static $_controller;

    /**
     * action
     * @var array
     */
    private static $_action;

    /**
     * constructor
     */
    private function __construct()
    {
        // register the auto load function
        spl_autoload_register('Kima\Application::autoload');
    }

    /**
     * gets the Application instance
     * @return Kima_Application
     */
    public static function get_instance()
    {
        isset(self::$_instance) || self::$_instance = new self;
        return self::$_instance;
    }

    /**
     * auto load function
     * @param string $class
     * @see http://php.net/manual/en/language.oop5.autoload.php
     */
    protected static function autoload($class)
    {
        // get the required file
        require_once str_replace('\\', '/', $class).'.php';
    }

    /**
     * run the application
     * @return void
     */
    public static function run()
    {
        // get the config
        $config = self::get_config();

        // get the controller and action from the request
        $controller = Request::get('controller', 'index');
        $action = Request::get('action', 'index');
        $module = getenv('MODULE') ? getenv('MODULE') : null;

        // set module, controller and action
        self::set_module($module);
        self::set_controller($controller);
        self::set_action($action);

        // run the action
        $action = new Action();
    }

    /**
     * returns the application config
     * @return array
     */
    public static function get_config()
    {
        return self::$_config;
    }

    /**
     * sets the config
     * @param string $path
     */
    public static function set_config($path)
    {
        // set the application config
        $config = new Config($path);

        // add the model to the include path
        set_include_path(implode(PATH_SEPARATOR,
            array(realpath($config->application['folder'] . '/model'), get_include_path())));

        self::$_config = $config;
        return self::$_instance;
    }

    /**
     * returns the application module
     * @return string
     */
    public static function get_module()
    {
        return self::$_module;
    }

    /**
     * sets the application module
     * @param string $module
     */
    public static function set_module($module)
    {
        // set the application module
        self::$_module = $module;
        return self::$_module;
    }

    /**
     * returns the application controller
     * @return string
     */
    public static function get_controller()
    {
        return self::$_controller;
    }

    /**
     * sets the application controller
     * @param string $controller
     */
    public static function set_controller($controller)
    {
        // set the application controller
        self::$_controller = $controller;
        return self::$_instance;
    }

    /**
     * returns the application action
     * @return string
     */
    public static function get_action()
    {
        return self::$_action;
    }

    /**
     * sets the action
     * @param string $action
     */
    public static function set_action($action)
    {
        // set the application action
        self::$_action = $action;
        return self::$_instance;
    }

}