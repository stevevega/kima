<?php
/**
 * Kima View
 * @author Steve Vega
 */
namespace Kima;

use \Kima\Html\CssToInline;

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
    const ERROR_DUPLICATE_TEMPLATE = 'Template "%s" was already created';
    const ERROR_NO_TEMPLATE = 'Template "%s" not exists';
    const ERROR_HTML_ONLY = '%s can only be added to html views';
    const ERROR_INVALID_HTML_HEADER = 'Invalid html format, <head> needed for meta tags and styles';
    const ERROR_INVALID_HTML_BODY = 'Invalid html format, <head> needed for %s';

    /**
     * Content types
     */
    const HTML = 'html';
    const XML = 'xml';
    const TXT = 'txt';

    /**
     * Scripts lazy load
     */
    const LAZY_LOAD_SCRIPT =
        '<script type="text/javascript">
            (function (w) {
                function ll()
                {
                    var e;
                    %s
                }

                if (w.addEventListener)
                    w.addEventListener("load", ll, false);
                else if (w.attachEvent)
                    w.attachEvent("onload", ll);
                else w.onload = ll;
            })(window);
        </script>';

    /**
     * Lazy load include
     */
    const LAZY_LOAD_INCLUDE =
        'e = document.createElement("script");
        e.src = "%s";
        e.type = "text/javascript";
        document.body.appendChild(e);';

    /**
     * Available content types
     * @var array
     */
    private $content_types = [self::HTML, self::XML, self::TXT];

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
     * Template failover view path
     * @var string
     */
    private $failover_path;

    /**
     * Should we use the layout?
     * @var boolean
     */
    private $use_layout = false;

    /**
     * Should apply css styles inline?
     * @var boolean
     */
    private $apply_styles_inline = false;

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
     * Lazy loaded js scripts
     * @var array
     */
    private $lazy_scripts = [];

    /**
     * Template css styles
     * @var array
     */
    private $styles = [];

    /**
     * Stored in case we want to apply styles inline
     * @var array
     */
    private $style_files = [];

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
     * Constructor
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        // set the cache handler
        $cache_options = isset($options['cache']) ? $options['cache'] : [];
        $this->set_cache($cache_options);

        // set the view directory
        $view_path = isset($options['folder']) ? $options['folder'] : '.';
        $this->set_view_path($view_path);

        // and failover path if required
        if (isset($options['folder_failover'])) {
            $this->set_view_path($options['folder_failover'], true);
        }

        // set auto display option
        $this->set_auto_display(isset($options['autodisplay']) ? $options['autodisplay'] : false);

        // set the compression option
        $this->set_compression(isset($options['compression']) ? $options['compression'] : false);

        // set the main template file path
        if (isset($options['layout'])) {
            $this->load($options['layout']);
            $this->use_layout = true;
        }
    }

    /**
     * Loads a view and set it into blocks
     * @param string $file
     * @param string $view_path custom view path
     */
    public function load($file, $view_path = null)
    {
        // is the first (main) view to load?
        if (empty($this->blocks)) {
            // set the content type
            $this->set_content_type($file);
        }

        // get the blocks from cache?
        $file_path = $this->get_view_file_path($file, $view_path);
        $cache_key = str_replace(DIRECTORY_SEPARATOR, '-', $file_path);
        $blocks = $this->cache->get_by_file($cache_key, $file_path);

        // do we have cached content?
        if (empty($blocks)) {
            // get the file contents
            $template = $this->get_file_content($file_path);

            // get the blocks from the template content
            $blocks = $this->get_blocks($template, $file);

            // set the blocks on cache
            $this->cache->set($cache_key, $blocks);
        }

        $blocks = $this->get_block_l10n($blocks, $file);

        // set the blocks
        $this->blocks = array_merge($this->blocks, $blocks);
    }

    /**
     * Sets a value to a variable
     * If no template is passed, it will send a global variable
     * @param  string  $name
     * @param  string  $value
     * @param  string  $template
     * @param  boolean $escaped     Escaped by default
     * @param  boolean $apply_nl2br Whether to apply nl2br to values or not
     * @return View
     */
    public function set($name, $value, $template = null, $escaped = true, $apply_nl2br = false)
    {
        // escape value if required
        if ($escaped) {
            $value = htmlentities($value, ENT_QUOTES, 'UTF-8', false);
        }

        // apply nl2br to the value if required
        if ($apply_nl2br) {
            $value = nl2br($value, true);
        }

        if (!isset($template)) {
            // set global variable
            $this->globals[$name] = $value;
        } else {
            // make sure the template exists
            isset($this->blocks[$template])
                ? $this->vars[$template][$name] = $value
                : Error::set(sprintf(self::ERROR_NO_TEMPLATE, $template), Error::WARNING);
        }

        return $this;
    }

    /**
     * Parse and renders the template content and merge it with the final result
     * prepared to flush
     * @param string  $template
     * @param boolean $keep_values
     */
    public function render($template, $keep_values = false)
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
        foreach ($vars as $var) {
            // get the current var
            $var_data = explode('.', $var);
            $current_var = array_pop($var_data);

            // check the var type
            if (isset($var_data[0]) && strcmp($var_data[0], '_BLOCK_') === 0) {
                // check for existing data
                $value = isset($this->parsed_blocks[$current_var])
                    ? $this->parsed_blocks[$current_var]
                    : '';

                // set the var with is corresponding value
                $copy = $this->set_value($var, $value, $copy);
                unset($this->parsed_blocks[$current_var]);
            } else {
                // get possible template value
                if (isset($this->vars[$template][$var])) {
                    $value = $this->vars[$template][$var];
                } else { // try with global value if no template value existed
                    $value = isset($this->globals[$var]) ? $this->globals[$var] : '';
                }

                // set the var with is corresponding value
                $copy = $this->set_value($var, $value, $copy, false);
            }
        }

        // clear template values unless we want to keep them
        if (!$keep_values) {
            unset($this->vars[$template]);
        }

        // set this as a parsed block
        $this->parsed_blocks[$template] =  isset($this->parsed_blocks[$template])
            ? $this->parsed_blocks[$template] .= $copy
            : $this->parsed_blocks[$template] = $copy;
    }

    /**
     * Alias of render
     * @see self::render()
     * @param string  $template
     * @param boolean $keep_values
     */
    public function show($template, $keep_values = false)
    {
        $this->render($template, $keep_values);
    }

    /**
     * Populates a template with an array data
     * @param  string $template
     * @param  mixed  $data
     * @return void
     */
    public function populate($template, $data)
    {
        // make sure the template exists and the data is a valid type
        if (isset($this->blocks[$template]) && (is_array($data) || is_object($data))) {
            // single object
            if (is_object($data)) {
                $element = get_object_vars($data);
                $this->populate_element($element, $template);
            } else { // array
                $temp_element = [];

                foreach ($data as $key => $element) {
                    switch (true) {
                        case is_object($element):
                            $element = get_object_vars($element);
                            $this->populate_element($element, $template);
                            break;
                        case is_array($element):
                            $this->populate($key, $element);
                            break;
                        default:
                            $temp_element[$key] = $element;
                            break;
                    }
                }

                if (!empty($temp_element)) {
                    $this->populate_element($temp_element, $template);
                }
            }
        }
    }

    /**
     * Clear a template
     * @param string $template
     */
    public function clear($template)
    {
        if (isset($this->parsed_blocks[$template])) {
            $this->parsed_blocks[$template] = null;
            $this->vars[$template] = null;
        } else {
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
     * @param string  $name
     * @param string  $content
     * @param boolean $http_equiv
     */
    public function meta($name, $content, $http_equiv = false)
    {
        // make sure we are on a html template
        if (self::HTML !== $this->content_type) {
            Error::set(sprintf(self::ERROR_HTML_ONLY, 'Meta tags'), Error::WARNING);
        }

        // set the meta
        $meta = '<meta ' .
            ($http_equiv ? 'http-equiv="' . $name . '"' : 'name="' . $name . '"')
            . ' content="' . $content . '" />';

        // avoid duplicates
        if (!in_array($meta, $this->meta_tags)) {
            $this->meta_tags[] = $meta;
        }
    }

    /**
     * Sets a script value to html type templates
     * @param string  $script
     * @param boolean $lazyLoad
     */
    public function script($script, $lazy_load = false, $attrs = null)
    {
        if (self::HTML !== $this->content_type) {
            Error::set(sprintf(self::ERROR_HTML_ONLY, 'Scripts'), Error::WARNING);
        }

        // set the script
        if ($lazy_load) {
            $script = sprintf(self::LAZY_LOAD_INCLUDE, $script);
            $target = 'lazy_scripts';
        } else {
            $attrs_str = $this->format_attrs($attrs);
            $script = '<script src="' . $script . '" ' .  $attrs_str . ' type="text/javascript"></script>';
            $target = 'scripts';
        }

        // avoid duplicates
        if (!in_array($script, $this->{$target})) {
            $this->{$target}[] = $script;
        }
    }

    /**
     * Sets a style value to html type templates
     * @param string  $style_file     (Requires full path wehn load_in_header is true)
     * @param string  $media_type
     * @param boolean $load_in_header
     */
    public function style($style_file, $media_type = null, $load_in_header = false)
    {
        // make sure we are on a html template
        if (self::HTML !== $this->content_type) {
            Error::set(sprintf(self::ERROR_HTML_ONLY, 'Styles'), Error::WARNING);
        }

        $media = isset($media_type) ? sprintf('media="%s"', $media_type) : '';

        // set the style
        if ($load_in_header) {
            $style_format = '<style type="text/css" %s />%s</style>';
            $style_content = file_get_contents($style_file);
            $style = sprintf($style_format, $media, $style_content);
        } else {
            $style_format = '<link rel="stylesheet" href="%s" type="text/css" %s />';
            $style = sprintf($style_format, $style_file, $media);
        }

        // avoid duplicates
        if (!in_array($style_file, $this->style_files)) {
            $this->styles[] = $style;
            $this->style_files[] = $style_file;
        }
    }

    /**
     * Gets the view path, it may be the module view if exists
     * otherwise it returns the global scope view path
     * @param  string $file
     * @param  string $view_path
     * @return string
     */
    public function get_view_file_path($file, $view_path = null)
    {
        // set the view path if not exists
        if (!isset($view_path)) {
            $view_path = $this->view_path;
        }

        // set the view file path
        $file_path = $view_path . DIRECTORY_SEPARATOR . $file;

        // check if we need to use the failover path
        if (!is_readable($file_path) && isset($this->failover_path)) {
            $file_path = $this->failover_path . DIRECTORY_SEPARATOR . $file;
        }

        return $file_path;
    }

    /**
     * Gets a template content with the corresponding information
     * @param  string  $template
     * @param  boolean $set_headers
     * @return string
     */
    public function get_view($template, $set_headers = true)
    {
        // make sure template exists
        if (empty($this->parsed_blocks[$template])) {
            Error::set(sprintf(self::ERROR_NO_TEMPLATE, $template));
        }

        if ($set_headers) {
            // set the default content type
            switch ($this->content_type) {
                // set the default html headers
                case self::HTML:
                    @header('Content-Type: text/html; charset=utf-8');
                    @header('X-UA-Compatible: IE=edge,chrome=1');

                    // add the headers and scripts
                    $this->add_headers($template);
                    $this->add_scripts($template);

                    break;
                // set the default xml headers
                case self::XML:
                    @header('Content-Type: text/xml; charset=utf-8');
                    break;
                // set the default txt headers
                case self::TXT:
                    @header('Content-Type: text/plain; charset=utf-8');
                    break;
            }
        }

        $result = $this->use_compression && !$this->apply_styles_inline
            ? $this->compress($this->parsed_blocks[$template])
            : $this->parsed_blocks[$template];

        // apply styles inline
        if ($this->apply_styles_inline && self::HTML === $this->content_type) {
            $css = '';
            foreach ($this->style_files as $file) {
                $css .= file_get_contents($file);
            }

            $inlineCss = new CssToInline($result, $css);
            $result = $inlineCss->convert();
        }

        return $result;
    }

    /**
     * Gets the main template for the current view
     */
    public function get_main_template()
    {
        if (empty($this->blocks)) {
            return null;
        }

        preg_match('/{_BLOCK_.([A-Za-z0-9._]+?)}/', $this->blocks[0], $matches);

        return $matches[1];
    }

    /**
     * Sets the auto display option for the main template
     * @param boolean $auto_display
     */
    public function set_auto_display($auto_display)
    {
        $this->auto_display = (boolean) $auto_display;

        return $this;
    }

    /**
     * Sets the compress option from the template
     * @access public
     */
    public function set_compression($compression)
    {
        $this->use_compression = (boolean) $compression;

        return $this;
    }

    /**
     * Enable convertion of CSS styles to html inline styles
     */
    public function apply_styles_inline()
    {
        $this->apply_styles_inline = true;

        return $this;
    }

    /**
    * Destructor
    */
    public function __destruct()
    {
        // flush the content if auto display option is on
        if ($this->auto_display) {
            $main_template = $this->get_main_template();
            if ($main_template) {
                if ($this->use_layout) {
                    $this->show($main_template);
                }

                $this->flush($main_template);
            }
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
     * @param  string  $view_path
     * @param  boolean $is_failover
     * @return self
     */
    private function set_view_path($view_path, $is_failover = false)
    {
        // get the view directory path
        if (!is_dir($view_path) || !is_readable($view_path)) {
            Error::set(sprintf(self::ERROR_INVALID_VIEW_PATH, $view_path));
        }

        // set the view path or failover path if required
        if ($is_failover) {
            $this->failover_path = $view_path;
        } else {
            $this->view_path = $view_path;
        }

        return $this;
    }

    /**
     * Set the cache handler
     * @param array $options
     */
    private function set_cache(array $options)
    {
        // set the cache instance
        $this->cache = Cache::get_instance('default', $options);
    }

    /**
     * Get block strings based on l10n strings
     * @param  array  $blocks
     * @param  string $view_file
     * @return array
     */
    private function get_block_l10n(array $blocks, $view_file)
    {
        foreach ($blocks as &$block) {
            // get the 10n vars from the block. Example: [var]
            $vars = [];
            preg_match_all('|\[([A-Za-z0-9._:,{}]+?)\]|', $block, $vars);
            $keys = !empty($vars[1]) ? $vars[1] : [];
            $vars = !empty($vars[0]) ? $vars[0] : [];

            foreach ($vars as $key => $var) {
                if (!empty($var)) {
                    $string_key = $keys[$key];
                    $args = [];

                    if (false !== strpos($string_key, ':')) {
                        list($string_key, $args) = explode(':', $string_key);
                        $args = explode(',', $args);
                    }

                    // get the localization string and replace it in the block
                    $string = L10n::t($string_key, $args);
                    $block = str_replace($var, $string, $block);
                }
            }
        }

        return $blocks;
    }

    /**
     * Validates the file_path and returns the file contents
     * @param  string $file_path
     * @return string
     */
    private function get_file_content($file_path)
    {
        // get the template content
        if (!is_readable($file_path)) {
            Error::set(sprintf(self::ERROR_INVALID_VIEW_PATH, $file_path));
        }

        return file_get_contents($file_path);
    }

    /**
     * Breaks the template content into blocks
     * @param  string $template
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
        foreach ($block_parts as $key => $block) {
            // set the result array
            $res = [];

            // set block structure
            if (preg_match_all('/' . $regex . "/ims", $block, $res, PREG_SET_ORDER)) {
                // set the block parts
                $block_tag = $res[0][1];
                $block_name = $res[0][2];
                $block_content = $res[0][3];

                // is a begin block?
                if (strcmp($block_tag, 'begin') === 0) {
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
                while (preg_match('/<!--\s*include:\s*([A-Za-z0-9_]+)\s*-->/', $blocks[$tmp], $res)) {
                    // replace the tag with the block definition
                    $blocks[$tmp] = preg_replace(
                        '\''.preg_quote($res[0]).'\'', '{_BLOCK_.'.$res[1].'}', $blocks[$tmp]);
                }
            }
        }

        // send the blocks result
        return $blocks;
    }

    private function populate_element(array $element, $template)
    {
        if ($element) {
            foreach ($element as $item => $value) {
                $this->populate_value($item, $value, $template);
            }
        }
        $this->show($template);
    }

    /**
     * Populates a view value based on the type
     * @param string $item
     * @param mixed  $value
     * @param string $template
     */
    private function populate_value($item, $value, $template)
    {
        if (is_array($value)) {
            $this->populate($item, $value);
        } else {
            $this->set($item, $value, $template);
        }
    }

    /**
     * Replace the tag var with the value on a template
     * also doing some data cleaning
     * @param  string  $var
     * @param  string  $value
     * @param string template
     * @param  boolean $is_block
     * @return string
     */
    private function set_value($var, $value, $template, $is_block = true)
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
        $template = preg_replace('|{' . $var . '}|m', $value, $template);

        // final cleaning
        if (preg_match("/^\n/", $template) && preg_match("/\n$/", $template)) {
            $template = substr($template, 1, -1);
        }

        return $template;
    }

    /**
     * Add the meta tags and styles to the html type templates
     * @param string @template
     */
    private function add_headers($template)
    {
        if (!empty($this->meta_tags) || !empty($this->styles)) {
            // put the headers together
            $headers = array_merge($this->meta_tags, $this->styles);

            // set the headers as text
            $headers = implode(' ', $headers);

            // try to add the headers just before the end of the head if exists
            if (strpos($this->parsed_blocks[$template], '</head>') > 0) {
                $this->parsed_blocks[$template] =
                    str_replace('</head>', $headers . '</head>', $this->parsed_blocks[$template]);
            } else {
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
        // add lazy scripts
        if (!empty($this->lazy_scripts)) {
            $lazy_scripts = implode(' ', $this->lazy_scripts);
            $this->scripts[] = sprintf(self::LAZY_LOAD_SCRIPT, $lazy_scripts);
        }

        // include scripts
        if (!empty($this->scripts)) {
            // set the scripts as text
            $scripts = implode(' ', $this->scripts);

            // and now add the scripts at the end
            if (strpos($this->parsed_blocks[$template], '</body>') > 0) {
                $this->parsed_blocks[$template] =
                    str_replace('</body>', $scripts . '</body>', $this->parsed_blocks[$template]);
            } else {
                Error::set(sprintf(self::ERROR_INVALID_HTML_BODY, 'scripts'), Error::WARNING);
            }
        }
    }

    /**
     * Removes innecesary spaces and chars from the output
     * @param  string $output
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
        $output = preg_replace('~>\s+<~', '><', $output);
        $output = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/', '', $output);

        return $output;
    }

   /**
    * Outputs a template content
    * @param string $template
    */
    private function flush($template)
    {
        $html = $this->get_view($template);

        echo $html;
    }

    /**
     * Receives an array of key value attributes and converts them
     * into a string of key values for an HTML tags
     * @param  [array]  $attrs array of key values
     * @return [string]
     */
    private function format_attrs(array $attrs = null)
    {
        $formatted_attrs = '';
        if ($attrs) {
            $KEY_VALUE_PAIR = '%s="%s"';
            $attrs_key_value_pair = [];
            foreach ($attrs as $key => $value) {
                $attrs_key_value_pair[] = sprintf($KEY_VALUE_PAIR, $key, $value);
            }
            $formatted_attrs = implode(' ', $attrs_key_value_pair);
        }

        return $formatted_attrs;
    }

}
