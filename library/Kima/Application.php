<?php
/**
 * Kima Application
 * @author Steve Vega
 */
namespace Kima;

use \Kima\Action,
    \Kima\Config,
    \Kima\Http\Request;

/**
 * Application
 * Kima Application class
 */
class Application
{

    /**
     * instance
     * @var \Kima\Application
     */
    private static $instance;

    /**
     * config
     * @var array
     */
    private static $config;

    /**
     * module
     * @var string
     */
    private static $module;

    /**
     * controller
     * @var string
     */
    private static $controller;

    /**
     * method
     * @var string
     */
    private static $method;

    /**
     * language
     * @var string
     */
    private static $language;

    /**
     * Construct
     */
    private function __construct()
    {
        // register the auto load function
        spl_autoload_register('Kima\Application::autoload');
    }

    /**
     * Get the application instance
     * @return \Kima\Application
     */
    public static function get_instance()
    {
        isset(self::$instance) || self::$instance = new self;
        return self::$instance;
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
     * Run the application
     * @param array $urls
     */
    public static function run(array $urls)
    {
        // get the config
        $config = self::get_config();

        // get the module and HTTP method
        switch (true)
        {
            case getenv('MODULE'):
                $module = getenv('MODULE');
                break;
            case !empty($_SERVER['MODULE']):
                $module = $_SERVER['MODULE'];
                break;
            default:
                $module = null;
        }
        $method = strtolower(Request::get_method());

        // set module, controller and action
        self::set_module($module);
        self::set_method($method);

        // run the action
        $action = new Action($urls);
    }

    /**
     * Return the application config
     * @return array
     */
    public static function get_config()
    {
        return self::$config;
    }

    /**
     * Set the config
     * @param string $path
     */
    public static function set_config($path)
    {
        // set the application config
        $config = new Config($path);

        // add the model to the include path
        set_include_path(
            implode(PATH_SEPARATOR,
            [realpath($config->application['folder'] . '/model'), get_include_path()]));

        self::$config = $config;
        return self::$instance;
    }

    /**
     * Return the application module
     * @return string
     */
    public static function get_module()
    {
        return self::$module;
    }

    /**
     * Set the application module
     * @param string $module
     */
    public static function set_module($module)
    {
        self::$module = (string)$module;
        return self::$module;
    }

    /**
     * Return the application controller
     * @return string
     */
    public static function get_controller()
    {
        return self::$controller;
    }

    /**
     * Set the application controller
     * @param string $controller
     */
    public static function set_controller($controller)
    {
        self::$controller = (string)$controller;
        return self::$instance;
    }

    /**
     * Returns the application method
     * @return string
     */
    public static function get_method()
    {
        return self::$method;
    }

    /**
     * Set the method
     * @param string $method
     */
    public static function set_method($method)
    {
        self::$method = (string)$method;
        return self::$instance;
    }

    /**
     * Return the application language
     * @return string
     */
    public static function get_language()
    {
        return self::$language;
    }

    /**
     * Sets the language
     * @param string $language
     */
    public static function set_language($language)
    {
        self::$language = (string)$language;
        return self::$instance;
    }

}