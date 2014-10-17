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

    /**
     * Bootstrap path
     */
    const BOOTSTRAP_PATH = 'Bootstrap.php';

    /**
     * URLs definition
     */
    const CONTROLLER = 0;
    const LANGUAGE_HANDLER = 1;

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
        list($controller, $lang_handler) = $this->get_definition($urls);

        // get language
        $language = $this->get_language($lang_handler);

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
        $this->run_action($controller);
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

        // performance
        $app_default_lang = $app->get_default_language();
        $app_default_lang_type = $app->get_default_language_type();
        $url_parameters = $this->url_parameters;
        $url_parameters_no_lang = array_slice($this->url_parameters, 1);
        $subject = '/' . implode('/', $url_parameters);
        $subject_no_lang = '/' . implode('/', $url_parameters_no_lang);

        // loop the defined urls looking for a match
        foreach ($urls as $url => $definition) {

            // set definition components
            switch (true) {
                case is_string($definition):
                    $controller = $definition;
                    $lang_handler = null;
                    break;
                case is_array($definition):
                    $controller = $definition[self::CONTROLLER];
                    $lang_handler = $definition[self::LANGUAGE_HANDLER];
                    break;
            }

            // set the match pattern
            $pattern = str_replace('/', '\/', $url);

            // no language handler provided, try to determine how language is set
            if (is_null($lang_handler)) {
                switch ($app_default_lang_type) {
                    case Application::LANG_DEFAULT_EXPLICIT:
                        // check if language was set by url
                        if (isset($this->url_parameters[0])) {
                            $language = $this->url_parameters[0];
                            // check if language is valid
                            if ($language === $app_default_lang || $app->is_language_available($language)) {
                                $match = $subject_no_lang;
                            } else {
                                // not a explicit valid language, neither a custom handler
                                // probably another kima language type
                                $match = $subject;
                            }
                        } else {
                            // language is not set by url,
                            // probably redirect to default language appended url
                            $match = $subject;
                        }
                        break;
                    case Application::LANG_DEFAULT_IMPLICIT:
                        // implicit language set, remove language url parameter
                        $match = $subject_no_lang;
                        break;
                }
            } else {
                // if a custom language source is provided (assume it implements ILanguage),
                // means that somehow it determines the language = leave url as is
                $match = $subject;
            }

            // try to check route url against the processed url (with or  without language)
            if (preg_match('/^' . $pattern . '$/', $match)) {
                // if it matches without language, remove language permanently from url parameters
                if ($match === $subject_no_lang) {
                    array_shift($this->url_parameters);
                }

                return [$controller, $lang_handler];
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
     * Runs an application action
     * @param string $controller
     */
    private function run_action($controller)
    {
        // get the application values
        $application = Application::get_instance();
        $module = $application->get_module();
        $method = $application->get_method();

        // get the controller class
        $controller_class = '\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $controller);

        // get the controller path for the module
        $controller_path = $this->get_controller_path($controller, $module);
        $default_path = isset($module) ? $this->get_controller_path($controller, null) : null;

        // get the controller instance
        $controller_obj = $this->get_controller_instance(
            $controller_class, $controller_path, $default_path);

        // validate-call action
        $methods = $this->get_controller_methods($controller_class);
        if (!in_array($method, $methods)) {
            $application->set_http_error(405);

            return;
        }

        $params = $this->url_parameters;
        $controller_obj->$method($params);
    }

    /**
     * Returns the controller path for a controller
     * @param  string $controller
     * @param  string $module
     * @return string
     */
    private function get_controller_path($controller, $module = null)
    {
        // get the app
        $app = Application::get_instance();

        // get the controller folder
        $controller_folder = isset($module)
            ? $app->get_module_folder() . '/' . $module . '/controller/'
            : $app->get_controller_folder();

        // return the controller path
        return $controller_folder . $controller . '.php';
    }

    /**
     * Gets the controller instance
     * @param  string           $controller      The controller name
     * @param  string           $controller_path
     * @return \Kima\Controller
     */
    private function get_controller_instance($controller, $controller_path, $default_path)
    {
        // require the controller file
        if (is_readable($controller_path)) {
            require_once $controller_path;
        }
        // look for the default controller path if the other one is not accessible
        else if (is_readable($default_path)) {
            $controller_path = $default_path;
            require_once $controller_path;
        }
        // no controller was found, error is triggered
        else {
            Error::set(sprintf(self::ERROR_NO_CONTROLLER_FILE,
                $controller, $controller_path, $default_path));

            return;
        }

        // validate-create controller object
        class_exists($controller, false)
            ? $controller_obj = new $controller
            : Error::set(sprintf(self::ERROR_NO_CONTROLLER_CLASS, $controller, $controller_path));

        // validate controller is instance of Kima\Controller
        if (!$controller_obj instanceof Controller) {
            Error::set(sprintf(self::ERROR_NO_CONTROLLER_INSTANCE, $controller));
        }

        return $controller_obj;
    }

    /**
     * Gets the controller available methods
     * removes the parent references
     * @param  string $controller
     * @return array
     */
    private function get_controller_methods($controller)
    {
        $parent_methods = get_class_methods('Kima\Controller');
        $controller_methods = get_class_methods($controller);

        return array_diff($controller_methods, $parent_methods);
    }

    /**
     * Gets the language required for the current action
     * @param string $handler
     */
    private function get_language($handler = null)
    {
        $app = Application::get_instance();

        // get the language object
        $lang_source = Language::get_instance($handler);

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
