<?php
/**
 * Kima Action
 * @author Steve Vega
 */
namespace Kima;

use \Kima\Http\Redirector;
use \Kima\Http\Request;
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

        // set the url parameters
        $this->set_url_parameters();

        // set the application language
        $app = Application::get_instance();

        // set the module routes if exists
        // reduce urls to module set
        $module = $app->get_module();
        if (!empty($module)) {
            array_key_exists($module, $urls)
                ? $urls = $urls[$module]
                : Error::set(sprintf(self::ERROR_NO_MODULE_ROUTES, $module));
        }

        // get definition
        list($controller, $lang_handler, $lang_handler_params) = $this->get_definition($urls);

        // get language
        $language = $this->get_language($lang_handler, $lang_handler_params);

        // set the action language
        $app->set_language($language);

        // check controller
        if (empty($controller)) {
            $language = isset($language) ? $language : $app->get_default_language();
            $app->set_language($language);
            $app->set_http_error(404);
        }

        // check language
        if (empty($language)) {
            $lang_source = Language::get_instance();
            $lang_url = $lang_source->get_language_url($app->get_default_language());
            Redirector::redirect($lang_url, 301);
        }

        // set the action controller
        $app->set_controller($controller);

        // run the predispatcher
        $this->load_predispatcher();

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
    private function get_definition(array $urls)
    {
        $app = Application::get_instance();

        // known possibilities
        $app_default_lang_type = $app->get_default_language_type();
        $language = (isset($this->url_parameters[0])) ? $this->url_parameters[0] : null;

        // simplified detection mechanisms
        $is_valid_language = (!is_null($language))
            ? ($language === $app->get_default_language() || $app->is_language_available($language))
            : false;
        $is_valid_type = (!is_null($app_default_lang_type))
            ? (Application::LANG_DEFAULT_EXPLICIT === $app_default_lang_type && $is_valid_language
                || Application::LANG_DEFAULT_IMPLICIT === $app_default_lang_type)
            : true;

        // matching options (with or without language url paramter)
        $subject = '/' . implode('/', $this->url_parameters);
        $subject_no_lang = '/' . implode('/', array_slice($this->url_parameters, 1));

        // loop the defined urls looking for a match
        foreach ($urls as $url => $definition) {

            // set definition components
            switch (true) {
                case is_string($definition):
                    $controller = $definition;
                    $lang_handler = null;
                    $lang_handler_params = array();
                    break;
                case is_array($definition):
                    $controller = $definition[self::CONTROLLER];
                    $lang_handler = $definition[self::LANGUAGE_HANDLER];
                    $lang_handler_params = $definition[self::LANGUAGE_HANDLER_PARAMS];
                    break;
                default:
                    $controller = null;
                    $lang_handler = null;
                    $lang_handler_params = array();
                    Error::set(sprintf(self::ERROR_INVALID_DEFINITION, $url));
                    break;
            }

            // set the match pattern
            $pattern = str_replace('/', '\/', $url);

            // simplified language detection
            $match = (is_null($lang_handler) && $is_valid_type && $is_valid_language) ? $subject_no_lang : $subject;

            // try to check route url against the detected url
            if (preg_match('/^' . $pattern . '$/', $match)) {
                // if it matches without language, remove language permanently from url parameters
                if ($match === $subject_no_lang) {
                    array_shift($this->url_parameters);
                }

                return [$controller, $lang_handler, $lang_handler_params];
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
        $application = Application::get_instance();
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
     * Gets the language required for the current action
     * @param string $handler
     */
    private function get_language($handler = null, $handler_params = [])
    {
        $app = Application::get_instance();

        // get the language object
        $lang_source = Language::get_instance($handler, $handler_params);

        return $lang_source->get_app_language();
    }

    /**
     * Sets the url parameters
     */
    private function set_url_parameters()
    {
        // get the URL path
        $path = parse_url(Request::server('REQUEST_URI'), PHP_URL_PATH);
        $url_parameters = array_values(array_filter(explode('/', $path), array($this,"validate_filter")));

        $this->url_parameters = $url_parameters;

        return $this;
    }

    /**
     * Loads the application bootstrap
     * Calls all public methods on it
     */
    private function load_bootstrap()
    {
        $app = Application::get_instance();

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
     * Loads predispatcher class
     * This will run just before the controller action is fired
     */
    private function load_predispatcher()
    {
        $predispatcher = Application::get_instance()->get_predispatcher();

        if (!empty($predispatcher)) {
            // get the bootstrap and make sure the class exists
            if (!class_exists($predispatcher)) {
                Error::set(sprintf(self::ERROR_NO_PREDISPATCHER, $predispatcher));
            }

            // get the bootstrap methods and call them
            $methods = get_class_methods($predispatcher);
            $predispatcher = new $predispatcher();
            foreach ($methods as $method) {
                $predispatcher->{$method}();
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
