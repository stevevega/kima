<?php
namespace Kima\Prime;

use \Kima\Action;
use \Kima\Error;
use \Kima\Http\Request;

/**
 * Kima Prime App
 * Entry point for apps using Kima with the front controller pattern
 * Example: App::get_instance()->run(['/' => 'Index']);
 */
class App
{

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
    private $module_folder;
    private $view_folder;
    private $l10n_folder;

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
     * Sets the base position for the url routes
     * @var integer
     */
    private $url_base_pos = 0;

    /**
     * Construct
     */
    private function __construct()
    {
        $this->set_application_folders();
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
     * Setup the basic application config
     * @param  string $custom_config a custom config file
     * @return App
     */
    public function setup($custom_config = null)
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
        $this->set_config($custom_config);

        // set the default language
        $lang_config = $this->get_config()->get('language');
        if (isset($lang_config) && isset($lang_config['default'])) {
            $this->set_language($lang_config['default']);
        }

        return $this;
    }

    /**
     * Run the application
     * @param  array  $urls
     * @param  string $custom_config a custom config file
     * @return Action
     */
    public function run(array $urls, $custom_config = null)
    {
        // setup the application
        $this->setup($custom_config);

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
     * @param  string $custom_config
     * @return App
     */
    public function set_config($custom_config = null)
    {
        $this->config = new Config($custom_config);

        return $this;
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
     * Returns the url base position for routing
     * @return int
     */
    public function get_url_base_pos()
    {
        return $this->url_base_pos;
    }

    /**
     * Sets the url routes starting position
     * @param  int $url_base_pos
     * @return App
     */
    public function set_url_base_pos($url_base_pos)
    {
        $this->url_base_pos = (string) $url_base_pos;

        return $this;
    }

    /**
     * Return the application language
     * @return string
     */
    public function get_language()
    {
        return $this->language;
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
     * Sets the application folders
     * @return Application
     */
    private function set_application_folders()
    {
        $this->application_folder = ROOT_FOLDER . '/application/';
        $this->controller_folder = $this->application_folder . 'controller/';
        $this->module_folder = $this->application_folder . 'module/';
        $this->view_folder = $this->application_folder . 'view/';
        $this->l10n_folder = ROOT_FOLDER . '/resource/l10n/';

        return $this;
    }
}
