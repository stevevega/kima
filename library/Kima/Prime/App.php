<?php
/**
 * Kima Application
 * @author Steve Vega
 */
namespace Kima\Prime;

use \Kima\Action;
use \Kima\Config;
use \Kima\Error;
use \Kima\Http\Request;

/**
 * Application
 * Kima Prime Application class
 */
class App
{

    /**
     * Error messages
     */
    const ERROR_NO_DEFAULT_LANGUAGE = 'Default language has not been set';
    const ERROR_NO_DEFAULT_LANGUAGE_TYPE = 'Default language type has not been set';

    /**
     * Application default language type
     */
    const LANG_DEFAULT_EXPLICIT = 'explicit';
    const LANG_DEFAULT_IMPLICIT = 'implicit';

    /**
     * instance
     * @var \Kima\Base\App
     */
    private static $instance;

    /**
     * Folder paths
     */
    private $application_folder;
    private $controller_folder;
    private $model_folder;
    private $module_folder;
    private $view_folder;
    private $l10n_folder;
    private $library_folder;
    private $kima_folder;

    /**
     * config
     * @var array
     */
    private $config;

    /**
     * Application environment
     * @var string
     */
    private $environment;

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
     * The default application language type
     * @var string
     */
    private $default_language_type;

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
    private $enforce_https = false;

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
     * Construct
     */
    private function __construct()
    {
        $this->set_application_folders();

        // register the auto load function
        spl_autoload_register(array('self', 'autoload'));
    }

    /**
     * Get the application instance
     * @return App
     */
    public static function get_instance()
    {
        isset(self::$instance) || self::$instance = new self;

        return self::$instance;
    }

    /**
     * auto load function
     * @param  string $class
     * @see    http://php.net/manual/en/language.oop5.autoload.php
     * @return bool
     */
    protected static function autoload($class)
    {
        // get the required file
        $filename = str_replace('\\', '/', $class) . '.php';

        // load file
        $app = self::get_instance();
        $include_paths = [$app->library_folder, $app->model_folder, $app->kima_folder];
        $app->load_class($include_paths, $filename);

        return true;
    }

    /**
     * Setup the basic application config
     * @return App
     */
    public function setup()
    {
        // get the module and HTTP method
        switch (true) {
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
        $this->set_module($module);
        $this->set_method($method);
        $this->set_is_https();

        // set the config
        return $this->set_config();
    }

    /**
     * Run the application
     * @param  array  $urls
     * @return Action
     */
    public function run(array $urls)
    {
        // setup the application
        $this->setup();

        // run the action
        return new Action($urls);
    }

    /**
     * Return the application config
     * @return Config
     */
    public function get_config()
    {
        return $this->config;
    }

    /**
     * Set the config
     * @param  string $path
     * @return App
     */
    public function set_config($path = '')
    {
        $this->config = new Config($path);

        return $this;
    }

    /**
     * Gets the app environment
     * @return string
     */
    public function get_environment()
    {
        if (isset($this->environment)) {
            return $this->environment;
        }

        // get the environment
        switch (true) {
            case getenv('ENVIRONMENT'):
                $this->environment = getenv('ENVIRONMENT');
                break;
            case !empty($_SERVER['ENVIRONMENT']):
                $this->environment = $_SERVER['ENVIRONMENT'];
                break;
            default:
                $this->environment = 'default';
        }

        return $this->environment;
    }

    /**
     * Returns the application module
     * @return string
     */
    public function get_module()
    {
        return $this->module;
    }

    /**
     * Set the application module
     * @param  string $module
     * @return App
     */
    public function set_module($module)
    {
        $this->module = (string) $module;

        return $this;
    }

    /**
     * Return the application controller
     * @return string
     */
    public function get_controller()
    {
        return $this->controller;
    }

    /**
     * Set the application controller
     * @param  string $controller
     * @return App
     */
    public function set_controller($controller)
    {
        $this->controller = (string) $controller;

        return $this;
    }

    /**
     * Returns the application method
     * @return string
     */
    public function get_method()
    {
        return $this->method;
    }

    /**
     * Sets the method
     * @param  string $method
     * @return App
     */
    public function set_method($method)
    {
        $this->method = (string) $method;

        return $this;
    }

    /**
     * Return whether a language is available or not
     * @param  string  $language
     * @return boolean
     */
    public function is_language_available($language)
    {
        return in_array($language, $this->get_available_languages());
    }

    /**
     * Return the application language
     * @return string
     */
    public function get_language($include_implicit = true)
    {
        return (self::LANG_DEFAULT_IMPLICIT === $this->default_language_type
                && !$include_implicit
                && $this->language === $this->default_language)
            ? null
            : $this->language;
    }

    /**
     * Sets the language
     * @param  string $language
     * @return App
     */
    public function set_language($language)
    {
        $this->language = (string) $language;

        return $this;
    }

    /**
     * Sets the default language
     * @param  string $language
     * @return App
     */
    public function set_default_language($language)
    {
        $this->default_language = (string) $language;

        return $this;
    }

    /**
     * Gets the application default language
     * @return string
     */
    public function get_default_language()
    {
        // set the default language type if not set already
        if (empty($this->default_language_type)) {
            $this->set_default_language_type();
        }

        // check if the default language was already set
        if (!empty($this->default_language)) {
            return $this->default_language;
        }

        $config = $this->get_config()->language;
        switch (true) {
            case Request::env('LANGUAGE_DEFAULT'):
                $this->default_language = Request::env('LANGUAGE_DEFAULT');
                break;
            case Request::server('LANGUAGE_DEFAULT'):
                $this->default_language = Request::server('LANGUAGE_DEFAULT');
                break;
            case !empty($config['default'])
                && !empty($config['default']['value']):
                $this->default_language = $config['default']['value'];
                break;
            default:
                Error::set(self::ERROR_NO_DEFAULT_LANGUAGE);
        }

        return $this->default_language;
    }

    /**
     * Sets all the available languages in the application
     * @param  array $languages
     * @return App
     */
    public function set_available_languages(array $languages)
    {
        $this->available_languages = $languages;

        return $this;
    }

    /**
     * Gets all the available languages in the application
     * @return array
     */
    public function get_available_languages()
    {
        if (!empty($this->available_languages)) {
            return $this->available_languages;
        }

        $config = $this->get_config()->language;

        switch (true) {
            case Request::env('LANGUAGES_AVAILABLE'):
                $languages = Request::env('LANGUAGES_AVAILABLE');
                break;
            case Request::server('LANGUAGES_AVAILABLE'):
                $languages = Request::server('LANGUAGES_AVAILABLE');
                break;
            case !empty($config['available']):
                $languages = $config['available'];
                break;
            default:
                $languages = '';
        }

        $this->available_languages = explode(',', $languages);

        return $this->available_languages;
    }

    /**
     * Sets the default time zone
     * @param  string $time_zone
     * @return App
     */
    public function set_time_zone($time_zone)
    {
        $this->time_zone = (string) $time_zone;

        return $this;
    }

    /**
     * Gets the application default time zone
     * @return string
     */
    public function get_time_zone()
    {
        return empty($this->time_zone)
            ? date_default_timezone_get()
            : $this->time_zone;
    }

    /**
     * Returns whether is a secure connection or not
     * @return boolean
     */
    public function is_https()
    {
        return $this->is_https;
    }

    /**
     * Set whether the connections is https or not
     * @return App
     */
    private function set_is_https()
    {
        // get values from sever
        $https = Request::server('HTTPS');
        $port = Request::server('SERVER_PORT');

        // check if https is on
        $this->is_https = (!empty($https) && 'off' !== $https || 443 == $port);

        return $this;
    }

    /**
     * Makes all request https by default
     * @return App
     */
    public function enforce_https()
    {
        $this->enforce_https = true;

        return $this;
    }

    /**
     * Returns whether the request should be https or not
     * @return boolean
     */
    public function is_https_enforced()
    {
        return $this->enforce_https;
    }

    /**
     * Sets the controllers that should be always https
     * @param  array $controllers
     * @return App
     */
    public function set_https_controllers(array $controllers)
    {
        $this->https_controllers = $controllers;

        return $this;
    }

    /**
     * Gets the controllers that should be always https
     * @return array
     */
    public function get_https_controllers()
    {
        return $this->https_controllers;
    }

    /**
     * Set an http error for the page
     * @param int $status_code
     */
    public function set_http_error($status_code)
    {
        // set the status code
        http_response_code($status_code);

        $module = $this->get_module();

        //  the controller path
        $controller_folder = $module
            ? $this->module_folder . '/' . $module . '/controller/'
            : $this->controller_folder;

        $this->controller = 'Error';
        require_once $controller_folder . $this->controller . '.php';

        $controller_obj = new \Error();
        $controller_obj->get();
        exit;
    }

    /**
     * Gets the application default language type
     * @return string
     */
    public function get_default_language_type()
    {
        return $this->default_language_type;
    }

    /**
     * Sets the predispatcher class
     * @param  string $predispatcher
     * @return App
     */
    public function set_predispatcher($predispatcher)
    {
        $this->predispatcher = (string) $predispatcher;

        return $this;
    }

    /**
     * Gets the predispatcher class
     * @return string
     */
    public function get_predispatcher()
    {
        return $this->predispatcher;
    }

    /**
     * Gets the application_folder
     * @return string
     */
    public function get_application_folder()
    {
        return $this->application_folder;
    }

    /**
     * Gets the controller_folder
     * @return string
     */
    public function get_controller_folder()
    {
        return $this->controller_folder;
    }

    /**
     * Gets the model_folder
     * @return string
     */
    public function get_model_folder()
    {
        return $this->model_folder;
    }

    /**
     * Gets the module_folder
     * @return string
     */
    public function get_module_folder()
    {
        return $this->module_folder;
    }

    /**
     * Gets the view_folder
     * @return string
     */
    public function get_view_folder()
    {
        return $this->view_folder;
    }

    /**
     * Gets the l10n_folder
     * @return string
     */
    public function get_l10n_folder()
    {
        return $this->l10n_folder;
    }

    /**
     * Gets the library_folder
     * @return string
     */
    public function get_library_folder()
    {
        return $this->library_folder;
    }

    /**
     * Gets the kima_folder
     * @return string
     */
    public function get_kima_folder()
    {
        return $this->kima_folder;
    }

    /**
     * Sets the application folders
     * @return Application
     */
    private function set_application_folders()
    {
        $this->application_folder = ROOT_FOLDER . '/application/';
        $this->controller_folder = $this->application_folder . 'controller/';
        $this->model_folder = $this->application_folder . 'model/';
        $this->module_folder = $this->application_folder . 'module/';
        $this->view_folder = $this->application_folder . 'view/';
        $this->l10n_folder = ROOT_FOLDER . '/resource/l10n/';
        $this->library_folder = ROOT_FOLDER . '/library/';
        $this->kima_folder = realpath(dirname(__FILE__) . '/..') . '/';

        return $this;
    }

    /**
     * Try loading a class from a list of include paths
     * It makes sure the file exists to avoid throwing errors
     * so other auto_loaders can be registered
     * @param  array   $include_paths
     * @param  string  $filename
     * @return boolean
     */
    private function load_class(array $include_paths, $filename)
    {
        // try the include paths
        foreach ($include_paths as $include_path) {
            $filepath = $include_path . $filename;
            if (file_exists($filepath)) {
                require_once $filepath;

                return true;
            }
        }

        // try the php include paths
        return false;
    }

    /**
     * Sets the language default type
     */
    private function set_default_language_type()
    {
        $config = $this->get_config()->language;
        if (empty($config['default']) || empty($config['default']['type'])) {
            Error::set(self::ERROR_NO_DEFAULT_LANGUAGE_TYPE);
        }

        // set the language type and valid types
        $type = $config['default']['type'];
        $types = [self::LANG_DEFAULT_IMPLICIT, self::LANG_DEFAULT_EXPLICIT];

        // validate the type set
        if (!in_array($type, $types)) {
            Error::set(sprintf(self::ERROR_INVALID_DEFAULT_LANGUAGE_TYPE, $type));
        }

        $this->default_language_type = $type;
    }

}
