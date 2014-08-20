<?php
/**
 * Kima Application
 * @author Steve Vega
 */
namespace Kima;

use \Kima\Http\Request;

/**
 * Application
 * Kima Application class
 */
class Application
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
     * @var \Kima\Application
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
        $filename = str_replace('\\', '/', $class) . '.php';

        // load file
        $app = self::get_instance();
        $include_paths = [$app->library_folder, $app->model_folder, $app->kima_folder];
        $app->load_class($include_paths, $filename);

        return true;
    }

    /**
     * Setup the basic application config
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
        return new Action($urls);
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
    public function set_config($path = '')
    {
        // set the application config
        $config = new Config($path);

        $app = self::get_instance();
        $app->config = $config;

        return $app;
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
     * Return the application module
     * @return string
     */
    public function get_module()
    {
        $app = self::get_instance();

        return $app->module;
    }

    /**
     * Set the application module
     * @param string $module
     */
    public function set_module($module)
    {
        $app = self::get_instance();
        $app->module = (string) $module;

        return $app;
    }

    /**
     * Return the application controller
     * @return string
     */
    public function get_controller()
    {
        $app = self::get_instance();

        return $app->controller;
    }

    /**
     * Set the application controller
     * @param string $controller
     */
    public function set_controller($controller)
    {
        $app = self::get_instance();
        $app->controller = (string) $controller;

        return $app;
    }

    /**
     * Returns the application method
     * @return string
     */
    public function get_method()
    {
        $app = self::get_instance();

        return $app->method;
    }

    /**
     * Set the method
     * @param string $method
     */
    public function set_method($method)
    {
        $app = self::get_instance();
        $app->method = (string) $method;

        return $app;
    }

    /**
     * Return whether a language is available or not
     * @param  string  $language
     * @return boolean
     */
    public function is_language_available($language)
    {
        $app = self::get_instance();

        return in_array($language, $app->get_available_languages());
    }

    /**
     * Return the application language
     * @return string
     */
    public function get_language($include_implicit = true)
    {
        $app = self::get_instance();

        return (self::LANG_DEFAULT_IMPLICIT === $app->default_language_type
                && !$include_implicit
                && $app->language === $app->default_language)
            ? null
            : $app->language;
    }

    /**
     * Sets the language
     * @param string $language
     */
    public function set_language($language)
    {
        $app = self::get_instance();
        $app->language = (string) $language;

        return $app;
    }

    /**
     * Sets the default language
     * @param string $language
     */
    public function set_default_language($language)
    {
        $app = self::get_instance();
        $app->default_language = (string) $language;

        return $app;
    }

    /**
     * Gets the application default language
     * @return string
     */
    public function get_default_language()
    {
        $app = self::get_instance();

        // set the default language type if not set already
        if (empty($app->default_language_type)) {
            $this->set_default_language_type();
        }

        // check if the default language was already set
        if (!empty($app->default_language)) {
            return $app->default_language;
        }

        $config = $app->get_config()->language;
        switch (true) {
            case Request::env('LANGUAGE_DEFAULT'):
                $language = Request::env('LANGUAGE_DEFAULT');
                break;
            case Request::server('LANGUAGE_DEFAULT'):
                $language = Request::server('LANGUAGE_DEFAULT');
                break;
            case !empty($config['default'])
                && !empty($config['default']['value']):
                $language = $config['default']['value'];
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
    public function set_available_languages(array $languages)
    {
        $app = self::get_instance();
        $app->available_languages = $languages;

        return $app;
    }

    /**
     * Gets all the available languages in the application
     * @return array
     */
    public function get_available_languages()
    {
        $app = self::get_instance();

        if (!empty($app->available_languages)) {
            return $app->available_languages;
        }

        $config = $app->get_config()->language;

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

        $app->available_languages = explode(',', $languages);

        return $app->available_languages;
    }

    /**
     * Sets the default time zone
     * @param string $time_zone
     */
    public function set_time_zone($time_zone)
    {
        $app = self::get_instance();
        $app->time_zone = $time_zone;

        return $app;
    }

    /**
     * Gets the application default time zone
     * @return string
     */
    public function get_time_zone()
    {
        $app = self::get_instance();

        return empty($app->time_zone)
            ? date_default_timezone_get()
            : $app->time_zone;
    }

    /**
     * Returns whether is a secure connection or not
     * @return boolean
     */
    public function is_https()
    {
        $app = self::get_instance();

        return $app->is_https;
    }

    /**
     * Set whether the connections is https or not
     */
    private function set_is_https()
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
    public function enforce_https()
    {
        $app = self::get_instance();
        $app->enforce_https = true;
    }

    /**
     * Returns whether the request should be https or not
     * @return boolean
     */
    public function is_https_enforced()
    {
        $app = self::get_instance();

        return empty($app->enforce_https) ? false : true;
    }

    /**
     * Sets the controllers that should be always https
     * @param array $controllers
     */
    public function set_https_controllers(array $controllers)
    {
        $app = self::get_instance();
        $app->https_controllers = $controllers;

        return $app;
    }

    /**
     * Gets the controllers that should be always https
     * @return array
     */
    public function get_https_controllers()
    {
        $app = self::get_instance();

        return $app->https_controllers;
    }

    /**
     * Set an http error for the page
     * @param int $status_code
     */
    public function set_http_error($status_code)
    {
        // set the status code
        http_response_code($status_code);

        $app = Application::get_instance();
        $module = $app->get_module();

        //  the controller path
        $controller_folder = $module
            ? $app->module_folder . '/' . $module . '/controller/'
            : $app->controller_folder;

        $app = self::get_instance();
        $app->controller = 'Error';
        $controller_path = $controller_folder . $app->controller . '.php';
        require_once $controller_path;

        $method = 'get';
        $controller_obj = new \Error();
        $controller_obj->$method();
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
     * @param string $predispatcher
     */
    public function set_predispatcher($predispatcher)
    {
        $app = self::get_instance();
        $app->predispatcher = $predispatcher;

        return $app;
    }

    /**
     * Gets the predispatcher class
     * @param string $predispatcher
     */
    public function get_predispatcher()
    {
        $app = self::get_instance();

        return $app->predispatcher;
    }

    /**
     * Gets the application_folder
     * @return string
     */
    public function get_application_folder()
    {
        $app = self::get_instance();

        return $app->application_folder;
    }

    /**
     * Gets the controller_folder
     * @return string
     */
    public function get_controller_folder()
    {
        $app = self::get_instance();

        return $app->controller_folder;
    }

    /**
     * Gets the model_folder
     * @return string
     */
    public function get_model_folder()
    {
        $app = self::get_instance();

        return $app->model_folder;
    }

    /**
     * Gets the module_folder
     * @return string
     */
    public function get_module_folder()
    {
        $app = self::get_instance();

        return $app->module_folder;
    }

    /**
     * Gets the view_folder
     * @return string
     */
    public function get_view_folder()
    {
        $app = self::get_instance();

        return $app->view_folder;
    }

    /**
     * Gets the l10n_folder
     * @return string
     */
    public function get_l10n_folder()
    {
        $app = self::get_instance();

        return $app->l10n_folder;
    }

    /**
     * Gets the library_folder
     * @return string
     */
    public function get_library_folder()
    {
        $app = self::get_instance();

        return $app->library_folder;
    }

    /**
     * Gets the kima_folder
     * @return string
     */
    public function get_kima_folder()
    {
        $app = self::get_instance();

        return $app->kima_folder;
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
        $app = Application::get_instance();
        $config = $app->get_config()->language;
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

        $app->default_language_type = $type;
    }

}
