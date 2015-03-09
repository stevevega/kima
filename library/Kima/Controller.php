<?php
/**
 * Kima Model
 * @author Steve Vega
 */
namespace Kima;

use \Kima\Prime\App;

/**
 * Controller
 */
class Controller
{

    /**
     * Error messages
     */
    const ERROR_DISABLE_LAYOUT = 'Method disable_layout should be called before any view reference';
    const ERROR_DISABLE_DEFAULT_VIEW = 'Method disable_default_view should be called before any view reference';
    const ERROR_DISABLE_AUTO_DISPLAY = 'Method disable_auto_display should be called before any view reference';
    const ERROR_AUTO_DISPLAY_INCOMPATIBLE = 'json_output method is incompatible with the auto display default view behavior, try using disable_auto_display method first';
    const ERROR_NO_CONTROLLER_FILE = 'Class file for "%s" is not accesible on "%s" or %s';
    const ERROR_NO_CONTROLLER_CLASS = ' Class "%s" not declared on "%s"';
    const ERROR_NO_CONTROLLER_INSTANCE = 'Object for "%s" is not an instance of \Kima\Controller';

    /**
     * The controller template
     * @var array
     */
    private $view = [];

    /**
     * Use view layout?
     * @var boolean
     */
    private $use_layout = true;

    /**
     * User default controller view?
     * @var boolean
     */
    private $use_default_view = true;

    /**
     * Auto display generated content?
     * @var boolean
     */
    private $auto_display = true;

    /**
     * __get magic method
     * used to initialize the view only when needed
     */
    public function __get($param)
    {
        if ('view' === $param) {
            if (isset($this->view[$param])) {
                return $this->view[$param];
            } else {
                // get the config and application module-controller-action
                $app = App::get_instance();
                $config = $app->get_config()->view;
                $module = $app->get_module();
                $controller = $app->get_controller();
                $method = $app->get_method();

                // get the config adapted for the view
                $config = $this->get_view_config($config, $module);

                // set the view
                $this->view['view'] = new View($config);

                // load the action view
                if ($this->use_default_view) {
                    $view_path = strtolower($controller) . DIRECTORY_SEPARATOR . $method . '.html';
                    $this->view['view']->load($view_path);
                }

                // auto display content
                if (false === $this->auto_display) {
                    $this->view['view']->set_auto_display(false);
                }

                return $this->view['view'];
            }
        }

        return null;
    }

    /**
     * Execute a controller object method
     * @param string $controller
     */
    public function run($controller, $params)
    {
        // get the application values
        $application = App::get_instance();
        $module = $application->get_module();
        $method = $application->get_method();
        // Set the controller on the application
        $application->set_controller($controller);

        // get the controller class
        $controller_class = '\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $controller);

        // get the controller path for the module
        $controller_path = $this->get_controller_path($controller, $module);
        $default_path = isset($module) ? $this->get_controller_path($controller, null) : null;

        // get the controller instance
        $controller_obj = $this->get_controller_instance(
            $controller_class,
            $controller_path,
            $default_path
        );

        // validate-call action
        $methods = $this->get_controller_methods($controller_class);
        if (!in_array($method, $methods)) {
            $application->set_http_error(405);

            return;
        }

        $controller_obj->$method($params);
    }

    /**
     * End the execution of the thread
     * @return exit
     */
    public function end_execution_flow()
    {
        exit;
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
        $app = App::get_instance();

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
     * Gets the config adapted for the current view
     * @param array  $config The view config
     * @param string $module
     */
    private function get_view_config(array $config, $module)
    {
        $app = App::get_instance();
        $app_config = $app->get_config();

        // disable layout if not wanted
        if (!$this->use_layout) {
            unset($config['layout']);
        }

        // set cache config
        $config['cache'] = $app_config->cache;

        // set module config if necessary
        if ($module) {
            $config['folder_failover'] = $app->get_view_folder();
            $config['folder'] = $app->get_module_folder() . $module . '/view';
        }

        return $config;
    }

    /**
     * Disables the view layout for the controller
     */
    public function disable_layout()
    {
        if (isset($this->view['view'])) {
            Error::set(self::ERROR_DISABLE_LAYOUT);
        }

        $this->use_layout = false;

        return $this;
    }

    /**
     * Disables the view loaded by default in the controller
     */
    public function disable_default_view()
    {
        if (isset($this->view['view'])) {
            Error::set(self::ERROR_DISABLE_DEFAULT_VIEW);
        }

        $this->use_default_view = false;

        return $this;
    }

    /**
     * Disables the auto display of content
     */
    public function disable_auto_display()
    {
        if (isset($this->view['view'])) {
            Error::set(self::ERROR_DISABLE_AUTO_DISPLAY);
        }

        $this->auto_display = false;

        return $this;
    }

    /**
     * Outputs json content
     * @param mixed $content
     */
    public function json_output($content)
    {
        if (isset($this->view['view']) && $this->auto_display) {
            Error::set(self::ERROR_AUTO_DISPLAY_INCOMPATIBLE);
        }

        header('Content-Type: application/json');
        echo json_encode($content);
    }

}
