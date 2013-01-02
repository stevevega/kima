<?php
/**
 * Kima View
 * @author Steve Vega
 */
namespace Kima;

use \Kima\Application,
    \Kima\Cache,
    \Kima\Error;

/**
 * View
 * Handles the view template system for better views
 * @package Kima
 */
class View
{

    /**
     * Error messages
     */
    const ERROR_INVALID_CONTENT_TYPE = '"%s" is not a valid content type';
    const ERROR_INVALID_VIEW_PATH = 'Cannot access template directory path "%s"';
    const ERROR_INVALID_STRINGS_PATH = 'Cannot access strings path "%s"';
    const ERROR_DUPLICATE_TEMPLATE = 'Template "%s" was already created';
    const ERROR_NO_TEMPLATE = 'Template "%s" not exists';
    const ERROR_HTML_ONLY = '%s can only be added to html views';
    const ERROR_INVALID_HTML_HEADER = 'Invalid html format, <head> needed for meta tags and styles';
    const ERROR_INVALID_HTML_BODY = 'Invalid html format, <head> needed for %s';

    /**
     * Available content types
     * @var array
     */
    private $content_types = ['html', 'xml', 'txt'];

    /**
     * Template content type
     * @var string
     */
    private $content_type;

    /**
     * Template default view path
     * @var string
     */
    private $view_path;

    /**
     * Should we use the layout?
     * @var boolean
     */
    private $use_layout = false;

    /**
     * Cache instance
     * @var \Kima\Cache
     */
    private $cache;

    /**
     * Global template variables
     * @var array
     */
    private $globals = [];

    /**
     * Template variables
     * @var array
     */
    private $vars = [];

    /**
     * Template js scripts
     * @var array
     */
    private $scripts = [];

    /**
     * Template css styles
     * @var array
     */
    private $styles = [];

    /**
     * Defined actions to do on page load
     * @var array
     */
    private $on_load = [];

    /**
     * Defined actions to do on page unload
     * @var array
     */
    private $on_unload = [];

    /**
     * Template meta tags
     * @var array
     */
    private $meta_tags = [];

    /**
     * Template blocks where we store every section apart
     * @var array
     */
    private $blocks = [];

    /**
     * Template blocks that were already parsed
     * @var array
     */
    private $parsed_blocks = [];

    /**
     * Auto display result option
     * @var boolean
     */
    private $auto_display;

    /**
     * Result compression option
     * @var boolean
     */
    private $use_compression;

    /**
     * Locale option for template translation
     * @var boolean
     */
    private $locale;

    /**
     * Constructor
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        // set the cache handler
        $cache_options = isset($options['cache']) ? $options['cache'] : [];
        $this->setCache($cache_options);

        // set the view directory
        $view_path = isset($options['folder']) ? $options['folder'] : '.';
        $this->set_view_path($view_path);

        // set auto display option
        $this->set_auto_display(isset($options['autodisplay']) ? $options['autodisplay'] : false);

        // set the compression option
        $this->set_compression(isset($options['compression']) ? $options['compression'] : false);

        // set the locale option
        $this->set_locale(isset($options['locale']) ? $options['locale'] : false);

        // set the main template file path
        if (isset($options['layout']))
        {
            $this->load($options['layout']);
            $this->use_layout = true;
        }
    }

    /**
     * Set the proper content type of the main template to use
     * @param string $view_file
     */
    private function set_content_type($view_file)
    {
        // set the content type
        $file_parts = explode('.', $view_file);
        $content_type = end($file_parts);

        in_array($content_type, $this->content_types)
            ? $this->content_type = $content_type
            : Error::set(sprintf(self::ERROR_INVALID_CONTENT_TYPE, $content_type));
    }

    /**
     * Set the folder path where the views are located
     * @param string $view_path
     */
    private function set_view_path($view_path)
    {
        // get the view directory path
        is_dir($view_path) && is_readable($view_path)
            ? $this->view_path = $view_path
            : Error::set(sprintf(self::ERROR_INVALID_VIEW_PATH, $view_path));
    }

    /**
     * Set the cache handler
     * @param array $options
     */
    private function setCache(array $options)
    {
        // set the cache instance
        $this->cache = Cache::get_instance('default', $options);
    }

    /**
     * Loads a view and set it into blocks
     * @param string $file
     */
    public function load($file)
    {
        // is the first (main) view to load?
        if (empty($this->blocks))
        {
            // set the content type
            $this->set_content_type($file);
        }

        // get the blocks from cache?
        $cache_key = str_replace(DIRECTORY_SEPARATOR, '-', $file);
        $blocks = $this->cache->get_by_file($cache_key, $this->view_path . '/' . $file);

        // do we have cached content?
        if (empty($blocks))
        {
            // get the file contents
            $template = $this->get_view_file($file);

            // get the blocks from the template content
            $blocks = $this->get_blocks($template, $file);

            // set the blocks on cache
            $this->cache->set($cache_key, $blocks);
        }

        if ($this->locale)
        {
            $blocks = $this->get_block_strings($blocks, $file);
        }

        // set the blocks
        $this->blocks = array_merge($this->blocks, $blocks);
    }

    /**
     * Get block strings based on locale settings
     * @param array $blocks
     * @param string $view_file
     * @return array
     */
    private function get_block_strings(array $blocks, $view_file)
    {
        $language = Application::get_language();
        $language_default = Application::get_config()->language['default'];

        $strings_path = Application::get_config()->strings['folder'];
        $strings_path .= $language_default === $language
            ? 'default.ini'
            : $language . '.ini';

        if (!is_readable($strings_path))
        {
            Error::set(sprintf(self::ERROR_INVALID_STRINGS_PATH, $strings_path));
        }

        // TODO: cache, template cache root folder
        $strings = parse_ini_file($strings_path, true);

        $module = Application::get_module();
        $info = pathinfo($view_file);

        $language_prefix = '.' !== $info['dirname'] ? str_replace('/', '-', $info['dirname']) : '';
        $language_prefix = !empty($module)
            ? $module . '-' . $language_prefix
            : $language_prefix;
        $language_key = !empty($language_prefix)
            ? $language_prefix . '-' . $info['filename']
            : $info['filename'];

        foreach ($blocks as &$block)
        {
            $vars = [];
            preg_match_all('|\[([A-Za-z0-9._]+?)\]|', $block, $vars);
            $keys = !empty($vars[1]) ? $vars[1] : [];
            $vars = !empty($vars[0]) ? $vars[0] : [];

            foreach ($vars as $key => $var)
            {
                if (!empty($var))
                {
                    switch (true)
                    {
                        // [module]-controller-action
                        case (!empty($strings[$language_key])
                            && array_key_exists($keys[$key], $strings[$language_key])
                            && !empty($strings[$language_key][$keys[$key]])) :
                            $value = $strings[$language_key][$keys[$key]];
                            break;
                        // [module]-controller
                        case (!empty($strings[$language_prefix])
                            && array_key_exists($keys[$key], $strings[$language_prefix])
                            && !empty($strings[$language_prefix][$keys[$key]])) :
                            $value = $strings[$language_prefix][$keys[$key]];
                            break;
                        // global
                        case (!empty($strings['global'])
                            && array_key_exists($keys[$key], $strings['global'])
                            && !empty($strings['global'][$keys[$key]])) :
                            $value = $strings['global'][$keys[$key]];
                            break;
                        default:
                            $value = '';
                            break;
                    }

                    $block = str_replace($var, $value, $block);
                }
            }
        }

        return $blocks;
    }

    /**
     * Gets the main template file and set its contents into a string
     * @param string $file
     * @return string
     */
    private function get_view_file($file)
    {
        // set the view file path
        $view_path = $this->view_path . '/' . $file;

        // get the template content
        is_readable($view_path)
            ? $content = file_get_contents($view_path)
            : Error::set(sprintf(self::ERROR_INVALID_VIEW_PATH, $view_path));

        // return the content
        return $content;
    }

    /**
     * Breaks the template content into blocks
     * @param string $template
     * @return array
     */
    private function get_blocks($template)
    {
        // initialize some needed vars
        $blocks = [];
        $block_names = [];
        $level = 0;
        $regex = '(begin|end):\s*(\w+)\s*-->(.*)';

        // set the template block parts
        $block_parts = explode('<!--', $template);

        // lets work with every block part
        foreach($block_parts as $key => $block)
        {
            // set the result array
            $res = [];

            // set block structure
            if (preg_match_all('/' . $regex . "/ims", $block, $res, PREG_SET_ORDER))
            {
                // set the block parts
                $block_tag = $res[0][1];
                $block_name = $res[0][2];
                $block_content = $res[0][3];

                // is a begin block?
                if (strcmp($block_tag, 'begin') === 0)
                {
                    // set the current parent
                    $parent_name = end($block_names);

                    // add one level
                    $block_names[++$level] = $block_name;

                    // set the current block name
                    $current_block_name = end($block_names);

                    // add contents
                    empty($blocks[$current_block_name])
                        ? $blocks[$current_block_name] = $block_content
                        : Error::set(sprintf(self::ERROR_DUPLICATE_TEMPLATE, $current_block_name));

                    // add {block.blockName} to the parent blog
                    $blocks[$parent_name] .= '{_BLOCK_.' . $current_block_name . '}';
                }
                else // is an end block
                {
                    // remove last level
                    unset($block_names[$level--]);

                    // set the parent name
                    $parent_name = end($block_names);

                    // add the rest of the block to the parent
                    $blocks[$parent_name] .= $block_content;
                }
            }
            else // set block content
            {
                // set the temp name
                $tmp = end($block_names);

                // this is for normal comments
                empty($key) || $blocks[$tmp] .= '<!--';

                // add the value to the current block
                $blocks[$tmp] = isset($blocks[$tmp]) ? $blocks[$tmp] . $block : $block;

                // now work the includes
                while (preg_match('/<!--\s*include:\s*([A-Za-z0-9]+)\s*-->/', $blocks[$tmp], $res))
                {
                    // replace the tag with the block definition
                    $blocks[$tmp] = preg_replace(
                        '\''.preg_quote($res[0]).'\'', '{_BLOCK_.'.$res[1].'}', $blocks[$tmp]);
                }
            }
        }

        // send the blocks result
        return $blocks;
    }

    /**
     * Sets a value to a variable
     * If no template is passed, it will send a global variable
     * @param string $name
     * @param string $value
     * @param string $template
     */
    public function set($name, $value, $template = '')
    {
        if (empty($template))
        {
            // set global variable
            $this->globals[$name] = $value;
        }
        else
        {
            // make sure the template exists
            isset($this->blocks[$template])
                ? $this->vars[$template][$name] = $value
                : Error::set(sprintf(self::ERROR_NO_TEMPLATE, $template), Error::WARNING);
        }
    }

    /**
     * Parse the template content and merge it with the final result
     * prepared to flush
     * @param string $template
     */
    public function show($template)
    {
        // make a copy of template if exists
        isset($this->blocks[$template])
            ? $copy = $this->blocks[$template]
            : Error::set(sprintf(self::ERROR_NO_TEMPLATE, $template));

        // set the vars array
        $vars = [];

        // get the vars
        preg_match_all('|{([A-Za-z0-9._]+?)}|', $copy, $vars);
        $vars = end($vars);

        // parse the values and add the sub-blocks
        foreach ($vars as $var)
        {
            // get the current var
            $var_data = explode('.', $var);
            $current_var = array_pop($var_data);

            // check the var type
            if (isset($var_data[0]) && strcmp($var_data[0], '_BLOCK_') === 0)
            {
                // check for existing data
                $value = isset($this->parsed_blocks[$current_var])
                    ? $this->parsed_blocks[$current_var]
                    : '';

                // set the var with is corresponding value
                $copy = $this->set_value($var, $value, $copy);
            }
            else
            {
                // get possible template value
                if (isset($this->vars[$template][$var]))
                {
                    $value = $this->vars[$template][$var];
                }
                else
                { // try with global value if no template value existed
                    $value = isset($this->globals[$var]) ? $this->globals[$var] : '';
                }

                // set the var with is corresponding value
                $copy = $this->set_value($var, $value, $copy, false);
            }
        }

        // set this as a parsed block
        $this->parsed_blocks[$template] =  isset($this->parsed_blocks[$template])
            ? $this->parsed_blocks[$template] .= $copy
            : $this->parsed_blocks[$template] = $copy;
    }

    /**
     * Populates a template with an array data
     * @param string $template
     * @param array $data
     * @return void
     */
    public function populate($template, array $data)
    {
        foreach ($data as $object)
        {
            $object = is_object($object) ? get_object_vars($object) : (array)$object;

            if ($object)
            {
                foreach ($object as $item => $value)
                {
                    $this->set($item, $value, $template);
                }
            }
            $this->show($template);
        }
    }

    /**
     * Replace the tag var with the value on a template
     * also doing some data cleaning
     * @param string $var
     * @param string $value
     * @param string template
     * @param boolean $is_block
     * @return string
     */
    private function set_value($var, $value, $template, $is_block = true)
    {
        // extra cleaning for block
        if ($is_block)
        {
            switch (true)
            {
                case preg_match("/^\n/", $value) && preg_match("/\n$/", $value):
                    $value = substr($value, 1, -1);
                    break;
                case preg_match("/^\n/", $value):
                    $value = substr($value, 1);
                    break;
                case preg_match("/\n$/", $value):
                    $value = substr($value, 0, -1);
                    break;
            }
        }

        // some data cleaning
        $value = trim($value);
        $value = str_replace('\\', '\\\\', $value);
        $value = str_replace('$', '\\$', $value);
        $value = str_replace('\\|', '|', $value);

        // replace the var with the value
        $template = preg_replace('|{' . $var . '}|m', $value, $template);

        // final cleaning
        if (preg_match("/^\n/", $template) && preg_match("/\n$/", $template)) {
            $template = substr($template, 1, -1);
        }

        return $template;
    }

    /**
     * Clear a template
     * @param string $template
     */
    public function clear($template)
    {
        if (isset($this->parsed_blocks[$template]))
        {
            $this->parsed_blocks[$template] = null;
            $this->vars[$template] = null;
        }
        else
        {
            Error::set(sprintf(self::ERROR_NO_TEMPLATE, $template), Error::WARNING);
        }
    }

    /**
     * Hides a template
     * @access public
     * @param string $template
     */
    public function hide($template)
    {
        isset($this->parsed_blocks[$template])
            ? $this->parsed_blocks[$template] = null
            : Error::set(sprintf(self::ERROR_NO_TEMPLATE, $template), Error::WARNING);
    }

    /**
     * Sets a meta value to html type templates
     * @access public
     * @param string $name
     * @param string $content
     * @param boolean $http_equiv
     */
    public function meta($name, $content, $http_equiv = false)
    {
        // make sure we are on a html template
        if ('html' !== $this->content_type)
        {
            Error::set(sprintf(self::ERROR_HTML_ONLY, 'Meta tags'), Error::WARNING);
        }

        // set the meta
        $meta = '<meta ' .
            ($http_equiv ? 'http-equiv="' . $name . '"' : 'name="' . $name . '"')
            . ' content="' . $content . '" />';

        // avoid duplicates
        if (!in_array($meta, $this->meta_tags))
        {
            $this->meta_tags[] = $meta;
        }
    }

    /**
     * Sets a script value to html type templates
     * @param string $script
     */
    public function script($script)
    {
        if ('html' !== $this->content_type)
        {
            Error::set(sprintf(self::ERROR_HTML_ONLY, 'Scripts'), Error::WARNING);
        }

        // set the script
        $script = '<script src="' . $script . '" type="text/javascript" charset="utf-8"></script>';

        // avoid duplicates
        if (!in_array($script, $this->scripts))
        {
            $this->scripts[] = $script;
        }
    }

    /**
     * Sets a style value to html type templates
     * @param string $style
     */
    public function style($style)
    {
        // make sure we are on a html template
        if ('html' !== $this->content_type)
        {
            Error::set(sprintf(self::ERROR_HTML_ONLY, 'Styles'), Error::WARNING);
        }

        // set the style
        $style = '<link rel="stylesheet" href="' . $style . '" type="text/css" />';

        // avoid duplicates
        if (!in_array($style, $this->styles))
        {
            $this->styles[] = $style;
        }
    }

    /**
     * Sets an on load action to html type templates
     * @access public
     * @param string $code
     */
    public function on_load($code)
    {
        // make sure we are on a html template
        if ('html' !== $this->content_type)
        {
            Error::set(sprintf(self::ERROR_HTML_ONLY, 'On load actions'), Error::WARNING);
        }

        // avoid duplicates
        if (!in_array($code, $this->on_load))
        {
            $this->on_load[] = $code;
        }
    }

    /**
     * Sets an on unload action to html type templates
     * @param string $code
     */
    public function on_unload($code)
    {
        // make sure we are on a html template
        if ('html' !== $this->content_type)
        {
            Error::set(sprintf(self::ERROR_HTML_ONLY, 'On unload actions'), Error::WARNING);
        }

        // avoid duplicates
        if (!in_array($code, $this->on_unload)) {
            $this->on_unload[] = $code;
        }
    }

    /**
     * Gets a template content with the corresponding information
     * @param string $template
     * @param boolean $set_headers
     * @return string
     */
    public function get_template($template, $set_headers = true)
    {
        // make sure template exists
        if (empty($this->parsed_blocks[$template]))
        {
            Error::set(sprintf(self::ERROR_NO_TEMPLATE, $template));
        }

        if ($set_headers)
        {
            // set the default content type
            switch ($this->content_type)
            {
                // set the default html headers
                case 'html':
                    @header('Content-Type: text/html; charset=utf-8');

                    // add the headers and scripts
                    $this->add_headers($template);
                    $this->add_scripts($template);
                    $this->add_on_load($template);
                    $this->add_on_unload($template);
                    break;
                // set the default xml headers
                case 'xml':
                    @header('Content-Type: text/xml; charset=utf-8');
                    break;
                // set the default txt headers
                case 'txt':
                    @header('Content-Type: text/plain; charset=utf-8');
                    break;
            }
        }

        return $this->use_compression
            ? $this->compress($this->parsed_blocks[$template])
            : $this->parsed_blocks[$template];
    }

    /**
     * Add the meta tags and styles to the html type templates
     * @param string @template
     */
    private function add_headers($template)
    {
        if (!empty($this->meta_tags) || !empty($this->styles))
        {
            // put the headers together
            $headers = array_merge($this->meta_tags, $this->styles);

            // set the headers as text
            $headers = implode(' ', $headers);

            // try to add the headers just before the end of the head if exists
            if (strpos($this->parsed_blocks[$template], '</head>') > 0)
            {
                $this->parsed_blocks[$template] =
                    str_replace('</head>', $headers . '</head>', $this->parsed_blocks[$template]);
            }
            else
            {
                Error::set(self::ERROR_INVALID_HTML_HEADER, Error::WARNING);
            }
        }
    }

    /**
     * Add javascripts to the html type templates
     * @access private
     * @param string @template
     */
    private function add_scripts($template)
    {
        if (!empty($this->scripts))
        {
            // set the scripts as text
            $scripts = implode(' ', $this->scripts);

            // and now add the scripts at the end
            if (strpos($this->parsed_blocks[$template], '</body>') > 0)
            {
                $this->parsed_blocks[$template] =
                    str_replace('</body>', $scripts . '</body>', $this->parsed_blocks[$template]);
            }
            else
            {
                Error::set(sprintf(self::ERROR_INVALID_HTML_BODY, 'scripts'), Error::WARNING);
            }
        }
    }

    /**
     * Add on load actions to the html type templates
     * @access private
     * @param string @template
     */
    private function add_on_load($template)
    {
        if (!empty($this->on_load))
        {
            // get the code
            $code = implode(' ', $this->on_load);

            // and now add the on load action to the body
            if (strpos($this->parsed_blocks[$template], '</body>') > 0)
            {
                $this->parsed_blocks[$template] =
                    str_replace('<body', '<body onload="' . $code . '"', $this->parsed_blocks[$template]);
            }
            else
            {
                Error::set(sprintf(self::ERROR_INVALID_HTML_BODY, 'on load actions'), Error::WARNING);
            }
        }
    }

    /**
     * Add on unload actions to the html type templates
     * @access private
     * @param string @template
     */
    private function add_on_unload($template)
    {
        if (!empty($this->on_unload))
        {
            // get the code
            $code = implode(' ', $this->on_unload);

            // and now add the on unload action to the body
            if (strpos($this->parsed_blocks[$template], '</body>') > 0)
            {
                $this->parsed_blocks[$template] =
                    str_replace('<body', '<body onunload="' . $code . '"', $this->parsed_blocks[$template]);
            }
            else
            {
                Error::set(sprintf(self::ERROR_INVALID_HTML_BODY, 'un load actions'), Error::WARNING);
            }
        }
    }

    /**
     * Removes innecesary spaces and chars from the output
     * @param string $output
     * @return string
     * @todo fix google ads bug
     */
    private function compress($output)
    {
        // strip unnecesary data from the resultant content
        $output = str_replace("\n", '', $output);
        $output = str_replace("\t", '', $output);
        $output = str_replace(chr(13), '', $output);
        $output = preg_replace('/[\s]{2,}/', ' ', $output);
        $output = preg_replace('<!\-\- [\/\ a-zA-Z]* \-\->', '', $output);

        return $output;
    }

    /**
     * Sets the auto display option for the main template
     * @param boolean $auto_display
     */
    public function set_auto_display($auto_display)
    {
        $this->auto_display = (boolean)$auto_display;
    }

    /**
     * Sets the compress option from the template
     * @access public
     */
    public function set_compression($compression)
    {
        $this->use_compression = (boolean)$compression;
    }

    /**
     * Sets the locale option from the template
     * @param boolean $locale
     */
    public function set_locale($locale)
    {
        $this->locale = (boolean)$locale;
    }

    /**
     * Gets the main template for the current view
     */
    public function get_main_template()
    {
        if (empty($this->blocks))
        {
            return null;
        }

        preg_match('/{_BLOCK_.([A-Za-z0-9._]+?)}/', $this->blocks[0], $matches);
        return $matches[1];
    }

   /**
    * Outputs a template content
    * @param string $template
    */
    public function flush($template)
    {
        echo $this->get_template($template);
    }

    /**
    * Destructor
    */
    public function __destruct()
    {
        // flush the content if auto display option is on
        if ($this->auto_display)
        {
            $main_template = $this->get_main_template();

            if ($this->use_layout)
            {
                $this->show($main_template);
            }

            $this->flush($main_template);
        }
    }

}
