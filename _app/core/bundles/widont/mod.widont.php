<?php
/**
 * Modifier_widont
 * Attempts to prevent widows in a variable's output
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_widont extends Modifier
{
    public function index($value, $parameters=array()) {
        // thanks to Shaun Inman for inspriation here
        // http://www.shauninman.com/archive/2008/08/25/widont_2_1_1
    
        // if there are content tags
        if (preg_match("/<\/(?:p|li|h1|h2|h3|h4|h5|h6|figcaption)>/ism", $value)) {
            // step 1, replace spaces in HTML tags with a code
            $value = preg_replace_callback("/<.*?>/ism", function($matches) {
                return str_replace(' ', '%###%##%', $matches[0]);
            }, $value);
            
            // step 2, replace last space with &nbsp;
            $value = preg_replace("/(?<!<[p|li|h1|h2|h3|h4|h5|h6|div|figcaption])([^\s])[ \t]+([^\s]+(?:[\s]*<\/(?:p|li|h1|h2|h3|h4|h5|h6|div|figcaption)>))$/im", "$1&nbsp;$2", rtrim($value));
    
            // step 3, re-replace the code from step 1 with spaces
            return str_replace("%###%##%", " ", $value);
            
        // otherwise
        } else {
            return preg_replace("/([^\s])\s+([^\s]+)\s*$/im", "$1&nbsp;$2", rtrim($value));
        }
    }
}