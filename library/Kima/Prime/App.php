<?php
namespace Kima\Prime;

use DDTrace\GlobalTracer;
use DDTrace\NoopTracer;
use DDTrace\Tag;
use Kima\Error;
use Kima\Http\Request;

/**
 * Kima Prime App
 * Entry point for apps using Kima with the front controller pattern
 * Example: App::get_instance()->run(['/' => 'Index']);
 */
class App
{
    /**
     * instance
     *
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
     *
     * @var array
     */
    private $config;

    /**
     * module
     *
     * @var string
     */
    private $module;

    /**
     * controller
     *
     * @var string
     */
    private $controller;

    /**
     * method
     *
     * @var string
     */
    private $method;

    /**
     * Current request language
     *
     * @var string
     */
    private $language;

    /**
     * Default time zone
     *
     * @var string
     */
    private $time_zone;

    /**
     * Whether the connection is secure or not
     *
     * @var bool
     */
    private $is_https;

    /**
     * Enforces the controller to be https
     *
     * @var bool
     */
    private $enforce_https = false;

    /**
     * Application predispatcher class
     *
     * @var string
     */
    private $predispatcher;

    /**
     * Individual controllers that should be always https
     *
     * @var array
     */
    private $https_controllers = [];

    /**
     * Sets the base position for the url routes
     *
     * @var int
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
     * Setup the basic application config
     *
     * @param string $custom_config a custom config file
     * @param bool   $skip_config
     *
     * @return App
     */
    public function setup($custom_config = null, $skip_config = false): App
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

        // Sets the config only if it isn't skipped
        if (!$skip_config) {
            $this->set_config($custom_config);
        }

        // set the default language
        $lang_config = $this->get_config()->get('language');
        if (isset($lang_config) && isset($lang_config['default'])) {
            $this->set_language($lang_config['default']);
        }

        return $this;
    }

    /**
     * Get the application instance
     *
     * @return App
     */
    public static function get_instance(): App
    {
        isset(self::$instance) || self::$instance = new self();

        return self::$instance;
    }

    /**
     * Run the application
     *
     * @param array  $urls
     * @param string $custom_config a custom config file
     *
     * @return Action
     */
    public function run(array $urls, $custom_config = null)
    {
        $this->set_config($custom_config);

        // Sets the datadog tracer config
        $this->setup_datadog();

        $this->setup($custom_config, true);

        $action = (new Action($urls))->run();
        
        // Finishes the span execution after action run
        GlobalTracer::get()->getRootScope()->getSpan()->finish();

        return $action;
    }

    /**
     * Return the application config
     *
     * @return Config
     */
    public function get_config()
    {
        return $this->config;
    }

    /**
     * Set the config
     *
     * @param string $custom_config
     *
     * @return App
     */
    public function set_config($custom_config = null): App
    {
        $this->config = new Config($custom_config);

        return $this;
    }

    /**
     * Returns the application module
     *
     * @return string
     */
    public function get_module()
    {
        return $this->module;
    }

    /**
     * Set the application module
     *
     * @param string $module
     *
     * @return App
     */
    public function set_module($module): App
    {
        $this->module = (string) $module;

        return $this;
    }

    /**
     * Return the application controller
     *
     * @return string
     */
    public function get_controller()
    {
        return $this->controller;
    }

    /**
     * Set the application controller
     *
     * @param string $controller
     *
     * @return App
     */
    public function set_controller($controller): App
    {
        $this->controller = (string) $controller;

        return $this;
    }

    /**
     * Returns the application method
     *
     * @return string
     */
    public function get_method()
    {
        return $this->method;
    }

    /**
     * Sets the method
     *
     * @param string $method
     *
     * @return App
     */
    public function set_method($method): App
    {
        $this->method = (string) $method;

        return $this;
    }

    /**
     * Returns the url base position for routing
     *
     * @return int
     */
    public function get_url_base_pos()
    {
        return $this->url_base_pos;
    }

    /**
     * Sets the url routes starting position
     *
     * @param int $url_base_pos
     *
     * @return App
     */
    public function set_url_base_pos($url_base_pos): App
    {
        $this->url_base_pos = (string) $url_base_pos;

        return $this;
    }

    /**
     * Return the application language
     *
     * @return string
     */
    public function get_language()
    {
        return $this->language;
    }

    /**
     * Sets the language
     *
     * @param string $language
     *
     * @return App
     */
    public function set_language($language): App
    {
        $this->language = (string) $language;

        return $this;
    }

    /**
     * Sets the default time zone
     *
     * @param string $time_zone
     *
     * @return App
     */
    public function set_time_zone($time_zone): App
    {
        $this->time_zone = (string) $time_zone;

        return $this;
    }

    /**
     * Gets the application default time zone
     *
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
     *
     * @return bool
     */
    public function is_https()
    {
        return $this->is_https;
    }

    /**
     * Makes all request https by default
     *
     * @return App
     */
    public function enforce_https(): App
    {
        $this->enforce_https = true;

        return $this;
    }

    /**
     * Returns whether the request should be https or not
     *
     * @return bool
     */
    public function is_https_enforced()
    {
        return $this->enforce_https;
    }

    /**
     * Sets the controllers that should be always https
     *
     * @param array $controllers
     *
     * @return App
     */
    public function set_https_controllers(array $controllers): App
    {
        $this->https_controllers = $controllers;

        return $this;
    }

    /**
     * Gets the controllers that should be always https
     *
     * @return array
     */
    public function get_https_controllers()
    {
        return $this->https_controllers;
    }

    /**
     * Set an http error for the page
     *
     * @param int $status_code
     */
    public function set_http_error($status_code)
    {
        // set the status code
        http_response_code($status_code);

        $error_path = 'Controller\\Error';

        $module = $this->get_module();
        if (!empty($module)) {
            $error_path = 'Module\\' . ucfirst(strtolower($module)) . '\\' . $error_path;
        }

        $this->set_controller('Error')->set_method('get');
        $controller = new $error_path();
        $controller->get();
        exit;
    }

    /**
     * Sets the predispatcher class
     *
     * @param string $predispatcher
     *
     * @return App
     */
    public function set_predispatcher($predispatcher): App
    {
        $this->predispatcher = (string) $predispatcher;

        return $this;
    }

    /**
     * Gets the predispatcher class
     *
     * @return string
     */
    public function get_predispatcher()
    {
        return $this->predispatcher;
    }

    /**
     * Gets the application_folder
     *
     * @return string
     */
    public function get_application_folder()
    {
        return $this->application_folder;
    }

    /**
     * Gets the controller_folder
     *
     * @return string
     */
    public function get_controller_folder()
    {
        return $this->controller_folder;
    }

    /**
     * Gets the module_folder
     *
     * @return string
     */
    public function get_module_folder()
    {
        return $this->module_folder;
    }

    /**
     * Gets the view_folder
     *
     * @return string
     */
    public function get_view_folder()
    {
        return $this->view_folder;
    }

    /**
     * Gets the l10n_folder
     *
     * @return string
     */
    public function get_l10n_folder()
    {
        return $this->l10n_folder;
    }

    /**
     * Set whether the connections is https or not
     *
     * @return App
     */
    private function set_is_https(): App
    {
        // get values from sever
        $https = Request::server('HTTPS');
        $port = Request::server('SERVER_PORT');

        // check if https is on
        $this->is_https = (!empty($https) && 'off' !== $https || 443 == $port);

        return $this;
    }

    /**
     * Sets the application folders
     *
     * @return App
     */
    private function set_application_folders(): App
    {
        $this->application_folder = ROOT_FOLDER . '/application/';
        $this->controller_folder = $this->application_folder . 'controller/';
        $this->module_folder = $this->application_folder . 'module/';
        $this->view_folder = $this->application_folder . 'view/';
        $this->l10n_folder = ROOT_FOLDER . '/resource/l10n/';

        return $this;
    }

    /**
     * Setups the datadog config
     *
     * @return App
     */
    private function setup_datadog(): App
    {
        $tracing_config = $this->config->get('tracing');

        // If the config isn't setted or isn't enabled then sets the tracer as a NoopTracer
        if (!isset($tracing_config) || empty($tracing_config['enabled'])) {
            GlobalTracer::set(NoopTracer::create());

            return $this;
        };

        // Gets the span in order to be used whenever it's necessary
        $span = GlobalTracer::get()->getRootScope()->getSpan();

        // Overwrites the operation name only if it exists
        if (!empty($tracing_config['weboperation']['name'])) {
            $span->overwriteOperationName($tracing_config['weboperation']['name']);
        }

        // Overwrites the service name only if it exists
        if (!empty($tracing_config['webservice']['name'])) {
            $span->setTag(Tag::SERVICE_NAME, $tracing_config['webservice']['name']);
        }

        // Sets the environment tag only if it exists
        if (!empty($tracing_config['env'])) {
            $span->setTag(Tag::ENV, $tracing_config['env']);
        }

        return $this;
    }
}
