<?php
/**
 * Kima Action
 * @author Steve Vega
 */
namespace Kima\Prime;

use Kima\Error;
use Kima\Http\Redirector;
use Kima\Http\Request;
use Bootstrap;

/**
 * Action
 * Implementation of the Front Controller design pattern
 */
class Action
{
    /**
     * Error messages
     */
    const ERROR_NO_BOOTSTRAP = 'Class Boostrap not defined in Bootstrap.php';
    const ERROR_NO_CONTROLLER_FILE = 'Class file for "%s" is not accesible on "%s"';
    const ERROR_NO_CONTROLLER_INSTANCE = 'Object for "%s" is not an instance of \Kima\Prime\Controller';
    const ERROR_NO_MODULE_ROUTES = 'Routes for module "%s" are not set';

    /**
     * Bootstrap path
     */
    const BOOTSTRAP_PATH = 'Bootstrap.php';

    /**
     * URLs definition
     */
    const CONTROLLER = 0;
    const LANGUAGE_HANDLER = 1;
    const LANGUAGE_HANDLER_PARAMS = 2;

    /**
     * Controller class name
     * @var string
     */
    private $controller;

    /**
     * Url parameters
     * @var array
     */
    private $url_parameters = [];

    /**
     * Construct
     * @param array $urls
     */
    public function __construct(array $urls)
    {
        // load the bootstrap
        $this->load_bootstrap();
        $app = App::get_instance();

        $this->set_url_parameters($app->get_url_base_pos())
            ->set_controller($urls);

        if (empty($this->controller)) {
            $app->set_http_error(404);
        }

        $this->check_https();
    }

    /**
     * Creates a controller instances and runs the request method
     */
    public function run()
    {
        $app = App::get_instance();
        $method = $app->get_method();

        $controller = $this->get_controller_instance();

        // validate controller is instance of Controller
        if (!$controller instanceof Controller) {
            Error::set(sprintf(self::ERROR_NO_CONTROLLER_INSTANCE, $this->controller));
        }

        // validate the required http method is implemented in controller
        if (!in_array($method, get_class_methods($controller))) {
            return $app->set_http_error(405);
        }

        return $controller->$method($this->url_parameters);
    }

    /**
     * Gets the action definition
     * - Includes the required controller to process the action
     * - Includes the language handler for detecting the action language
     * @param  array $urls
     * @return Action
     */
    private function set_controller(array $urls)
    {
        // reduce urls to module set if exists
        $app = App::get_instance();
        $module = $app->get_module();
        if (!empty($module)) {
            array_key_exists($module, $urls)
                ? $urls = $urls[$module]
                : Error::set(sprintf(self::ERROR_NO_MODULE_ROUTES, $module));
        }

        // loop the defined urls looking for a match
        foreach ($urls as $url => $controller) {
            if (is_string($controller)) {
                // set the match pattern
                $pattern = str_replace('/', '\/', $url);
                // set the string to search
                $subject = '/' . implode('/', $this->url_parameters);
                if (preg_match('/^' . $pattern . '$/', $subject)) {
                    $this->controller = $controller;
                    $app->set_controller($controller);
                    return $this;
                }
            }
        }

        return $this;
    }

    /**
     * Checks for possible http/https redirections
     */
    private function check_https()
    {
        $app = App::get_instance();
        $is_https = $app->is_https();

        // check if https is enforced
        if ($app->is_https_enforced()) {
            return $is_https ? true : Redirector::https();
        }

        // check if the controller is in the individual list of https request
        $https_controllers = $app->get_https_controllers();
        if (in_array($this->controller, $https_controllers)) {
            return $is_https ? true : Redirector::https();
        }

        // if we are on https but shouldn't redirect to http
        if ($is_https && !in_array($this->controller, $https_controllers)) {
            return Redirector::http();
        }
    }

    /**
     * Sets the url parameters
     * @param int $base_pos What position to start looking for a match
     */
    private function set_url_parameters($base_pos = 0)
    {
        // get the URL path
        $path = parse_url(Request::server('REQUEST_URI'), PHP_URL_PATH);
        $url_parameters = array_values(array_filter(explode('/', $path), array($this,"validate_filter")));

        $this->url_parameters = $base_pos > 0
            ? array_slice($url_parameters, $base_pos)
            : $url_parameters;

        return $this;
    }

    /**
     * Loads the application bootstrap
     * Calls all public methods on it
     */
    private function load_bootstrap()
    {
        $app = App::get_instance();

        // set module path if exists
        $module = $app->get_module();
        $module_path = !empty($module)
            ? 'module' . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR
            : '';

        // set the bootstrap path
        $bootstrap_path = $app->get_application_folder() . DIRECTORY_SEPARATOR
            . $module_path . self::BOOTSTRAP_PATH;

        // load the bootstrap if available
        if (is_readable($bootstrap_path)) {
            // get the bootstrap and make sure the class exists
            require_once $bootstrap_path;
            if (!class_exists('Bootstrap', false)) {
                Error::set(self::ERROR_NO_BOOTSTRAP);
            }

            // get the bootstrap methods and call them
            $methods = get_class_methods('Bootstrap');
            $bootstrap = new Bootstrap();
            foreach ($methods as $method) {
                $bootstrap->{$method}();
            }
        }
    }

    /**
     * Validate if the filter is a valid filter
     * @param  obj  $param
     * @return bool
     */
    private function validate_filter($param)
    {
        return (null !== $param && false !== $param && '' !== $param);
    }

    /**
     * Gets the controller instance
     * @return Controller
     */
    private function get_controller_instance()
    {
        $default_path = 'Controller\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $this->controller);

        $module = App::get_instance()->get_module();
        if (isset($module)) {
            $module_path = 'Module\\' . ucfirst(strtolower($module)) . '\\' . $default_path;
            if (class_exists($module_path)) {
                return new $module_path();
            }
        }

        if (!class_exists($default_path)) {
            Error::set(sprintf(self::ERROR_NO_CONTROLLER_FILE, $this->controller, $default_path));
        }

        return new $default_path();
    }
}
