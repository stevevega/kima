<?php
/**
 * Namespace Kima
 */
namespace Kima;

/**
 * Namespaces to use
 */
use \Kima\Application,
    \Kima\Error,
    \Kima\Template;

/**
 * Controller
 *
 * @package Kima
 */
class Controller
{

    /**
     * The controller template
     * @access protected
     * @var array
     */
    protected $_template = array();

    /**
     * Use main template?
     * @access protected
     * @var boolean
     */
    private $_use_main_view = true;

    /**
     * __get magic method
     * used to initialize the view only when needed
     */
    public function __get($param)
    {
        if ($param === '_view') {
            if (isset($this->_template[$param])) {
                return $this->_template[$param];
            } else {
                // get the config and application module-controller-action
                $config = Application::get_instance()->get_config()->template;
                $module = Application::get_instance()->get_module();
                $controller = Application::get_instance()->get_controller();
                $method = Application::get_instance()->get_method();

                if (!$this->_use_main_view) {
                    unset($config['main']);
                }

                // set cache config
                $config['cache'] = Application::get_instance()->get_config()->cache;
                $config['cache']['folder'] .= '/template';

                if ($module) {
                    $module_folder = Application::get_instance()->get_config()->module['folder'];

                    $config['folder'] = $module_folder . '/' . $module . '/view';
                    $config['cache']['prefix'] = $module;
                }

                // set the view
                $this->_template['_view'] = new Template($config);

                // load the action view
                $view_path = $controller . '/' . $method . '.html';
                $this->_template['_view']->load($view_path);
                return $this->_template['_view'];
            }
        }
        return null;
    }

    /**
     * Disables the main view for the current
     * controller action
     */
    public function disable_main_view()
    {
        if (isset($this->_template['_view'])) {
            Error::set(__METHOD__, 'disable_main_view() should be called before any view reference');
        }

        $this->_use_main_view = false;
    }

}