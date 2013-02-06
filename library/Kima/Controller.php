<?php
/**
 * Kima Model
 * @author Steve Vega
 */
namespace Kima;

use \Kima\Application,
    \Kima\Error,
    \Kima\View;

/**
 * Controller
 */
class Controller
{

    /**
     * Error messages
     */
     const ERROR_DISABLE_LAYOUT = 'Method disable_layout() should be called before any view reference';
     const ERROR_DISABLE_DEFAULT_VIEW = 'Method disable_default_view() should be called before any view reference';


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
     * __get magic method
     * used to initialize the view only when needed
     */
    public function __get($param)
    {
        if ('view' === $param)
        {
            if (isset($this->view[$param]))
            {
                return $this->view[$param];
            }
            else
            {
                // get the config and application module-controller-action
                $config = Application::get_instance()->get_config()->view;
                $module = Application::get_instance()->get_module();
                $controller = Application::get_instance()->get_controller();
                $method = Application::get_instance()->get_method();

                // get the config adapted for the view
                $config = $this->get_view_config($config, $module);

                // set the view
                $this->view['view'] = new View($config);

                // load the action view
                if ($this->use_default_view)
                {
                    $view_path = strtolower($controller) . '/' . $method . '.html';
                    $this->view['view']->load($view_path);
                }

                return $this->view['view'];
            }
        }

        return null;
    }

    /**
     * Gets the config adapted for the current view
     * @param array $config The view config
     * @param string $module
     */
    private function get_view_config(array $config, $module)
    {
        // disable layout if not wanted
        if (!$this->use_layout)
        {
            unset($config['layout']);
        }

        // set cache config
        $config['cache'] = Application::get_instance()->get_config()->cache;
        $config['cache']['folder'] .= '/template';

        // set module config if necessary
        if ($module)
        {
            $module_folder = Application::get_instance()->get_config()->module['folder'];

            $config['folder'] = $module_folder . '/' . $module . '/view';
            $config['cache']['prefix'] = $module;
        }

        return $config;
    }

    /**
     * Disables the view layout for the controller
     */
    public function disable_layout()
    {
        if (isset($this->view['view']))
        {
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
        if (isset($this->view['view']))
        {
            Error::set(self::ERROR_DISABLE_DEFAULT_VIEW);
        }

        $this->use_default_view = false;
        return $this;
    }

}