<?php
/**
 * Kima Action
 * @author Steve Vega
 */
namespace Kima;

use \Kima\Http\Redirector;
use \Kima\Http\Request;
use \Kima\Prime\App;
use \Bootstrap;

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
    const ERROR_NO_PREDISPATCHER = 'Registered Predispatcher class %s is not accesible';
    const ERROR_NO_CONTROLLER_FILE = 'Class file for "%s" is not accesible on "%s" or %s';
    const ERROR_NO_CONTROLLER_CLASS = ' Class "%s" not declared on "%s"';
    const ERROR_NO_CONTROLLER_INSTANCE = 'Object for "%s" is not an instance of \Kima\Controller';
    const ERROR_NO_MODULE_ROUTES = 'Routes for module "%s" are not set';
    const ERROR_INVALID_DEFINITION = 'Url "%s" definition has invalid data types';

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

        // set the url parameters
        $this->set_url_parameters($app->get_url_base_pos());

        // set the module routes if exists
        // reduce urls to module set
        $module = $app->get_module();
        if (!empty($module)) {
            array_key_exists($module, $urls)
                ? $urls = $urls[$module]
                : Error::set(sprintf(self::ERROR_NO_MODULE_ROUTES, $module));
        }

        // gets the controller
        $controller = $this->get_controller($urls);
        if (empty($controller)) {
            $app->set_http_error(404);
        }

        // set the action controller
        $app->set_controller($controller);

        // check for https/http redirections
        $this->check_https($controller);

        // inits the controller action
        $controller_handler = new Controller();
        $controller_handler->run($controller, $this->url_parameters);
    }

    /**
     * Gets the action definition
     * - Includes the required controller to process the action
     * - Includes the language handler for detecting the action language
     * @param  array $urls
     * @return array
     */
    private function get_controller(array $urls)
    {
        // loop the defined urls looking for a match
        foreach ($urls as $url => $controller) {
            if (is_string($controller)) {
                // set the match pattern
                $pattern = str_replace('/', '\/', $url);
                // set the string to search
                $subject = '/' . implode('/', $this->url_parameters);
                if (preg_match('/^' . $pattern . '$/', $subject)) {
                    return $controller;
                }
            }
        }

        return null;
    }

    /**
     * Checks for possible http/https redirections
     * @param string $controller
     */
    private function check_https($controller)
    {
        // get whether we are currently on https or not
        $application = App::get_instance();
        $is_https = $application->is_https();

        // check if https is enforced
        $is_https_enforced = $application->is_https_enforced();
        if ($is_https_enforced) {
            return $is_https ? true : Redirector::https();
        }

        // check if the controller is in the individual list of https request
        $https_controllers = $application->get_https_controllers();
        if (in_array($controller, $https_controllers)) {
            return $is_https ? true : Redirector::https();
        }

        // if we are on https but shouldn't redirect to http
        if ($is_https && !in_array($controller, $https_controllers)) {
            Redirector::http();
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

}
