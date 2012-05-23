<?php
/**
 * Namespace Kima
 */
namespace Kima;

/**
 * Namespaces to use
 */
use \Kima\Error;
use \Kima\Controller;

/**
 * Action
 *
 * @package Kima
 */
class Action
{

    /**
     * constructor
     * @param string $controller
     * @param string $action
     */
    public function __construct()
    {
        // gets the controller and action
        $controller = Application::get_instance()->get_controller();
        $action = Application::get_instance()->get_action();

        // validate controller and action
        if (empty($controller)) {
            Error::set(__METHOD__, ' Controller was not set');
        }

        if (empty($action)) {
            Error::set(__METHOD__, ' Action was not set');
        }

        // inits the controller action
        $this->_run_action($controller, $action);
    }

    /**
     * runs an action
     * @param string $controller
     * @param string $action
     */
    private function _run_action($controller, $action)
    {
        // get the config
        $config = Application::get_instance()->get_config();

        // set the needed values
        $action = strtolower($action) . '_action';
        $controller = ucwords(strtolower($controller));
        $controller_path = $config->controller['folder'] . '/' . $controller . '.php';

        // get the controller
        is_readable($controller_path)
            ? require_once $controller_path
            : Error::set(__METHOD__, ' Cannot access controller file path ' . $controller_path);

        // validate-create controller object
        class_exists($controller)
            ? $controller_obj = new $controller
            : Error::set(__METHOD__, ' Class ' . $controller . ' not found on ' . $controller_path);

        // validate controller is instance of Kima\Controller
        if (!$controller_obj instanceof Controller) {
            Error::set(__METHOD__, ' Object ' . $controller . ' is not an instance of Kima\Controller');
        }

        // validate-call action
        method_exists($controller, $action)
            ? $controller_obj->$action()
            : Error::set(__METHOD__, ' Method ' . $action . ' not found on ' . $controller . ' controller');
    }

}