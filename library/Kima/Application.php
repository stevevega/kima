<?php
/**
 * Kima Application
 * @author Steve Vega
 */
namespace Kima;

use \Kima\Action,
    \Kima\Config,
    \Kima\Error,
    \Kima\Http\Request,
    Exception;

/**
 * Application
 * Kima Application class
 */
class Application
{

    /**
     * Error messages
     */
    const ERROR_NO_BOOTSTRAP = 'Class Boostrap not defined in Bootstrap.php';
    const ERROR_NO_DEFAULT_LANGUAGE = 'Default language should be set in the application ini or as a server param "DEFAULT_LANGUAGE"';

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
     * The default language
     * @var string
     */
    private static $default_language;

    /**
     * The language prefix used in urls
     * @var string
     */
    private static $language_url_prefix;

    /**
     * Whether the connection is secure or not
     * @var boolean
     */
    private static $is_https;

    /**
     * Global default view params
     * @var array
     */
    private static $view_params = [];

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
        self::set_is_https();

        // set the config
        self::set_config();

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
    public static function set_config($path = '')
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
     * Returns the language prefix to be used in urls
     * @return string
     */
    public static function get_language_url_prefix()
    {
        return self::$language_url_prefix;
    }

    /**
     * Sets the language
     * @param string $language
     */
    public static function set_language($language)
    {
        self::$language = (string)$language;
        // set the url prefix depending on the language selected
        self::$language_url_prefix = self::get_default_language() !== $language ? "/$language" : '';
        return self::$instance;
    }

    /**
     * Gets the application default language
     * @return string
     */
    public static function get_default_language()
    {
        if (!empty(self::$default_language))
        {
            return self::$default_language;
        }

        switch (true)
        {
            case Request::env('LANGUAGE_DEFAULT'):
                $language = Request::env('LANGUAGE_DEFAULT');
                break;
            case Request::server('LANGUAGE_DEFAULT'):
                $language = Request::server('LANGUAGE_DEFAULT');
                break;
            case !empty(self::get_config()->language['default']):
                $language = self::get_config()->language['default'];
                break;
            default:
                Error::set(self::ERROR_NO_DEFAULT_LANGUAGE);
        }

        self::$default_language = $language;
        return $language;
    }

    /**
     * Sets view global default params to set
     * @param array $params
     */
    public static function set_view_params(array $params)
    {
        self::$view_params = $params;
    }

    /**
     * Gets view global default params to set
     * @param array $params
     */
    public static function get_view_params()
    {
        return self::$view_params;
    }

    /**
     * Returns whether is a secure connection or not
     * @return boolean
     */
    public static function is_https()
    {
        return self::$is_https;
    }

    /**
     * Set whether the connections is https or not
     */
    private static function set_is_https()
    {
        // get values from sever
        $https = Request::server('HTTPS');
        $port = Request::server('SERVER_PORT');

        // check if https is on
        self::$is_https = !empty($https) && 'off' !== $https || 443 == $port ? true : false;
    }

}