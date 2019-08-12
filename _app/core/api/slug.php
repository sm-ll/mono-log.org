<?php
/**
 * Slug
 * API for interacting and manipulating slugs
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class Slug
{
    /**
     * Creates a slug from a given $string
     * This is a PHP-port of https://github.com/pid/speakingurl
     *
     * @param string  $string  Value to make slug from
     * @param array  $options  Array of options for how slugs are created
     * @return string
     */
    public static function make($string, $options=array())
    {
        // merge in custom replacements set in settings
        if (Config::get('custom_transliteration') && is_array(Config::get('custom_transliteration'))) {
            if (!isset($options['custom_replacements']) || !is_array($options['custom_replacements'])) {
                $options['custom_replacements'] = Config::get('custom_transliteration');
            } else {
                $options['custom_replacements'] = Config::get('custom_transliteration') + $options['custom_replacements'];
            }
        }
        
        // langauge-specific replacements
        $symbol_map = array(
            'ar' => array(
                '∆' => 'delta',
                '∞' => 'la-nihaya',
                '♥' => 'hob',
                '&' => 'wa',
                '|' => 'aw',
                '<' => 'aqal-men',
                '>' => 'akbar-men',
                '∑' => 'majmou',
                '¤' => 'omla'
            ),
    
            'de' => array(
                '∆' => 'delta',
                '∞' => 'unendlich',
                '♥' => 'Liebe',
                '&' => 'und',
                '|' => 'oder',
                '<' => 'kleiner als',
                '>' => 'groesser als',
                '∑' => 'Summe von',
                '¤' => 'Waehrung'
            ),
    
            'nl' => array(
                '∆' => 'delta',
                '∞' => 'oneindig',
                '♥' => 'liefde',
                '&' => 'en',
                '|' => 'of',
                '<' => 'kleiner dan',
                '>' => 'groter dan',
                '∑' => 'som',
                '¤' => 'valuta'
            ),
    
            'en' => array(
                '∆' => 'delta',
                '∞' => 'infinity',
                '♥' => 'love',
                '&' => 'and',
                '|' => 'or',
                '<' => 'less than',
                '>' => 'greater than',
                '∑' => 'sum',
                '¤' => 'currency'
            ),
    
            'es' => array(
                '∆' => 'delta',
                '∞' => 'infinito',
                '♥' => 'amor',
                '&' => 'y',
                '|' => 'u',
                '<' => 'menos que',
                '>' => 'mas que',
                '∑' => 'suma de los',
                '¤' => 'moneda'
            ),
    
            'fr' => array(
                '∆' => 'delta',
                '∞' => 'infiniment',
                '♥' => 'Amour',
                '&' => 'et',
                '|' => 'ou',
                '<' => 'moins que',
                '>' => 'superieure a',
                '∑' => 'somme des',
                '¤' => 'monnaie'
            ),
    
            'pt' => array(
                '∆' => 'delta',
                '∞' => 'infinito',
                '♥' => 'amor',
                '&' => 'e',
                '|' => 'ou',
                '<' => 'menor que',
                '>' => 'maior que',
                '∑' => 'soma',
                '¤' => 'moeda'
            ),
    
            'ru' => array(
                '∆' => 'delta',
                '∞' => 'beskonechno',
                '♥' => 'lubov',
                '&' => 'i',
                '|' => 'ili',
                '<' => 'menshe',
                '>' => 'bolshe',
                '∑' => 'summa',
                '¤' => 'valjuta'
            )
        );
        
        $char_map = array(
            // latin
            'À' => 'A',
            'Á' => 'A',
            'Â' => 'A',
            'Ã' => 'A',
            'Ä' => 'Ae',
            'Å' => 'A',
            'Æ' => 'AE',
            'Ç' => 'C',
            'È' => 'E',
            'É' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'Ì' => 'I',
            'Í' => 'I',
            'Î' => 'I',
            'Ï' => 'I',
            'Ð' => 'D',
            'Ñ' => 'N',
            'Ò' => 'O',
            'Ó' => 'O',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ö' => 'Oe',
            'Ő' => 'O',
            'Ø' => 'O',
            'Ù' => 'U',
            'Ú' => 'U',
            'Û' => 'U',
            'Ü' => 'Ue',
            'Ű' => 'U',
            'Ý' => 'Y',
            'Þ' => 'TH',
            'ß' => 'ss',
            'à' => 'a',
            'á' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'ä' => 'ae',
            'å' => 'a',
            'æ' => 'ae',
            'ç' => 'c',
            'è' => 'e',
            'é' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'ì' => 'i',
            'í' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ð' => 'd',
            'ñ' => 'n',
            'ò' => 'o',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ö' => 'oe',
            'ő' => 'o',
            'ø' => 'o',
            'ù' => 'u',
            'ú' => 'u',
            'û' => 'u',
            'ü' => 'ue',
            'ű' => 'u',
            'ý' => 'y',
            'þ' => 'th',
            'ÿ' => 'y',
            'ẞ' => 'SS',
            
            // greek
            'α' => 'a',
            'β' => 'b',
            'γ' => 'g',
            'δ' => 'd',
            'ε' => 'e',
            'ζ' => 'z',
            'η' => 'h',
            'θ' => '8',
            'ι' => 'i',
            'κ' => 'k',
            'λ' => 'l',
            'μ' => 'm',
            'ν' => 'n',
            'ξ' => '3',
            'ο' => 'o',
            'π' => 'p',
            'ρ' => 'r',
            'σ' => 's',
            'τ' => 't',
            'υ' => 'y',
            'φ' => 'f',
            'χ' => 'x',
            'ψ' => 'ps',
            'ω' => 'w',
            'ά' => 'a',
            'έ' => 'e',
            'ί' => 'i',
            'ό' => 'o',
            'ύ' => 'y',
            'ή' => 'h',
            'ώ' => 'w',
            'ς' => 's',
            'ϊ' => 'i',
            'ΰ' => 'y',
            'ϋ' => 'y',
            'ΐ' => 'i',
            'Α' => 'A',
            'Β' => 'B',
            'Γ' => 'G',
            'Δ' => 'D',
            'Ε' => 'E',
            'Ζ' => 'Z',
            'Η' => 'H',
            'Θ' => '8',
            'Ι' => 'I',
            'Κ' => 'K',
            'Λ' => 'L',
            'Μ' => 'M',
            'Ν' => 'N',
            'Ξ' => '3',
            'Ο' => 'O',
            'Π' => 'P',
            'Ρ' => 'R',
            'Σ' => 'S',
            'Τ' => 'T',
            'Υ' => 'Y',
            'Φ' => 'F',
            'Χ' => 'X',
            'Ψ' => 'PS',
            'Ω' => 'W',
            'Ά' => 'A',
            'Έ' => 'E',
            'Ί' => 'I',
            'Ό' => 'O',
            'Ύ' => 'Y',
            'Ή' => 'H',
            'Ώ' => 'W',
            'Ϊ' => 'I',
            'Ϋ' => 'Y',
            
            // turkish
            'ş' => 's',
            'Ş' => 'S',
            'ı' => 'i',
            'İ' => 'I',
            // 'ç' => 'c', // duplicate
            // 'Ç' => 'C', // duplicate
            // 'ü' => 'ue', // duplicate
            // 'Ü' => 'Ue', // duplicate
            // 'ö' => 'oe', // duplicate
            // 'Ö' => 'Oe', // duplicate
            'ğ' => 'g',
            'Ğ' => 'G',
            
            // macedonian
            'Ќ' => 'Kj',
            'ќ' => 'kj',
            'Љ' => 'Lj',
            'љ' => 'lj',
            'Њ' => 'Nj',
            'њ' => 'nj',
            'Тс' => 'Ts',
            'тс' => 'ts',
            
            // russian
            'а' => 'a',
            'б' => 'b',
            'в' => 'v',
            'г' => 'g',
            'д' => 'd',
            'е' => 'e',
            'ё' => 'yo',
            'ж' => 'zh',
            'з' => 'z',
            'и' => 'i',
            'й' => 'j',
            'к' => 'k',
            'л' => 'l',
            'м' => 'm',
            'н' => 'n',
            'о' => 'o',
            'п' => 'p',
            'р' => 'r',
            'с' => 's',
            'т' => 't',
            'у' => 'u',
            'ф' => 'f',
            'х' => 'h',
            'ц' => 'c',
            'ч' => 'ch',
            'ш' => 'sh',
            'щ' => 'sh',
            'ъ' => '',
            'ы' => 'y',
            'ь' => '',
            'э' => 'e',
            'ю' => 'yu',
            'я' => 'ya',
            'А' => 'A',
            'Б' => 'B',
            'В' => 'V',
            'Г' => 'G',
            'Д' => 'D',
            'Е' => 'E',
            'Ё' => 'Yo',
            'Ж' => 'Zh',
            'З' => 'Z',
            'И' => 'I',
            'Й' => 'J',
            'К' => 'K',
            'Л' => 'L',
            'М' => 'M',
            'Н' => 'N',
            'О' => 'O',
            'П' => 'P',
            'Р' => 'R',
            'С' => 'S',
            'Т' => 'T',
            'У' => 'U',
            'Ф' => 'F',
            'Х' => 'H',
            'Ц' => 'C',
            'Ч' => 'Ch',
            'Ш' => 'Sh',
            'Щ' => 'Sh',
            'Ъ' => '',
            'Ы' => 'Y',
            'Ь' => '',
            'Э' => 'E',
            'Ю' => 'Yu',
            'Я' => 'Ya',
            
            // ukranian
            'Є' => 'Ye',
            'І' => 'I',
            'Ї' => 'Yi',
            'Ґ' => 'G',
            'є' => 'ye',
            'і' => 'i',
            'ї' => 'yi',
            'ґ' => 'g',
            
            // czech
            'č' => 'c',
            'ď' => 'd',
            'ě' => 'e',
            'ň' => 'n',
            'ř' => 'r',
            'š' => 's',
            'ť' => 't',
            'ů' => 'u',
            'ž' => 'z',
            'Č' => 'C',
            'Ď' => 'D',
            'Ě' => 'E',
            'Ň' => 'N',
            'Ř' => 'R',
            'Š' => 'S',
            'Ť' => 'T',
            'Ů' => 'U',
            'Ž' => 'Z',
            
            // polish
            'ą' => 'a',
            'ć' => 'c',
            'ę' => 'e',
            'ł' => 'l',
            'ń' => 'n',
            // 'ó' => 'o', // duplicate
            'ś' => 's',
            'ź' => 'z',
            'ż' => 'z',
            'Ą' => 'A',
            'Ć' => 'C',
            'Ę' => 'E',
            'Ł' => 'L',
            'Ń' => 'N',
            'Ś' => 'S',
            'Ź' => 'Z',
            'Ż' => 'Z',
            
            // latvian
            'ā' => 'a',
            // 'č' => 'c', // duplicate
            'ē' => 'e',
            'ģ' => 'g',
            'ī' => 'i',
            'ķ' => 'k',
            'ļ' => 'l',
            'ņ' => 'n',
            // 'š' => 's', // duplicate
            'ū' => 'u',
            // 'ž' => 'z', // duplicate
            'Ā' => 'A',
            // 'Č' => 'C', // duplicate
            'Ē' => 'E',
            'Ģ' => 'G',
            'Ī' => 'I',
            'Ķ' => 'k',
            'Ļ' => 'L',
            'Ņ' => 'N',
            // 'Š' => 'S', // duplicate
            'Ū' => 'U',
            // 'Ž' => 'Z', // duplicate
            
            // Arabic
            'ا' => 'a',
            'أ' => 'a',
            'إ' => 'i',
            'آ' => 'aa',
            'ؤ' => 'u',
            'ئ' => 'e',
            'ء' => 'a',
            'ب' => 'b',
            'ت' => 't',
            'ث' => 'th',
            'ج' => 'j',
            'ح' => 'h',
            'خ' => 'kh',
            'د' => 'd',
            'ذ' => 'th',
            'ر' => 'r',
            'ز' => 'z',
            'س' => 's',
            'ش' => 'sh',
            'ص' => 's',
            'ض' => 'dh',
            'ط' => 't',
            'ظ' => 'z',
            'ع' => 'a',
            'غ' => 'gh',
            'ف' => 'f',
            'ق' => 'q',
            'ك' => 'k',
            'ل' => 'l',
            'م' => 'm',
            'ن' => 'n',
            'ه' => 'h',
            'و' => 'w',
            'ي' => 'y',
            'ى' => 'a',
            'ة' => 'h',
            'ﻻ' => 'la',
            'ﻷ' => 'laa',
            'ﻹ' => 'lai',
            'ﻵ' => 'laa',
            
            // Arabic diactrics
            'َ' => 'a',
            'ً' => 'an',
            'ِ' => 'e',
            'ٍ' => 'en',
            'ُ' => 'u',
            'ٌ' => 'on',
            'ْ' => '',
            
            // Arabic numbers
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
            
            // symbols
            '“' => '"',
            '”' => '"',
            '‘' => '\'',
            '’' => '\'',
            '∂' => 'd',
            'ƒ' => 'f',
            '™' => '(TM)',
            '©' => '(C)',
            'œ' => 'oe',
            'Œ' => 'OE',
            '®' => '(R)',
            '†' => '+',
            '℠' => '(SM)',
            '…' => '...',
            '˚' => 'o',
            'º' => 'o',
            'ª' => 'a',
            '•' => '*',
            
            // currency
            '$' => 'USD',
            '€' => 'EUR',
            '₢' => 'BRN',
            '₣' => 'FRF',
            '£' => 'GBP',
            '₤' => 'ITL',
            '₦' => 'NGN',
            '₧' => 'ESP',
            '₩' => 'KRW',
            '₪' => 'ILS',
            '₫' => 'VND',
            '₭' => 'LAK',
            '₮' => 'MNT',
            '₯' => 'GRD',
            '₱' => 'ARS',
            '₲' => 'PYG',
            '₳' => 'ARA',
            '₴' => 'UAH',
            '₵' => 'GHS',
            '¢' => 'cent',
            '¥' => 'CNY',
            '元' => 'CNY',
            '円' => 'YEN',
            '﷼' => 'IRR',
            '₠' => 'EWE',
            '฿' => 'THB',
            '₨' => 'INR',
            '₹' => 'INR',
            '₰' => 'PF'    
        );
        
        // create defaults
        $defaults = array(
            'maintain_case' => false,
            'title_case' => false,
            'custom_replacements' => array(),
            'separator' => '-',
            'truncate' => false,
            'uric_flag' => false,
            'uric_no_slash_flag' => false,
            'mark_flag' => false,
            'uric_chars' => array(';', '?', ':', '@', '&', '=', '+', '$', ',', '/'),
            'uric_no_slash_chars' => array(';', '?', ':', '@', '&', '=', '+', '$', ','),
            'mark_chars' => array('.', '!', '~', '*', '\'', '(', ')')
        );
        
        // result output
        $result = '';
        
        // merge set options with defaults
        $settings = $options + $defaults;
        
        // set symbol map
        $language = array_get($options, 'language', Config::getCurrentLanguage());
        $settings['symbols'] = ($language && isset($symbol_map[$language])) ? $symbol_map[$language] : $symbol_map['en'];

        // list of allowed characters
        $allowedChars = $settings['separator'];
        
        // if title_case is an array, these are words to ignore
        if ($settings['title_case'] && is_array($settings['title_case'])) {
            foreach ($settings['title_case'] as $value) {
                $settings['custom_replacements'][$value] = $value;
            }
        }
        
        // make sure something was passed before we go do all that work
        if (!$string) {
            return "";
        }
        
        // grab configured allowed characters
        if ($settings['uric_flag']) {
            $allowedChars .= join('', $settings['uric_chars']);
        }
        
        if ($settings['uric_no_slash_flag']) {
            $allowedChars .= join('', $settings['uric_no_slash_chars']);
        }
        
        if ($settings['mark_flag']) {
            $allowedChars .= join('', $settings['mark_chars']);
        }
        
        // perform custom character replacements
        foreach ($settings['custom_replacements'] as $key => $value) {
            $key = preg_replace('/([-\\^$*+?.()|[\]{}\/])/', '\\\$1', $key);
            
//            if (strlen($value) > 1) {
//                $string = preg_replace('/\b' . $key . '\b/i', $value, ' ' . $string . ' ');
//            } else {
                $string = preg_replace('/' . $key . '/i', $value, $string);
//            }
        }
        
        // title case
        if ($settings['title_case']) {
            $string = preg_replace_callback('/(\w)(\S*)/', function($matches) use ($string, $settings) {
                $replacement = strtoupper($matches[1]);
                
                return (!isset($settings['custom_replacements'][$replacement])) ? $replacement : strtolower($replacement);
            }, $string);
        }
        
        // escape all necessary characters
        $allowedChars = preg_replace('/[-\\^$*+?.()|[\]{}\/]/', '\\$&', $allowedChars);
        
        // trim whitespace
        $string = trim($string);
        
        // slug-makin' time
        $last_character_was_symbol = false;
        for ($i = 0, $length = strlen($string); $i < $length; $i++) {
            $char = mb_substr($string, $i, 1, 'UTF-8');

            if (isset($char_map[$char])) {
                // process diactrics characters
                $char = ($last_character_was_symbol && preg_match('/[A-Za-z0-9]/', $char_map[$char])) ? ' ' . $char_map[$char] : $char_map[$char];
                $last_character_was_symbol = false;
            } elseif (
                // process symbol chars
                isset($settings['symbols'][$char]) && 
                !($settings['uric_flag'] && mb_strpos(join('', $settings['uric_chars']), $char, null, 'UTF-8') !== false) &&
                !($settings['uric_no_slash_flag'] && mb_strpos(join('', $settings['uric_no_slash_chars']), $char, null, 'UTF-8') !== false) &&
                !($settings['mark_flag'] && mb_strpos(join('', $settings['mark_chars']), $char, null, 'UTF-8') !== false)
            ) {
                $char = ($last_character_was_symbol || preg_match('/A-Za-z0-9]/', mb_substr($result, -1, null, 'UTF-8'))) ? $settings['separator'] . $settings['symbols'][$char] : $settings['symbols'][$char];
                
                $char .= (mb_substr($string, $i + 1, 1, 'UTF-8') !== false && preg_match('/A-Za-z0-9]/', mb_substr($string, $i + 1, 1, 'UTF-8'))) ? $settings['separator'] : '';
                
                $last_character_was_symbol = true;
            } else {
                // process latin character
                if ($last_character_was_symbol && (preg_match('/A-Za-z0-9]/', $char) || preg_match('/A-Za-z0-9]/',  mb_substr($result, -1, null, 'UTF-8')))) {
                    $char = ' ' . $char;
                }
                
                $last_character_was_symbol = false;
            }
            
            $result .= preg_replace('/^\w\s' . $allowedChars . '_-]/', $settings['separator'], $char);
        }
        
        // add separators
        $result = preg_replace('/\s/', $settings['separator'], $result);
        
        // remove duplicate separators
        $result = preg_replace('/\\' . $settings['separator'] . '+/', $settings['separator'], $result);
        
        // trim separators from ends
        $result = preg_replace('/(^\\' . $settings['separator'] . '+|\\' . $settings['separator'] . '+$)/', '', $result);
        
        // truncate if necessary
        if ($settings['truncate'] && strlen($result) > $settings['truncate']) {
            $lucky = (mb_substr($result, $settings['truncate'], 1, 'UTF-8') === $settings['separator']);
            $result = mb_substr($result, 0, $settings['truncate'], 'UTF-8');
            
            if (!$lucky) {
                $last_index = strrpos($result, $settings['separator']);
                
                if ($last_index !== false) {
                    $result = mb_substr($result, 0, $last_index, 'UTF-8');
                }
            }
        }
        
        // to lowercase?
        if (!$settings['maintain_case'] && !$settings['title_case']) {
            $result = strtolower($result);
        }
        
        return $result;
    }


    /**
     * Humanizes a slug, converting delimiters to spaces
     *
     * @param string  $value  Value to humanize from slug form
     * @return string
     */
    public static function humanize($value)
    {
        return trim(preg_replace('~[-_]~', ' ', $value), " ");
    }


    /**
     * Pretties up a slug, making it title case
     *
     * @param string  $value  Value to pretty
     * @return string
     */
    public static function prettify($value)
    {
        return ucwords(self::humanize($value));
    }


    /**
     * Checks to see whether a given $slug matches the DATE pattern
     *
     * @param string  $slug  Slug to check
     * @return bool
     */
    public static function isDate($slug)
    {
        if (!preg_match(Pattern::DATE, $slug, $matches)) {
            return FALSE;
        }

        return Pattern::isValidDate($matches[0]);
    }


    /**
     * Checks to see whether a given $slug matches the DATETIME pattern
     *
     * @param string  $slug  Slug to check
     * @return bool
     */
    public static function isDateTime($slug)
    {
        if (!preg_match(Pattern::DATETIME, $slug, $matches)) {
            return FALSE;
        }

        return Pattern::isValidDate($matches[0]);
    }


    /**
     * Checks to see whether a given $slug matches the NUMERIC pattern
     *
     * @param string  $slug  Slug to check
     * @return bool
     */
    public static function isNumeric($slug)
    {
        return (bool) preg_match(Pattern::NUMERIC, $slug);
    }


    /**
     * Checks the slug for status indicators
     *
     * @param string  $slug  Slug to check
     * @return string
     */
    public static function getStatus($slug)
    {
        $slugParts = explode('/', $slug);
        $slug = end($slugParts);

        if (substr($slug, 0, 2) === "__") {
            return 'draft';
        } elseif (substr($slug, 0, 1) === "_") {
            return 'hidden';
        }

        return 'live';
    }


    /**
     * Returns the proper status prefix
     *
     * @param string  $status  Status to check
     * @return string
     */
    public static function getStatusPrefix($status)
    {
        if ($status === 'draft') {
            return '__';
        } elseif ($status === 'hidden') {
            return '_';
        }

        return '';
    }

    /**
     * Checks if the slug has a draft indicator
     *
     * @param string  $slug  Slug to check
     * @return bool
     */
    public static function isDraft($slug)
    {
        return self::getStatus($slug) === 'draft';
    }


    /**
     * Checks if the slug has a hidden indicator
     *
     * @param string  $slug  Slug to check
     * @return bool
     */
    public static function isHidden($slug)
    {
        return self::getStatus($slug) === 'hidden';
    }


    /**
     * Checks if the slug has a no status indicators (thus, live)
     *
     * @param string  $slug  Slug to check
     * @return bool
     */
    public static function isLive($slug)
    {
        return self::getStatus($slug) === 'live';
    }


    /**
     * Gets the date and time from a given $slug
     *
     * @param string  $slug  Slug to parse
     * @return int
     */
    public static function getTimestamp($slug)
    {
        if (!preg_match(Pattern::DATE_OR_DATETIME, $slug, $matches) || !Pattern::isValidDate($matches[0])) {
            return FALSE;
        }

        $date_string = substr($matches[0], 0, 10);
        $delimiter   = substr($date_string, 4, 1);
        $date_array  = explode($delimiter, $date_string);

        // check to see if this is a full date and time
        $time_string = (strlen($matches[0]) > 11) ? substr($matches[0], 11, 4) : '0000';

        // construct the stringed time
        $date = $date_array[2] . '-' . $date_array[1] . '-' . $date_array[0];
        $time = substr($time_string, 0, 2) . ":" . substr($time_string, 2);

        return strtotime("{$date} {$time}");
    }


    /**
     * Gets the order number from a given $slug
     *
     * @param string  $slug  Slug to parse
     * @return int
     */
    public static function getOrderNumber($slug)
    {
        if (!preg_match(Pattern::NUMERIC, $slug, $matches)) {
            return FALSE;
        }

        return $matches[1];
    }
}
