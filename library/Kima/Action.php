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
     * url parameters
     * @var array
     */
    private $_url_parameters = array();

    /**
     * constructor
     * @param array $urls
     */
    public function __construct(array $urls)
    {
        // set the url parameters
        $this->set_url_parameters();

        // set the application language
        $language = $this->get_language();
        Application::get_instance()->set_language($language);

        $controller = $this->_get_controller($urls);

        // validate controller and action
        if (empty($controller)) {
            $this->set_error_action(404);
            return;
        }

        // set the action controller
        Application::get_instance()->set_controller($controller);

        // inits the controller action
        $this->_run_action($controller);
    }


    /**
     * gets the url match route to follow
     * returns the required controller to process the action
     * @param array $urls
     */
    private function _get_controller(array $urls)
    {
        // gets the URL path needed parameters
        $url_parameters = $this->get_url_parameters();
        $url_parameters_count = count($url_parameters);


        // loop the defined urls looking for a match
        foreach ($urls as $url => $controller) {
            // split the url elements
            $url_elements = array_values(array_filter(explode('/', $url)));

            // just compare if the elements size match
            if ($url_parameters_count === count($url_elements)) {
                $is_match = true;

                // loop each url elements
                foreach ($url_elements as $key => $url_element) {
                    // match the url element with the path element
                    preg_match('/^' . $url_element . '$/', $url_parameters[$key], $matches);
                    if (!$matches) {
                        $is_match = false;
                        break;
                    }
                }

                // if all the elements matched, return its controller
                if ($is_match) {
                    return $controller;
                }
            }
        }
    }

    /**
     * runs an application action
     * @param string $controller
     */
    private function _run_action($controller)
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

        // get the controller
        if (is_readable($controller_path)) {
            require_once $controller_path;
        } else {
            Error::set(' Class ' . $controller . ' not found on ' . $controller_path);
            return;
        }

        // validate-create controller object
        class_exists($controller)
            ? $controller_obj = new $controller
            : Error::set(' Class ' . $controller . ' not declared on ' . $controller_path);

        // validate controller is instance of Kima\Controller
        if (!$controller_obj instanceof Controller) {
            Error::set(' Object ' . $controller . ' is not an instance of Kima\Controller');
        }

        // validate-call action
        $methods = $this->get_controller_methods($controller);
        if (!in_array($method, $methods)) {
            $this->set_error_action(405);
            return;
        }

        $params = $this->get_url_parameters();
        $controller_obj->$method($params);
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

        // get the list of available languages
        $languages = Application::get_instance()->get_config()->language['available'];
        $languages = explode(',', $languages);

        // return the desired languages
        if (in_array($language, $languages)) {
            array_shift($this->_url_parameters);
        } else {
            $language = Application::get_instance()->get_config()->language['default'];
        }

        return $language;
    }

    /**
     * Sets the url parameters
     */
    public function set_url_parameters()
    {
        $path_parts = explode('?', $_SERVER['REQUEST_URI']);
        $path = array_shift($path_parts);
        $path_elements = array_values(array_filter(explode('/', $path)));

        $this->_url_parameters = $path_elements;
        return $this;
    }

    /**
     * gets the url parameters
     * @return array
     */
    public function get_url_parameters()
    {
        return $this->_url_parameters;
    }

}