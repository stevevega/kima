<?php
/**
 * Namespace Kima
 */
namespace Kima;

/**
 * Namespaces to use
 */
use \Kima\Cache,
    \Kima\Error;

/**
 * Template
 *
 * Handles the template system for better views
 * @package Kima
 */
class Template
{

    /**
     * Available content types
     * @access private
     * @var array
     */
    private $_content_types = array('html', 'xml', 'txt');

    /**
     * Template default folder path
     * @access private
     * @var string
     */
    private $_folder_path;

    /**
     * Has a main view?
     * @access private
     * @var boolean
     */
    private $_has_main_view = false;

    /**
     * Template content type
     * @access private
     * @var string
     */
    private $_content_type;

    /**
     * Cache instance
     * @access private
     * @var Kima_Cache
     */
    private $_cache;

    /**
     * Global variables
     * @access private
     * @var array
     */
    private $_globals = array();

    /**
     * Template variables
     * @access private
     * @var array
     */
    private $_vars = array();

    /**
     * Template js scripts
     * @access private
     * @var array
     */
    private $_scripts = array();

    /**
     * Template css styles
     * @access private
     * @var array
     */
    private $_styles = array();

    /**
     * Defined actions to do on page load
     * @access private
     * @var array
     */
    private $_on_load = array();

    /**
     * Defined actions to do on page unload
     * @access private
     * @var array
     */
    private $_on_unload = array();

    /**
     * Template meta tags
     * @access private
     * @var array
     */
    private $_meta_tags = array();

    /**
     * Template blocks where we store every section apart
     * @access private
     * @var array
     */
    private $_blocks = array();

    /**
     * Template blocks that were already parsed
     * @access private
     * @var array
     */
    private $_parsed_blocks = array();

    /**
     * Auto display result option
     * @access private
     * @var boolean
     */
    private $_auto_display;

    /**
     * Result compression option
     * @access private
     * @var boolean
     */
    private $_compression;

    /**
     * Constructor
     * @access public
     * @param array $options
     */
    public function __construct($options = array())
    {
        // set the cache handler
        $cache_config = isset($options['cache']) ? $options['cache'] : array();
        $this->_setCache($cache_config);

        // set the template directory
        $folder_path = isset($options['folder']) ? $options['folder'] : '.';
        $this->_set_folder_path($folder_path);

        // set the main template file path
        if (isset($options['main']['file'])) {
            $this->load($options['main']['file']);
            $this->_has_main_view = true;
        }

        // set auto display option
        $this->set_auto_display(isset($options['autodisplay']) ? $options['autodisplay'] : false);

        // set the compression option
        $this->set_compression(isset($options['compression']) ? $options['compression'] : false);
    }

    /**
     * Set the proper content type of the main template to use
     * @access private
     * @param string $template_file
     */
    private function _set_content_type($template_file)
    {
        // set the content type
        $content_type = end(explode('.', $template_file));

        in_array($content_type, $this->_content_types)
            ? $this->_content_type = $content_type
            : Error::set(__METHOD__, $content_type . ' is not a valid content type');
    }

    /**
     * Set the folder path where the templates are located
     * @access private
     * @param string $folder_path
     */
    private function _set_folder_path($folder_path)
    {
        // get the template directory path
        is_dir($folder_path) && is_readable($folder_path)
            ? $this->_folder_path = $folder_path
            : Error::set(__METHOD__, ' Cannot access template directory path ' . $folder_path);
    }

    /**
     * Set the cache handler
     * @param array $config
     * @access private
     */
    private function _setCache($config)
    {
        // set the cache instance
        $this->_cache = Cache::get_instance('default', $config);
    }

    /**
     * Loads a template and set it into blocks
     * @access public
     * @param string $template
     */
    public function load($template_file)
    {
        // is the first (main) template to load?
        if (empty($this->_blocks)) {
            // set the content type
            $this->_set_content_type($template_file);
        }

        // get the blocks from cache?
        $cache_file = str_replace(DIRECTORY_SEPARATOR, '-', $template_file);
        $blocks = $this->_cache->get_by_file($cache_file, $this->_folder_path . '/' . $template_file);

        // do we have cached content?
        if (empty($blocks)) {
            // get the file contents
            $template = $this->_get_template_file($template_file);

            // get the blocks from the template content
            $blocks = $this->_get_blocks($template, $template_file);

            // set the blocks on cache
            $this->_cache->set($cache_file, $blocks);
        }

        // set the blocks
        $this->_blocks = array_merge($this->_blocks, $blocks);
    }

    /**
     * Gets the main template file and set its contents into a string
     * @access private
     * @param string $template_file
     * @return string
     */
    private function _get_template_file($template_file)
    {
        // set the template file path
        $template_path = $this->_folder_path . '/' . $template_file;

        // get the template content
        is_readable($template_path)
            ? $content = file_get_contents($template_path)
            : Error::set(__METHOD__, ' Cannot access template file path ' . $template_path);

        // return the content
        return $content;
    }

    /**
     * Breaks the template content into blocks
     * @access private
     * @param string $template
     * @return array
     */
    private function _get_blocks($template)
    {
        // initialize some needed vars
        $blocks = array();
        $block_names = array();
        $level = 0;
        $regex = '(begin|end):\s*(\w+)\s*-->(.*)';

        // set the template block parts
        $block_parts = explode('<!--', $template);

        // lets work with every block part
        foreach($block_parts as $key => $block) {
            // set the result array
            $res = Array();

            // set block structure
            if (preg_match_all('/'.$regex."/ims", $block, $res, PREG_SET_ORDER)) {
                // set the block parts
                $block_tag = $res[0][1];
                $block_name = $res[0][2];
                $block_content = $res[0][3];

                // is a begin block?
                if ( strcmp($block_tag, 'begin')==0 ) {
                    // set the current parent
                    $parent_name = end($block_names);

                    // add one level
                    $block_names[++$level] = $block_name;

                    // set the current block name
                    $current_block_name = end($block_names);

                    // add contents
                    empty($blocks[$current_block_name])
                        ? $blocks[$current_block_name] = $block_content
                        : Error::set(__METHOD__, ' Duplicate template ' . $current_block_name);

                    // add {block.blockName} to the parent blog
                    $blocks[$parent_name] .= '{_BLOCK_.'.$current_block_name.'}';
                } else { // is an end block
                    // remove last level
                    unset($block_names[$level--]);

                    // set the parent name
                    $parent_name = end($block_names);

                    // add the rest of the block to the parent
                    $blocks[$parent_name] .= $block_content;
                }
            } else { // set block content
                // set the temp name
                $tmp = end($block_names);

                // this is for normal comments
                empty($key) || $blocks[$tmp] .= '<!--';

                // add the value to the current block
                $blocks[$tmp] = isset($blocks[$tmp]) ? $blocks[$tmp] . $block : $block;

                // now work the includes
                while (preg_match('/<!--\s*include:\s*([A-Za-z0-9]+)\s*-->/', $blocks[$tmp], $res)) {
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
     * @access public
     * @param string $name
     * @param string $value
     * @param string $template
     */
    public function set($name, $value, $template='')
    {
        if (empty($template)) {
            // set global variable
            $this->_globals[$name] = $value;
        } else {
            // make sure the template exists
            isset($this->_blocks[$template])
                ? $this->_vars[$template][$name] = $value
                : Error::set(__METHOD__, ' Template ' . $template . ' doesn\'t exists', false);
        }
    }

    /**
     * Parse the template content and merge it with the final result
     * prepared to flush
     * @access public
     * @param string $template
     */
    public function show($template)
    {
        // make a copy of template if exists
        isset($this->_blocks[$template])
            ? $copy = $this->_blocks[$template]
            : Error::set(__METHOD__, ' Template ' . $template . ' doesn\'t exist');

        // set the vars array
        $vars = Array();

        // get the vars
        preg_match_all('|{([A-Za-z0-9._]+?)}|', $copy, $vars);
        $vars = end($vars);

        // parse the values and add the sub-blocks
        foreach ($vars as $var) {
            // get the current var
            $var_data = explode('.', $var);
            $current_var = array_pop($var_data);

            // check the var type
            if (isset($var_data[0]) && strcmp($var_data[0], '_BLOCK_')==0) {
                // check for existing data
                $value = isset($this->_parsed_blocks[$current_var])
                    ? $this->_parsed_blocks[$current_var]
                    : '';

                // set the var with is corresponding value
                $copy = $this->_set_value($var, $value, $copy);
            } else {
                // get possible template value
                if (isset($this->_vars[$template][$var])) {
                    $value = $this->_vars[$template][$var];
                } else { // try with global value if no template value existed
                    $value = isset($this->_globals[$var]) ? $this->_globals[$var] : '';
                }

                // set the var with is corresponding value
                $copy = $this->_set_value($var, $value, $copy, false);
            }
        }

        // set this as a parsed block
        $this->_parsed_blocks[$template] =  isset($this->_parsed_blocks[$template])
            ? $this->_parsed_blocks[$template] .= $copy
            : $this->_parsed_blocks[$template] = $copy;
    }

    /**
     * populates a template with an array data
     * @access public
     * @param string $template
     * @param array $data
     * @return void
     */
    public function populate($template, $data)
    {
        foreach ($data as $object) {
            $object = is_array($object) ? $object : get_object_vars($object);

            if ($object) {
                foreach ($object as $item => $value) {
                    $this->set($item, $value, $template);
                }
            }
            $this->show($template);
        }
    }

    /**
     * replace the tag var with the value on a template
     * also doing some data cleaning
     * @access private
     * @param string $var
     * @param string $value
     * @param string template
     * @param boolean $is_block
     * @return string
     */
    private function _set_value($var, $value, $template, $is_block=true)
    {
        // extra cleaning for block
        if ($is_block) {
            switch (true) {
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
        $template = preg_replace('|{'.$var.'}|m', $value, $template);

        // final cleaning
        if (preg_match("/^\n/", $template) && preg_match("/\n$/", $template)) {
            $template = substr($template, 1, -1);
        }

        return $template;
    }

    /**
     * Clears a template
     * @access public
     * @param string $template
     */
    public function clear($template)
    {
        if (isset($this->_parsed_blocks[$template])) {
            $this->_parsed_blocks[$template] = null;
            $this->_vars[$template] = null;
        } else {
            Error::set(__METHOD__, ' Template ' . $template . ' doesn\'t exists', false);
        }
    }

    /**
     * Hides a template
     * @access public
     * @param string $template
     */
    public function hide($template)
    {
        isset($this->_parsed_blocks[$template])
            ? $this->_parsed_blocks[$template] = null
            : Error::set(__METHOD__, ' Template ' . $template . ' doesn\'t exists', false);
    }

    /**
     * Sets a meta value to html type templates
     * @access public
     * @param string $name
     * @param string $content
     * @param boolean $http_equiv
     */
    public function meta($name, $content, $http_equiv=false)
    {
        // make sure we are on a html template
        if ($this->_content_type != 'html') {
            Error::set(__METHOD__, ' Metas can only be asigned to html templates', false);
        }

        // set the meta
        $meta = '<meta ' .
            ( $http_equiv ? 'http-equiv="'.$name.'"' : 'name="'.$name.'"' )
            . ' content="' . $content . '" />';

        // avoid duplicates
        if (!in_array($meta, $this->_meta_tags)) {
            $this->_meta_tags[] = $meta;
        }
    }

    /**
     * Sets a script value to html type templates
     * @access public
     * @param string $script
     */
    public function script($script)
    {
        // make sure we are on a html template
        if ($this->_content_type !=  'html') {
            Error::set(__METHOD__, ' Scripts can only be asigned to html templates', false);
        }

        // set the script
        $script = '<script src="'.$script.'" type="text/javascript" charset="utf-8"></script>';

        // avoid duplicates
        if (!in_array($script, $this->_scripts)) {
            $this->_scripts[] = $script;
        }
    }

    /**
     * Sets a style value to html type templates
     * @access public
     * @param string $style
     */
    public function style($style)
    {
        // make sure we are on a html template
        if ($this->_content_type != 'html') {
            Error::set(__METHOD__, ' Styles can only be asigned to html templates', false);
        }

        // set the style
        $style = '<link rel="stylesheet" href="'.$style.'" type="text/css" />';

        // avoid duplicates
        if (!in_array($style, $this->_styles)) {
            $this->_styles[] = $style;
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
        if ($this->_content_type != 'html') {
            Error::set(__METHOD__, ' On load actions can only be asigned to html templates', false);
        }

        // avoid duplicates
        if (!in_array($code, $this->_on_load)) {
            $this->_on_load[] = $code;
        }
    }

    /**
     * Sets an on unload action to html type templates
     * @access public
     * @param string $code
     */
    public function on_unload($code)
    {
        // make sure we are on a html template
        if ($this->_content_type!='html') {
            Error::set(__METHOD__, ' On unload can only be asigned to html templates', false);
        }

        // avoid duplicates
        if (!in_array($code, $this->_on_unload)) {
            $this->_on_unload[] = $code;
        }
    }

    /**
     * Gets a template content with the corresponding information
     * @access public
     * @param string $template
     * @param boolean $set_headers
     * @return string
     */
    public function get_template($template, $set_headers=true)
    {
        // make sure template exists
        if (empty($this->_parsed_blocks[$template])) {
            Error::set(__METHOD__, ' Template ' . $template . ' doesn\'t exists');
        }

        if ($set_headers) {
            // set the default content type
            switch ($this->_content_type) {
                // set the default html headers
                case 'html':
                    @header('Content-Type: text/html; charset=utf-8');

                    // add the headers and scripts
                    $this->_add_headers($template);
                    $this->_add_scripts($template);
                    $this->_add_on_load($template);
                    $this->_add_on_unload($template);
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

        return $this->_compression
            ? $this->_compress($this->_parsed_blocks[$template])
            : $this->_parsed_blocks[$template];
    }

    /**
     * Add the meta tags and styles to the html type templates
     * @access private
     * @param string @template
     */
    private function _add_headers($template)
    {
        if (!empty($this->_meta_tags) || !empty($this->_styles)) {
            // put the headers together
            $headers = array_merge($this->_meta_tags, $this->_styles);

            // set the headers as text
            $headers = implode(' ', $headers);

            // try to add the headers just before the end of the head if exists
            if (strpos($this->_parsed_blocks[$template], '</head>')>0) {
                $this->_parsed_blocks[$template] =
                    str_replace('</head>', $headers . '</head>', $this->_parsed_blocks[$template]);
            } else {
                Error::set(__METHOD__, ' invalid html format, <head> needed for meta and styles', false);
            }
        }
    }

    /**
     * Add javascripts to the html type templates
     * @access private
     * @param string @template
     */
    private function _add_scripts($template)
    {
        if (!empty($this->_scripts)) {
            // set the scripts as text
            $scripts = implode(' ', $this->_scripts);

            // and now add the scripts at the end
            if (strpos($this->_parsed_blocks[$template], '</body>')>0) {
                $this->_parsed_blocks[$template] =
                    str_replace('</body>', $scripts . '</body>', $this->_parsed_blocks[$template]);
            } else {
                Error::set(__METHOD__, ' invalid html format, <body> needed for javascripts', false);
            }
        }
    }

    /**
     * Add on load actions to the html type templates
     * @access private
     * @param string @template
     */
    private function _add_on_load($template)
    {
        if (!empty($this->_on_load)) {
            // get the code
            $code = implode(' ', $this->_on_load);

            // and now add the on load action to the body
            if (strpos($this->_parsed_blocks[$template], '</body>')>0) {
                $this->_parsed_blocks[$template] =
                    str_replace('<body', '<body onload="' . $code . '"', $this->_parsed_blocks[$template]);
            } else {
                Error::set(__METHOD__, ' invalid html format, <body> needed for on load actions', false);
            }
        }
    }

    /**
     * Add on unload actions to the html type templates
     * @access private
     * @param string @template
     */
    private function _add_on_unload($template)
    {
        if (!empty($this->_on_unload)) {
            // get the code
            $code = implode(' ', $this->on_unload);

            // and now add the on unload action to the body
            if (strpos($this->_parsed_blocks[$template], '</body>')>0) {
                $this->_parsed_blocks[$template] =
                    str_replace('<body', '<body onunload="' . $code . '"', $this->_parsed_blocks[$template]);
            } else {
                Error::set(__METHOD__, ' invalid html format, <body> needed for on unload actions', false);
            }
        }
    }

    /**
     * Removes innecesary spaces and chars from the output
     * @access private
     * @param string $output
     * @return string
     * @todo fix google ads bug
     */
    private function _compress($output)
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
     * @access public
     */
    public function set_auto_display($auto_display)
    {
        $this->_auto_display = (boolean)$auto_display;
    }

    /**
     * Sets the compress option from the template
     * @access public
     */
    public function set_compression($compression)
    {
        $this->_compression = (boolean)$compression;
    }

    /**
     * Gets the main template for the current view
     * @access public
     */
    public function get_main_template()
    {
        if (empty($this->_blocks)) {
            return null;
        }

        preg_match('/{_BLOCK_.([A-Za-z0-9._]+?)}/', $this->_blocks[0], $matches);
        return $matches[1];
    }

   /**
    * Outputs a template content
    * @access public
    * @param string $template
    */
    public function flush($template)
    {
        echo $this->get_template($template);
    }

    /**
    * Destructor
    * @access public
    */
    public function __destruct()
    {
        // flush the content if auto display option is on
        if ($this->_auto_display) {
            $main_template = $this->get_main_template();

            if ($this->_has_main_view) {
                $this->show($main_template);
            }

            $this->flush($main_template);
        }
    }

}