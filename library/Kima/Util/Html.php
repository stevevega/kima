<?php
/**
 * Namespace
 */
namespace Kima\Util;

/**
 * Namespaces to use
 */
use \DOMDocument;
use \DOMXPath;

/**
 * Html
 *
 * Html Util functions
  */
class Html
{

    /**
     * Gets text from a html file/string
     */
    public static function get_text($html, $is_file = false)
    {
        $doc = self::load_html($html, $is_file);

        $remove_tags = array('script', 'link', 'style');
        $doc = self::remove_tags($doc, $remove_tags);

        $xpath = new DOMXPath($doc);
        $values = array();
        $last_line = 0;

        foreach ($xpath->query("//html") as $q) {
            $node_lines = substr_count($q->nodeValue, "\n");
            $current_line = $q->getLineNo() - $node_lines;

            if ($current_line==$last_line) {
                $last_key = key(array_slice($values, -1, 1, TRUE));
                $values[$last_key] = $values[$last_key] . $q->nodeValue;
            } else {
                $values[] = $q->nodeValue;
            }

            $last_line = $q->getLineNo();
        }
        var_dump($values);

        var_dump($doc->saveHTML());
    }

    /**
     * Loads html from string or file
     */
    public static function load_html($html, $is_file = false)
    {
        $doc = new DOMDocument();
        $result = $is_file ? $doc->loadHTMLFile($html) : $doc->loadHTML($html);

        if (!$result) {
            $html_source = $is_file ? 'file' : 'string';
            Error::set(__METHOD__, 'Unable to load HTML from ' . $html_source);
        }

        return $doc;
    }

    /**
     * Removes a list of tags of an html DOM
     */
    public static function remove_tags(DOMDocument $doc, array $remove_tags)
    {
        foreach ($remove_tags as $remove_tag) {
            $tags = $doc->getElementsByTagName($remove_tag);
            $tags_length = $tags->length;

            for ($i = 0; $i < $tags_length; $i++) {
                $tags->item(0)->parentNode->removeChild($tags->item(0));
            }
        }

        return $doc;
    }

}
