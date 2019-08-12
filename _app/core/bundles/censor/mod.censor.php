<?php
/**
 * Modifier_censor
 * Censors certain words out of a given string
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_censor extends Modifier
{
    const CARTOON = 1;
    const REDACT  = 2;
    const STARS   = 4;
    

    public function index($value, $parameters=array()) {
        $bad_word_segments     = $this->fetchConfig("bad_word_segments", "", "is_array", false, false);
        $bad_word_singles      = $this->fetchConfig("bad_word_singles", "", "is_array", false, false);
        $leave_first_character = $this->fetchConfig("leave_first_character", false, null, true);
        
        $type = "cartoon";
        if (isset($parameters[0])) {
            $type = $parameters[0];
        } elseif ($this->fetchConfig('replacement_mode')) {
            $type = $this->fetchConfig('replacement_mode');
        }
        
        // determine the type of replacement we're doing
        switch (strtolower($type)) {
            case "redact":
                $mode = self::REDACT;
                break;
            
            case "stars":
                $mode = self::STARS;
                break;
            
            default:
                $mode = self::CARTOON;
        }

        // perform the replacement with the configured words
        if ($mode === self::CARTOON) {
            return preg_replace_callback("/(" . join("|", $bad_word_segments) . "|\b" . join("\b|\b", $bad_word_singles) . "\b)/ism", function($word) use ($leave_first_character) {
                $word_length = strlen($word[1]);
                $replacement = "";

                if ($leave_first_character) {
                    $word_length--;
                    $replacement = substr($word[1], 0, 1);
                }

                return $replacement . $this->randomString($word_length);
            }, $value);
        } elseif ($mode === self::REDACT) {
            return preg_replace_callback("/(" . join("|", $bad_word_segments) . "|\b" . join("\b|\b", $bad_word_singles) . "\b)/ism", function($word) {
                $word_length = ceil(strlen($word[1]) / 2);

                return str_repeat("█", $word_length);
            }, $value);
        } elseif ($mode === self::STARS) {
            return preg_replace_callback("/(" . join("|", $bad_word_segments) . "|\b" . join("\b|\b", $bad_word_singles) . "\b)/ism", function($word) {
                $word_length = strlen($word[1]);

                return str_repeat("*", $word_length);
            }, $value);
        }
        
        // the code should never get here, but just in case
        return $value;
    }

    
    /**
     * Generate a random string based on this modifier's config file
     * 
     * @param integer  $length  Length of the string to generate
     * @return string
     */
    private function randomString($length) {
        $string      = $this->fetchConfig("replacement_characters", "@#$%!?*&", null, false, false);
        $use_shuffle = $this->fetchConfig("shuffle_replacement_characters", false, null, true, false);
        $characters  = ($use_shuffle) ? str_shuffle($string) : $string;

        $characters = str_repeat($characters, ceil($length / strlen($string)));
        return substr($characters, 0, $length);
    }
}