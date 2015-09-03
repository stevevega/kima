<?php
/**
 * Kima Model
 * @author Steve Vega
 */
namespace Kima\Prime;

use Kima\Error;
use Kima\View;

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

                // get the config adapted for the view
                $config = $this->get_view_config($config, $app->get_module());

                // set the view
                $this->view['view'] = new View($config);

                // load the action view
                if ($this->use_default_view) {
                    $view_path = strtolower($app->get_controller()) .
                        DIRECTORY_SEPARATOR .
                        $app->get_method() .
                        '.html';
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
     * End the execution of the thread
     * @return exit
     */
    public function end_execution_flow()
    {
        exit;
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
