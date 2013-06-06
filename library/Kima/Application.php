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
    private $config;

    /**
     * module
     * @var string
     */
    private $module;

    /**
     * controller
     * @var string
     */
    private $controller;

    /**
     * method
     * @var string
     */
    private $method;

    /**
     * Current request language
     * @var string
     */
    private $language;

    /**
     * The default application language
     * @var string
     */
    private $default_language;

    /**
     * The language prefix used in urls
     * @var string
     */
    private $language_url_prefix;

    /**
     * All the available languages in the application
     * @var array
     */
    private $available_languages = [];

    /**
     * Default time zone
     * @var string
     */
    private $time_zone;

    /**
     * Whether the connection is secure or not
     * @var boolean
     */
    private $is_https;

    /**
     * Enforces the controller to be https
     * @var boolean
     */
    private $enforce_https;

    /**
     * Application predispatcher class
     * @var string
     */
    private $predispatcher;

    /**
     * Individual controllers that should be always https
     * @var array
     */
    private $https_controllers = [];

    /**
     * Global default view params
     * @var array
     */
    private $view_params = [];

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
     * Setup the basic application config
     */
    public function setup()
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
        $app = self::get_instance();

        // set module, controller and action
        $app->set_module($module);
        $app->set_method($method);
        $app->set_is_https();

        // set the config
        $app->set_config();
    }

    /**
     * Run the application
     * @param array $urls
     */
    public function run(array $urls)
    {
        // setup the application
        $app = self::get_instance();
        $app->setup();

        // run the action
        $action = new Action($urls);
    }

    /**
     * Return the application config
     * @return array
     */
    public function get_config()
    {
        $app = self::get_instance();
        return $app->config;
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

        $app = self::get_instance();
        $app->config = $config;
        return $app;
    }

    /**
     * Return the application module
     * @return string
     */
    public static function get_module()
    {
        $app = self::get_instance();
        return $app->module;
    }

    /**
     * Set the application module
     * @param string $module
     */
    public static function set_module($module)
    {
        $app = self::get_instance();
        $app->module = (string)$module;
        return $app;
    }

    /**
     * Return the application controller
     * @return string
     */
    public static function get_controller()
    {
        $app = self::get_instance();
        return $app->controller;
    }

    /**
     * Set the application controller
     * @param string $controller
     */
    public static function set_controller($controller)
    {
        $app = self::get_instance();
        $app->controller = (string)$controller;
        return $app;
    }

    /**
     * Returns the application method
     * @return string
     */
    public static function get_method()
    {
        $app = self::get_instance();
        return $app->method;
    }

    /**
     * Set the method
     * @param string $method
     */
    public static function set_method($method)
    {
        $app = self::get_instance();
        $app->method = (string)$method;
        return $app;
    }

    /**
     * Return the application language
     * @return string
     */
    public static function get_language()
    {
        $app = self::get_instance();
        return $app->language;
    }

    /**
     * Returns the language prefix to be used in urls
     * @return string
     */
    public static function get_language_url_prefix()
    {
        $app = self::get_instance();
        return $app->language_url_prefix;
    }

    /**
     * Sets the language
     * @param string $language
     */
    public static function set_language($language)
    {
        $app = self::get_instance();
        $app->language = (string)$language;

        // set the url prefix depending on the language selected
        $app->language_url_prefix =
            $app->get_default_language() !== $language ? "/$language" : '';
        return $app;
    }

    /**
     * Sets the default language
     * @param string $language
     */
    public static function set_default_language($language)
    {
        $app = self::get_instance();
        $app->default_language = (string)$language;
        return $app;
    }

    /**
     * Gets the application default language
     * @return string
     */
    public static function get_default_language()
    {
        $app = self::get_instance();

        if (!empty($app->default_language))
        {
            return $app->default_language;
        }

        switch (true)
        {
            case Request::env('LANGUAGE_DEFAULT'):
                $language = Request::env('LANGUAGE_DEFAULT');
                break;
            case Request::server('LANGUAGE_DEFAULT'):
                $language = Request::server('LANGUAGE_DEFAULT');
                break;
            case property_exists($app->get_config(), 'language')
                && !empty($app->get_config()->language['default']):
                $language = $app->get_config()->language['default'];
                break;
            default:
                Error::set(self::ERROR_NO_DEFAULT_LANGUAGE);
        }

        $app->default_language = $language;
        return $app->default_language;
    }

    /**
     * Sets all the available languages in the application
     * @param array $languages
     */
    public static function set_available_languages(array $languages)
    {
        $app = self::get_instance();
        $app->available_languages = $languages;
        return $app;
    }

    /**
     * Gets all the available languages in the application
     * @return array
     */
    public static function get_available_languages()
    {
        $app = self::get_instance();

        if (!empty($app->available_languages))
        {
            return $app->available_languages;
        }

        switch (true)
        {
            case Request::env('LANGUAGES_AVAILABLE'):
                $languages = Request::env('LANGUAGES_AVAILABLE');
                break;
            case Request::server('LANGUAGES_AVAILABLE'):
                $languages = Request::server('LANGUAGES_AVAILABLE');
                break;
            case property_exists($app->get_config(), 'language')
                && !empty($app->get_config()->language['available']):
                $languages = $app->get_config()->language['available'];
                break;
            default:
                $languages = '';
        }

        $app->available_languages = explode(',', $languages);
        return $app->available_languages;
    }

    /**
     * Sets the default time zone
     * @param string $time_zone
     */
    public static function set_time_zone($time_zone)
    {
        $app = self::get_instance();
        $app->time_zone = $time_zone;
        return $app;
    }

    /**
     * Gets the application default time zone
     * @return string
     */
    public static function get_time_zone()
    {
        $app = self::get_instance();
        return empty($app->time_zone)
            ? date_default_timezone_get()
            : $app->time_zone;
    }

    /**
     * Sets view global default params to set
     * @param array $params
     */
    public static function set_view_params(array $params)
    {
        $app = self::get_instance();
        $app->view_params = array_merge($app->view_params, $params);
        return $app;
    }

    /**
     * Gets view global default params to set
     * @param array $params
     */
    public static function get_view_params()
    {
        $app = self::get_instance();
        return $app->view_params;
    }

    /**
     * Returns whether is a secure connection or not
     * @return boolean
     */
    public static function is_https()
    {
        $app = self::get_instance();
        return $app->is_https;
    }

    /**
     * Set whether the connections is https or not
     */
    private static function set_is_https()
    {
        $app = self::get_instance();

        // get values from sever
        $https = Request::server('HTTPS');
        $port = Request::server('SERVER_PORT');

        // check if https is on
        $app->is_https = !empty($https) && 'off' !== $https || 443 == $port
            ? true
            : false;
        return $app;
    }

    /**
     * Makes all request https by default
     */
    public static function enforce_https()
    {
        $app = self::get_instance();
        $app->enforce_https = true;
    }

    /**
     * Returns whether the request should be https or not
     * @return boolean
     */
    public static function is_https_enforced()
    {
        $app = self::get_instance();
        return empty($app->enforce_https) ? false : true;
    }

    /**
     * Sets the controllers that should be always https
     * @param array $controllers
     */
    public static function set_https_controllers(array $controllers)
    {
        $app = self::get_instance();
        $app->https_controllers = $controllers;
        return $app;
    }

    /**
     * Gets the controllers that should be always https
     * @return array
     */
    public static function get_https_controllers()
    {
        $app = self::get_instance();
        return $app->https_controllers;
    }

    /**
     * Set an http error for the page
     * @param int $status_code
     */
    public static function set_http_error($status_code)
    {
        // set the status code
        http_response_code($status_code);

        $application = Application::get_instance();
        $config = $application->get_config();
        $module = $application->get_module();

        //  the controller path
        $controller_folder = $module
            ? $config->module['folder'] . '/' . $module . '/controller'
            : $config->controller['folder'];

        $app = self::get_instance();
        $app->controller = 'Error';
        $controller_path = $controller_folder . '/Error.php';
        require_once $controller_path;

        $method = 'get';
        $controller_obj = new \Error();
        $controller_obj->$method();
        exit;
    }

    /**
     * Sets the predispatcher class
     * @param string $predispatcher
     */
    public static function set_predispatcher($predispatcher)
    {
        $app = self::get_instance();
        $app->predispatcher = $predispatcher;
        return $app;
    }

    /**
     * Gets the predispatcher class
     * @param string $predispatcher
     */
    public static function get_predispatcher()
    {
        $app = self::get_instance();
        return $app->predispatcher;
    }

}