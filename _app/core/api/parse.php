<?php

use \Michelf\MarkdownExtra;
use \Michelf\SmartyPants;
use \Michelf\SmartyPantsTypographer;
use Netcarver\Textile\Parser as Textile;

/**
 * Parse
 * API for parsing different types of content and templates
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class Parse
{
    private static $parsers = array();
    
    /**
     * Parse a block of YAML into PHP
     *
     * @param string  $yaml  YAML-formatted string to parse
     * @return array
     */
    public static function yaml($yaml)
    {
        return YAML::parse($yaml);
    }

    /**
     * Parse and transform Markdown strings into HTML
     *
     * @param string  $string  text to transform
     * @return string
     */
    public static function markdown($string)
    {
        // start measuring
        $hash = Debug::markStart('parsing', 'markdown');
        
        // check for parser, create if needed
        if (!isset(self::$parsers['markdown'])) {
            if (strtolower(Config::get('markdown_parser', 'standard')) === "parsedown") {
                $parser = new ParsedownExtra();
	            $parser->setUrlsLinked(Config::get('markdown:convert_urls_to_links', true));

            } else {
                $parser = new MarkdownExtra;

                $parser->no_markup         = Config::get('markdown:no_markup', false);
                $parser->no_entities       = Config::get('markdown:no_entities', false);
                $parser->predef_urls       = Config::get('markdown:predefined_urls', array());
                $parser->predef_abbr       = Config::get('markdown:predefined_abbreviations', array());
                $parser->code_class_prefix = Config::get('markdown:code_class_prefix', '');
                $parser->code_attr_on_pre  = Config::get('markdown:code_attr_on_pre', false);
            }

            self::$parsers['markdown'] = $parser;
        }

        // parse for markdown
        if (strtolower(Config::get('markdown_parser', 'standard')) === "parsedown") {
            $result = self::$parsers['markdown']->text($string);
        } else {
            $result = self::$parsers['markdown']->transform($string);
        }
        
        // end measuring
        Debug::markEnd($hash);
        Debug::increment('parses', 'markdown');
        
        return $result;
    }

    /**
     * Parse and transform Textile strings into HTML
     *
     * @param string  $string  text to transform
     * @return string
     */
    public static function textile($string)
    {
        // start measuring
        $hash = Debug::markStart('parsing', 'textile');
        
        $parser = new Textile();
        $result = $parser->textileThis($string);

        // end measuring
        Debug::markEnd($hash);
        Debug::increment('parses', 'textile');
        
        return $result;
    }

    /**
     * Translate plain ASCII punctuation characters into "smart" typographic punctuation HTML entities.
     *
     * @param string  $string  text to transform
     * @return array
     */
    public static function smartypants($string)
    {
        // start measuring
        $hash = Debug::markStart('parsing', 'smartypants');

        if (Config::get('enable_smartypants', true) === 'typographer') {            
            $result = SmartyPantsTypographer::defaultTransform($string);
        } else {
            $result = SmartyPants::defaultTransform($string);
        }

        // end measuring
        Debug::markEnd($hash);
        Debug::increment('parses', 'smartypants');
        
        return $result;
    }


    /**
     * Parses a template, replacing variables with their values
     *
     * @param string  $html  HTML template to parse
     * @param array  $variables  List of variables ($key => $value) to replace into template
     * @param mixed  $callback  Callback to call when done
     * @param array  $context  Context to use when parsing
     * @return string
     */
    public static function template($html, $variables, $callback = array('statamic_view', 'callback'), $context=array())
    {
        // start measuring
        $hash = Debug::markStart('parsing', 'statamic_tmpl');
        
        if (!isset(self::$parsers['template_parser'])) {
            $parser = new \Lex\Parser();
            $parser->cumulativeNoparse(TRUE);

            self::$parsers['template_parser'] = $parser;
        }
        
        $result = self::$parsers['template_parser']->parse($html, ($variables + $context), $callback, Config::get('_allow_php', false));

        // end measuring
        Debug::markEnd($hash);
        Debug::increment('parses', 'statamic_tmpl');
        
        return $result;
    }


    /**
     * Parses a template with context, shortcut to Parse::template with context variable
     * 
     * @param string  $html  HTML template to parse
     * @param array  $variables  List of variables ($key => $value) to replace into template
     * @param array  $context  List of contextual variables to merge variables into
     * @param mixed  $callback  Callback to call when done
     * @return string 
     */
    public static function contextualTemplate($html, $variables, $context=array(), $callback = array('statamic_view', 'callback'))
    {
        return self::template($html, $variables, $callback, $context);
    }


    /**
     * Parses a tag loop, replacing template variables with each array in a list of arrays
     *
     * @param string  $content  Template for replacing
     * @param array  $data  Array of arrays containing values
     * @param bool  $supplement  Supplement each loop with contextual information?
     * @param array  $context  Contextual data to add into loop
     * @return string
     */
    public static function tagLoop($content, $data, $supplement = false, $context=array())
    {
        $output = '';

        if ($supplement) {
            // loop through each record of $data
            $i = 1;
            $count = count($data);
            
            foreach ($data as $item) {
                $item['first']         = ($i === 1);
                $item['last']          = ($i === $count);
                $item['index']         = $i;
                $item['zero_index']    = $i - 1;
                $item['total_results'] = $count;
                
                $output .= Parse::contextualTemplate($content, $item, $context);

                $i++;
            }

        } else {
            foreach ($data as $item) {
                $output .= Parse::contextualTemplate($content, $item, $context, array('statamic_view', 'callback'));
            }
        }

        return $output;
    }

    /**
     * Checks for and parses front matter
     *
     * @param string  $string  Content to parse
     * @return array
     */
    public static function frontMatter($string, $yamlize = true)
    {
        $data = array();
        $content = $string;

        if (Pattern::startsWith($string, "---")) {
            list($yaml, $content) = preg_split("/\n---/", $string, 2, PREG_SPLIT_NO_EMPTY);

            if ($yamlize) {
                $data = self::yaml($yaml);
            }
        }

        return compact('data', 'content');
    }


    /**
     * Parses a conditions string
     *
     * @param string  $conditions  Conditions to parse
     * @return array
     */
    public static function conditions($conditions)
    {
        // start measuring
        $hash = Debug::markStart('parsing', 'conditions');
        Debug::increment('parses', 'condition_statements');
        $replacement = '__TEMP_COMMA_' . substr(md5(time()), 0, 12) . '__';
        
        $conditions = explode(",", str_replace('\,', $replacement, $conditions));
        $output = array();

        foreach ($conditions as $condition) {
            Debug::increment('parses', 'conditions');
            $result = Parse::condition(str_replace($replacement, ',', $condition));
            $output[$result['key']] = $result['value'];
        }

        // end measuring
        Debug::markEnd($hash);

        return $output;
    }


    /**
     * Recursively parses a condition (key:value), returning the key and value
     *
     * @param string  $condition  Condition to parse
     * @return array
     */
    public static function condition($condition)
    {
        // has a colon, is a comparison
        if (strstr($condition, ":") !== false) {
            // breaks this into key => value
            $parts  = explode(":", $condition, 2);

            $condition_array = array(
                "key" => trim($parts[0]),
                "value" => Parse::conditionValue(trim($parts[1]))
            );

        // doesn't have a colon, looking for existence (or lack thereof)
        } else {
            $condition = trim($condition);
            $condition_array = array(
                "key" => $condition,
                "value" => array()
            );

            if (substr($condition, 0, 1) === "!") {
                $condition_array['key'] = substr($condition, 1);
                $condition_array['value'] = array(
                    "kind" => "existence",
                    "type" => "lacks"
                );
            } else {
                $condition_array['value'] = array(
                    "kind" => "existence",
                    "type" => "has"
                );
            }
        }

        // return the parsed array
        return $condition_array;
    }


    /**
     * Recursively parses a condition, returning the key and value
     *
     * @param string  $value  Condition to parse
     * @return array
     */
    public static function conditionValue($value)
    {
        // found a bar, split this
        if (strstr($value, "|")) {
            if (substr($value, 0, 4) == "not ") {
                $item = array(
                    "kind" => "comparison",
                    "type" => "not in",
                    "value" => explode("|", substr($value, 4))
                );
            } else {
                $item = array(
                    "kind" => "comparison",
                    "type" => "in",
                    "value" => explode("|", $value)
                );
            }
        } else {
            if (substr($value, 0, 4) == "not ") {
                $item = array(
                    "kind" => "comparison",
                    "type" => "not equal",
                    "value" => substr($value, 4)
                );
            } elseif (substr($value, 0, 1) == "!") {
                $item = array(
                    "kind" => "comparison",
                    "type" => "not equal",
                    "value" => substr($value, 1)
                );
            } elseif (substr($value, 0, 2) == "! ") {
                $item = array(
                    "kind" => "comparison",
                    "type" => "not equal",
                    "value" => substr($value, 2)
                );
            } elseif (substr($value, 0, 2) == "<=") {
                // less than or equal to
                $item = array(
                    "kind" => "comparison",
                    "type" => "less than or equal to",
                    "value" => substr($value, 2)
                );
            } elseif (substr($value, 0, 3) == "<= ") {
                // less than or equal to
                $item = array(
                    "kind" => "comparison",
                    "type" => "less than or equal to",
                    "value" => substr($value, 3)
                );
            } elseif (substr($value, 0, 2) == ">=") {
                // greater than or equal to
                $item = array(
                    "kind" => "comparison",
                    "type" => "greater than or equal to",
                    "value" => substr($value, 2)
                );
            } elseif (substr($value, 0, 3) == ">= ") {
                // greater than or equal to
                $item = array(
                    "kind" => "comparison",
                    "type" => "greater than or equal to",
                    "value" => substr($value, 3)
                );
            } elseif (substr($value, 0, 1) == ">") {
                // greater than
                $item = array(
                    "kind" => "comparison",
                    "type" => "greater than",
                    "value" => substr($value, 1)
                );
            } elseif (substr($value, 0, 2) == "> ") {
                // greater than
                $item = array(
                    "kind" => "comparison",
                    "type" => "greater than",
                    "value" => substr($value, 2)
                );
            } elseif (substr($value, 0, 1) == "<") {
                // less than
                $item = array(
                    "kind" => "comparison",
                    "type" => "less than",
                    "value" => substr($value, 1)
                );
            } elseif (substr($value, 0, 2) == "< ") {
                // less than
                $item = array(
                    "kind" => "comparison",
                    "type" => "less than",
                    "value" => substr($value, 2)
                );
            } elseif (substr($value, 0, 1) == '~') {
                // contains
                $item = array(
                    'kind' => 'comparison',
                    'type' => 'contains text',
                    'value' => substr($value, 1)
                );
            } elseif (substr($value, 0, 2) == '~ ') {
                // contains
                $item = array(
                    'kind' => 'comparison',
                    'type' => 'contains text',
                    'value' => substr($value, 2)
                );
            } elseif (substr($value, 0, 9) == 'contains ') {
                // contains
                $item = array(
                    'kind' => 'comparison',
                    'type' => 'contains text',
                    'value' => substr($value, 9)
                );
            } else{
                $item = array(
                    "kind" => "comparison",
                    "type" => "equal",
                    "value" => $value
                );
            }
        }

        return $item;
    }


    /**
     * Parses a string or pipe-delimited string into an array
     * 
     * @param mixed  $list  String to parse
     * @return array
     */
    public static function pipeList($list)
    {
        $output = array();

        // make an array of all options
        if (is_array($list)) {
            foreach ($list as $list_item) {
                if (strpos($list_item, "|") !== false) {
                    $output = explode("|", $list_item) + $output;
                } else {
                    array_push($output, $list_item);
                }
            }
        } else {
            if (strpos($list, "|") !== false) {
                $output = explode("|", $list);
            } else {
                array_push($output, $list);
            }
        }

        // now fix the array
        if (!count($output)) {
            $output = array();
        } else {
            $output = array_map(function($item) {
                return Path::removeStartingSlash($item);
            }, $output);
        }

        return array_unique($output);
    }

    /**
     * Parse from mixed sources
     *
     * @param string  $file  a filename or string to retreive data from
     * @param bool    $type  if the type is known, you can pass it
     * @return array
     **/
    public static function mixed($file, $type = false)
    {
        if ( ! $type) {
            // resolve type
        }

        switch ($type) {
            case "md":
            case "markdown":
                return Statamic::yamlize_content($file);
            case "textile":
                return Statamic::yamlize_content($file);
            case "yaml":
                return self::yaml($file);
            case "json":
                return json_decode($file);
            default:
                return false;
        }
    }
    


    /**
     * Parse for modifier aliases
     * 
     * @param string  $original_modifier  Original modifier to check for
     * @return string
     */
    public static function modifierAlias($original_modifier)
    {
        switch ($original_modifier) {
            case "+":
                return "add";
            
            case "-":
                return "subtract";
            
            case "*":
                return "multiply";
            
            case "/":
                return "divide";
            
            case "%":
                return "mod";
            
            case "^":
                return "exponent";
            
            case "ago":
            case "until":
            case "since":
                return "relative";
            
            case "specialchars":
                return "sanitize";
            
            default:
                return $original_modifier;
        }
    }
}