<?php
/**
 * Kima Action
 * @author Steve Vega
 */
namespace Kima;

use \Kima\Error,
    \Kima\Controller,
    \Kima\Http\Request,
    \Kima\Http\StatusCode;

/**
 * Action
 * Implementation of the Front Controller design pattern
 */
class Action
{

    /**
     * Error messages
     */
    const ERROR_NO_CONTROLLER_FILE = 'Class file for "%s" is not accesible on "%s"';
    const ERROR_NO_CONTROLLER_CLASS = ' Class "%s" not declared on "%s"';
    const ERROR_NO_CONTROLLER_INSTANCE = 'Object for "%s" is not an instance of \Kima\Controller';
    const ERROR_NO_MODULE_ROUTES = 'Routes for module "%s" are not set';

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
        // set the url parameters
        $this->set_url_parameters();

        // set the application language
        $language = $this->get_language();
        Application::get_instance()->set_language($language);

        // set the module routes if exists
        $module = Application::get_instance()->get_module();
        if (!empty($module))
        {
            array_key_exists($module, $urls)
                ? $urls = $urls[$module]
                : Error::set(sprintf(self::ERROR_NO_MODULE_ROUTES, $module));
        }

        // get the controller matching the routes
        $controller = $this->get_controller($urls);

        // validate controller and action
        if (empty($controller)) {
            $this->set_error_action(404);
            return;
        }

        // set the action controller
        Application::get_instance()->set_controller($controller);

        // inits the controller action
        $this->run_action($controller);
    }


    /**
     * Gets the url match route to follow
     * Returns the required controller to process the action
     * @param array $urls
     * @return string
     */
    private function get_controller(array $urls)
    {
        // gets the URL path needed parameters
        $url_parameters = $this->get_url_parameters();
        $url_parameters_count = count($url_parameters);

        // loop the defined urls looking for a match
        foreach ($urls as $url => $controller)
        {
            if (is_string($controller))
            {
                // split the url elements
                $url_elements = array_values(array_filter(explode('/', $url)));

                // compare the elements size
                if ($url_parameters_count === count($url_elements))
                {
                    $is_match = true;

                    // loop each url elements
                    foreach ($url_elements as $key => $url_element)
                    {
                        // match the url element with the path element
                        preg_match('/^' . $url_element . '$/', $url_parameters[$key], $matches);
                        if (!$matches)
                        {
                            $is_match = false;
                            break;
                        }
                    }

                    // if all the elements matched, return its controller
                    if ($is_match)
                    {
                        return $controller;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Runs an application action
     * @param string $controller
     */
    private function run_action($controller)
    {
        // get the application values
        $config = Application::get_instance()->get_config();
        $module = Application::get_instance()->get_module();
        $method = Application::get_instance()->get_method();

        // get the controller path
        $controller_folder = $module
            ? $config->module['folder'] . '/' . $module . '/controller'
            : $config->controller['folder'];

        $controller_path = $controller_folder . '/' . $controller . '.php';

        // get the controller class
        $controller_class = '\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $controller);

        $controller_obj = $this->get_controller_instance($controller_class, $controller_path);

        // validate-call action
        $methods = $this->get_controller_methods($controller_class);
        if (!in_array($method, $methods))
        {
            $this->set_error_action(405);
            return;
        }

        $params = $this->get_url_parameters();
        $controller_obj->$method($params);
    }

    /**
     * Gets the controller instance
     * @param string $controller The controller name
     * @param string $controller_path
     * @return \Kima\Controller
     */
    private function get_controller_instance($controller, $controller_path)
    {
        // require the controller file
        if (is_readable($controller_path))
        {
            require_once $controller_path;
        }
        else
        {
            Error::set(sprintf(self::ERROR_NO_CONTROLLER_FILE, $controller, $controller_path));
            return;
        }

        // validate-create controller object
        class_exists($controller, false)
            ? $controller_obj = new $controller
            : Error::set(sprintf(self::ERROR_NO_CONTROLLER_CLASS, $controller, $controller_path));

        // validate controller is instance of Kima\Controller
        if (!$controller_obj instanceof Controller)
        {
            Error::set(sprintf(self::ERROR_NO_CONTROLLER_INSTANCE, $controller));
        }

        return $controller_obj;
    }

    /**
     * gets the controller available methods
     * removes the parent references
     * @param string $controller
     * @return array
     */
    private function get_controller_methods($controller)
    {
        $parent_methods = get_class_methods('Kima\Controller');
        $controller_methods = get_class_methods($controller);

        return array_diff($controller_methods, $parent_methods);
    }

    /**
     * set an http error for the page
     * @param int $status_code
     */
    private function set_error_action($status_code)
    {
        // set the status code
        http_response_code($status_code);

        $config = Application::get_instance()->get_config();
        $controller = 'Error';
        $controller_path = $config->controller['folder'] . '/Error.php';
        require_once $controller_path;

        $method = 'get';
        $controller_obj = new $controller;
        $controller_obj->$method();
    }

    /**
     * gets the language required for the current action
     * @return string
     */
    public function get_language()
    {
        // get the possible language
        $url_parameters = $this->get_url_parameters();
        $language = array_shift($url_parameters);
        $languages = [];

        // get the list of available languages
        if (!empty(Application::get_instance()->get_config()->language['available']))
        {
            $languages = Application::get_instance()->get_config()->language['available'];
            $languages = explode(',', $languages);
        }

        // return the desired languages
        if (in_array($language, $languages))
        {
            array_shift($this->url_parameters);
        }
        else
        {
            $language = Application::get_instance()->get_default_language();
        }

        return $language;
    }

    /**
     * Sets the url parameters
     */
    public function set_url_parameters()
    {
        $path_parts = explode('?', Request::server('REQUEST_URI'));
        $path = array_shift($path_parts);
        $path_elements = array_values(array_filter(explode('/', $path)));

        $this->url_parameters = $path_elements;
        return $this;
    }

    /**
     * Gets the url parameters
     * @return array
     */
    public function get_url_parameters()
    {
        return $this->url_parameters;
    }

}