<?php
/**
 * Copyright (c) Tijs Verkoyen. All rights reserved. Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 *
 * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 * The name of the author may not be used to endorse or promote products derived from this software without specific prior written permission.
 * This software is provided by the author "as is" and any express or implied warranties, including, but not limited to, the implied warranties of merchantability and fitness for a particular purpose are disclaimed. In no event shall the author be liable for any direct, indirect, incidental, special, exemplary, or consequential damages (including, but not limited to, procurement of substitute goods or services; loss of use, data, or profits; or business interruption) however caused and on any theory of liability, whether in contract, strict liability, or tort (including negligence or otherwise) arising in any way out of the use of this software, even if advised of the possibility of such damage.
 */
namespace Kima\Html;

use Kima\Error;
use DOMDocument;
use DOMXPath;

/**
 * HTML CSS to Inline library
 * Based on https://github.com/tijsverkoyen/CssToInlineStyles
 */
class CssToInline
{

    /**
     * Error messages
     */
    const ERROR_NO_HTML = 'No HTML was provided';

    /**
     * Original styles
     */
    const ORIGINAL_STYLES = 'data-original-styles';

    /**
     * The HTML to process
     * @var string
     */
    private $html;

    /**
     * The css
     * @var string
     */
    private $css;

    /**
     * The css rules
     * @var array
     */
    private $css_rules;

    /**
     * Should the generated HTML be cleaned
     * @var bool
     */
    private $cleanup = true;

    /**
     * The encoding to use
     * @var string
     */
    private $encoding = 'UTF-8';

    /**
     * Include the media queries to the inlined styles
     * @var bool
     */
    private $include_media_queries = false;

    /**
     * Creates a CssToInline instance
     * Optionally sets the html and css to use
     * @param string $html The HTML to process.
     * @param string $css  The CSS to use.
     */
    public function __construct($html = null, $css = null)
    {
        $this->set_html($html);
        $this->set_css($css);
    }

    /**
     * Set HTML to process
     * @param string $html The HTML to process.
     */
    public function set_html($html)
    {
        $this->html = (string) $html;
    }

    /**
     * Set CSS to use
     * @param string $css The CSS to use.
     */
    public function set_css($css)
    {
        $this->css = (string) $css;
    }

    /**
     * Sets the cleanup parameter on
     * The ids, classes and style tags will be removed
     */
    public function cleanup()
    {
        $this->cleanup = true;
    }

    /**
     * Set the encoding to use with the DOMDocument
     * @param string $encoding
     */
    public function set_encoding($encoding)
    {
        $this->encoding = (string) $encoding;
    }

    /**
     * Include the media queries in the css conversion
     */
    public function include_media_queries()
    {
        $this->include_media_queries = true;
    }

    /**
     * Convert HTML/CSS to HTML inline style
     * @return string
     */
    public function convert()
    {
        if (empty($this->html)) {
            Error::set(self::ERROR_NO_HTML);
        }

        // process css
        $this->process_css();

        // create new DOMDocument
        $document = new DOMDocument('1.0', $this->get_encoding());

        // set error level
        libxml_use_internal_errors(true);

        // load HTML and create new XPath
        $document->loadHTML($this->html);
        $xpath = new DOMXPath($document);

        if (!empty($this->css_rules)) {
            // apply every rule
            foreach ($this->css_rules as $rule) {
                // get query
                $query = $this->build_xpath_query($rule['selector']);
                if (empty($query)) {
                    continue;
                }

                // search elements
                $elements = $xpath->query($query);
                if (empty($elements)) {
                    continue;
                }

                // loop found elements
                foreach ($elements as $element) {
                    $this->set_original_styles($element);

                    // get the properties
                    $styles_attribute = $element->attributes->getNamedItem('style');
                    $properties = $this->get_properties($styles_attribute);

                    // set the properties
                    $this->set_properties($rule['properties'], $properties, $element);
                }
            }

            // reapply original styles
            $query = $this->build_xpath_query('*[@' . self::ORIGINAL_STYLES . ']');
            if (false === $query) {
                return;
            }

            // search elements
            $elements = $xpath->query($query);

            // loop found elements
            foreach ($elements as $element) {
                // get the original styles
                $original_style =
                    $element->attributes->getNamedItem(self::ORIGINAL_STYLES);
                $original_properties = $this->get_properties($original_style);

                if (!empty($original_properties)) {
                    // get current styles
                    $styles_attribute = $element->attributes->getNamedItem('style');
                    $properties = $this->get_properties($styles_attribute);

                    // set the properties
                    $this->set_properties($original_properties, $properties, $element);
                }

                // remove placeholder
                $element->removeAttribute(self::ORIGINAL_STYLES);
            }
        }

        // get the HTML
        $html = $document->saveHTML();

        // cleanup the HTML if we need to
        if ($this->cleanup) {
            $html = $this->cleanup_html($html);
        }

        return $html;
    }

    /**
     * Gets the style properties for an atribute
     * @param  DOMAtrr
     * @return array
     */
    private function get_properties($attribute)
    {
        $properties = [];

        // get current styles
        if (null !== $attribute) {
            // get value for the styles attribute
            $defined_styles = $attribute->value;

            // split into properties
            $defined_properties = explode(';', $defined_styles);

            // loop properties
            foreach ($defined_properties as $property) {
                // validate property
                if (empty($property)) {
                    continue;
                }

                // split into chunks
                $chunks = explode(':', trim($property), 2);

                if (isset($chunks[1])) {
                    $properties[$chunks[0]] = (array) trim($chunks[1]);
                }
            }
        }

        return $properties;
    }

    /**
     * Sets the properties to the defined element
     * @param array      $original_properties
     * @param array      $properties
     * @param DOMELement $element
     */
    private function set_properties($original_properties, $properties, &$element)
    {
        // add new properties into the list
        foreach ($original_properties as $key => $value) {
            $properties[$key] = $value;
        }

        // build string
        $property_chunks = [];

        // build chunks
        foreach ($properties as $key => $values) {
            foreach ($values as $value) {
                $property_chunks[] = $key . ': ' . $value . ';';
            }
        }

        // build properties string
        $properties_string = implode(' ', $property_chunks);

        // set attribute
        if (!empty($properties_string)) {
            $element->setAttribute('style', $properties_string);
        }
    }

    /**
     * Convert a CSS-selector into an xpath-query
     * @return string
     * @param  string $selector The CSS-selector.
     */
    private function build_xpath_query($selector)
    {
        // redefine
        $selector = $selector;

        // the CSS selector
        $css_selector = [
            // E F, Matches any F element that is a descendant of an E element
            '/(\w)\s+([\w\*])/',
            // E > F, Matches any F element that is a child of an element E
            '/(\w)\s*>\s*([\w\*])/',
            // E:first-child, Matches element E when E is the first child of its parent
            '/(\w):first-child/',
            // E + F, Matches any F element immediately preceded by an element
            '/(\w)\s*\+\s*(\w)/',
            // E[foo], Matches any E element with the "foo" attribute set (whatever the value)
            '/(\w)\[([\w\-]+)]/',
            // E[foo="warning"], Matches any E element whose "foo" attribute value is exactly equal to "warning"
            '/(\w)\[([\w\-]+)\=\"(.*)\"]/',
            // div.warning, HTML only. The same as DIV[class~="warning"]
            '/(\w+|\*)+\.([\w\-]+)+/',
            // .warning, HTML only. The same as *[class~="warning"]
            '/\.([\w\-]+)/',
            // E#myid, Matches any E element with id-attribute equal to "myid"
            '/(\w+)+\#([\w\-]+)/',
            // #myid, Matches any element with id-attribute equal to "myid"
            '/\#([\w\-]+)/'
        ];

        // the xPath-equivalent
        $xpath_query = [
            // E F, Matches any F element that is a descendant of an E element
            '\1//\2',
            // E > F, Matches any F element that is a child of an element E
            '\1/\2',
            // E:first-child, Matches element E when E is the first child of its parent
            '*[1]/self::\1',
            // E + F, Matches any F element immediately preceded by an element
            '\1/following-sibling::*[1]/self::\2',
            // E[foo], Matches any E element with the "foo" attribute set (whatever the value)
            '\1 [ @\2 ]',
            // E[foo="warning"], Matches any E element whose "foo" attribute value is exactly equal to "warning"
            '\1[ contains( concat( " ", @\2, " " ), concat( " ", "\3", " " ) ) ]',
            // div.warning, HTML only. The same as DIV[class~="warning"]
            '\1[ contains( concat( " ", @class, " " ), concat( " ", "\2", " " ) ) ]',
            // .warning, HTML only. The same as *[class~="warning"]
            '*[ contains( concat( " ", @class, " " ), concat( " ", "\1", " " ) ) ]',
            // E#myid, Matches any E element with id-attribute equal to "myid"
            '\1[ @id = "\2" ]',
            // #myid, Matches any element with id-attribute equal to "myid"
            '*[ @id = "\1" ]'
        ];

        // return
        $xpath = (string) '//' . preg_replace($css_selector, $xpath_query, $selector);

        return str_replace('] *', ']//*', $xpath);
    }

    /**
     * Sets the original styles in the html to the DOMElement
     * @param DOMElement $element
     */
    private function set_original_styles(&$element)
    {
        // no styles stored?
        if (null == $element->attributes->getNamedItem(self::ORIGINAL_STYLES)) {
            $original_style = '';
            if (null !== $element->attributes->getNamedItem('style')) {
                $original_style =
                    $element->attributes->getNamedItem('style')->value;
            }

            // store original styles
            $element->setAttribute(self::ORIGINAL_STYLES, $original_style);

            // clear the styles
            $element->setAttribute('style', '');
        }
    }

    /**
     * Cleanup the generated HTML
     *
     * @return string
     * @param  string $html The HTML to cleanup.
     */
    private function cleanup_html($html)
    {
        // remove classes
        $html = preg_replace('/(\s)+class="(.*)"(\s)+/U', ' ', $html);

        // remove IDs
        $html = preg_replace('/(\s)+id="(.*)"(\s)+/U', ' ', $html);

        // remove style tags
        $html = preg_replace('|<style(.*)>(.*)</style>|isU', '', $html);
        $html = preg_replace('|<link(.*)>(.*)>|isU', '', $html);

        // return
        return $html;
    }

    /**
     * Get the encoding to use
     * @return string
     */
    private function get_encoding()
    {
        return $this->encoding;
    }

    /**
     * Process the loaded CSS
     *
     * @return void
     */
    private function process_css()
    {
        // init vars
        $css = $this->clean_css($this->css);

        if (!$this->include_media_queries) {
            $css = preg_replace('/@media [^{]*{([^{}]|{[^{}]*})*}/', '', $css);
        }

        // rules are splitted by }
        $rules = explode('}', $css);

        // init var
        $i = 1;

        // loop rules
        foreach ($rules as $rule) {
            // split into chunks
            $chunks = explode('{', $rule);

            // invalid rule?
            if (!isset($chunks[1])) {
                continue;
            }

            // set the selectors
            $selectors = trim($chunks[0]);

            // get cssProperties
            $cssProperties = trim($chunks[1]);

            // split multiple selectors
            $selectors = explode(',', $selectors);

            // loop selectors
            foreach ($selectors as $selector) {
                // cleanup
                $selector = trim($selector);

                // build an array for each selector
                $ruleSet = [];

                // store selector
                $ruleSet['selector'] = $selector;

                // process the properties
                $ruleSet['properties'] =
                    $this->process_properties($cssProperties);

                // calculate specifity
                $ruleSet['specifity'] = $this->get_specifity($selector) + $i;

                // add into global rules
                $this->css_rules[] = $ruleSet;
            }

            // increment
            $i++;
        }

        // sort based on specifity
        if (!empty($this->css_rules)) {
            usort($this->css_rules, [__CLASS__, 'sort_on_specifity']);
        }
    }

    /**
     * Cleans the css string
     * @param  string $css
     * @return string
     */
    private function clean_css($css)
    {
        // remove newlines
        $css = str_replace(["\r", "\n"], '', $css);

        // replace double quotes by single quotes
        $css = str_replace('"', '\'', $css);

        // remove comments
        $css = preg_replace('|/\*.*?\*/|', '', $css);

        // remove spaces
        $css = preg_replace('/\s\s+/', ' ', $css);

        return $css;
    }

    /**
     * Process the CSS-properties
     * @param  string $property_string The CSS-properties.
     * @return array
     */
    private function process_properties($property_string)
    {
        // split into chunks
        $properties = explode(';', $property_string);

        // init var
        $pairs = [];

        // loop properties
        foreach ($properties as $property) {
            $chunks = explode(':', $property, 2);

            // validate
            if(!isset($chunks[1])) continue;

            // cleanup
            $chunks[0] = trim($chunks[0]);
            $chunks[1] = trim($chunks[1]);

            // add to pairs array
            if (!isset($pairs[$chunks[0]])
                || !in_array($chunks[1], $pairs[$chunks[0]]))
            {
                $pairs[$chunks[0]][] = $chunks[1];
            }
        }

        // sort the pairs
        ksort($pairs);

        // return
        return $pairs;
    }

    /**
     * Calculate the specifity for the CSS-selector
     *
     * @return int
     * @param  string $selector The selector to calculate the specifity for.
     */
    private function get_specifity($selector)
    {
        // cleanup selector
        $selector = str_replace(['>', '+'], [' > ', ' + '], $selector);

        // init var
        $specifity = 0;

        // split the selector into chunks based on spaces
        $chunks = explode(' ', $selector);

        // loop chunks
        foreach ($chunks as $chunk) {
            // an ID is important, so give it a high specifity
            if (false !== strstr($chunk, '#')) {
                $specifity += 100;
            }
            // classes are more important than a tag, but less important then an ID
            else if ((strstr($chunk, '.'))) {
                $specifity += 10;
            }
            // anything else isn't that important
            else {
                $specifity += 1;
            }
        }

        // return
        return $specifity;
    }

    /**
     * Sort an array on the specifity element
     * @param  array $e1 The first element.
     * @param  array $e2 The second element.
     * @return int
     */
    private function sort_on_specifity($e1, $e2)
    {
        // lower
        if ($e1['specifity'] < $e2['specifity']) {
            return -1;
        }

        // higher
        if ($e1['specifity'] > $e2['specifity']) {
            return 1;
        }

        // fallback
        return 0;
    }

}
