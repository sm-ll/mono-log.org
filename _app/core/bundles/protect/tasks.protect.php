<?php

class Tasks_protect extends Tasks
{
    /**
     * Checks that a given $password is valid for a given $url
     * 
     * @param string  $url  URL to check
     * @param string  $password  Password to check
     * @return bool
     */
    public function isValidPassword($url, $password)
    {
        // get allowed passwords
        $allowed = $this->getAllowedPasswords($url);
        
        // check for matches
        return in_array($password, $allowed);
    }
    
    
    /**
     * Does this user have a correct password for a given $url?
     * 
     * @param string  $url  URL to check password for
     * @param array  $allowed  Override URL list with given list of allowed passwords
     * @return bool
     */
    public function hasPassword($url, $allowed=array())
    {
        $passwords  = $this->getUserPasswords($url);
        $allowed    = (is_array($allowed) && count($allowed)) ? $allowed : $this->getAllowedPasswords($url);
        
        // no allowed passwords? get out
        if (empty($allowed)) {
            return false;
        }
        
        // check for matches
        $matches = array_intersect($allowed, $passwords);
        
        return (bool) count($matches);
    }


    /**
     * Does this user have a correct IP for a given $url
     * 
     * @param string  $url  URL to check IP address for
     * @return bool
     */
    public function hasIP($url)
    {
        $ip_address = Request::getIP();
        $allowed    = $this->getAllowedIPs($url);        

        // no allowed passwords? get out
        if (empty($allowed)) {
            return false;
        }

        // check for matches
        return Helper::isIPInRange($ip_address, $allowed);
    }


    /**
     * Adds a password for a given URL
     * 
     * @param string  $url  URL to add password for
     * @param string  $password  Password to add
     */
    public function addPassword($url, $password)
    {
        $passwords = $this->session->get('passwords', array());
        $url_hash  = $this->hashURL($url);
        
        if (!isset($passwords[$url_hash])) {
            $passwords[$url_hash] = array();
        }
        
        if (!in_array($password, $passwords[$url_hash])) {
            $passwords[$url_hash][] = $password;
        }
        
        $this->session->set('passwords', $passwords);
    }
    
    
    /**
     * Gets passwords allowed for a given $url
     * 
     * @param string  $url  URL to retrieve passwords for
     * @return array
     */
    public function getAllowedPasswords($url)
    {
        // get data
        $data = Content::get($url);

        // is there data?
        if (empty($data['_protect']['password']['allowed'])) {
            return array();
        }

        // grab allowed passwords
        return Helper::ensureArray($data['_protect']['password']['allowed']);
    }
    
    
    /**
     * Gets IPs allowed for a given $url
     * 
     * @param string  $url  URL to retrieve IPs for
     * @return array
     */
    public function getAllowedIPs($url)
    {
        // get data
        $data = Content::get($url);

        // is there data?
        if (empty($data['_protect']['ip_address']['allowed'])) {
            return array();
        }

        // grab allowed passwords
        return Helper::ensureArray($data['_protect']['ip_address']['allowed']);
    }
    
    
    public function getUserPasswords($url)
    {
        $passwords = $this->session->get('passwords', array());
        $url_hash  = $this->hashURL($url);
        
        if (isset($passwords[$url_hash]) && is_array($passwords[$url_hash])) {
            return $passwords[$url_hash];
        }
        
        return array();
    }


    /**
     * Consistently create hashes for a URL
     * 
     * @param string  $url  URL to hash
     * @return string
     */
    public function hashURL($url)
    {
        return md5($url);
    }

    
    /**
     * Evaluates a rule
     * 
     * @param string  $rule  Type of rule
     * @param mixed  $value  Value to evaluate for the rule
     * @return bool
     */
    public function evaluateRule($rule, $value)
    {
        $member = (Auth::isLoggedIn()) ? Auth::getCurrentMember() : new Member(array());
        
        if ($rule === '_any') {
            // this is an "any" grouping
            foreach ($value as $sub_rule) {
                reset($sub_rule);
                $key = key($sub_rule);
                if ($this->evaluateRule(key($sub_rule), $sub_rule[$key])) {
                    return true;
                }
            }

            return false;
        } elseif ($rule === '_none') {
            // this is a "none" grouping
            foreach ($value as $sub_rule) {
                reset($sub_rule);
                $key = key($sub_rule);
                if ($this->evaluateRule(key($sub_rule), $sub_rule[$key])) {
                    return false;
                }
            }

            return true;
        } elseif ($rule === '_all') {
            // this is an "all" grouping
            foreach ($value as $sub_rule) {
                reset($sub_rule);
                $key = key($sub_rule);
                if (!$this->evaluateRule(key($sub_rule), $sub_rule[$key])) {
                    return false;
                }
            }

            return true;
        } elseif ($rule === '_addon') {
            // this is an add-on API call
            // grab add-on definition
            $method      = array_get($value, 'method', null);
            $comparison  = array_get($value, 'comparison', '==');
            $parameters  = array_get($value, 'parameters', array());
            $error       = array_get($value, 'error', null);
            $value       = array_get($value, 'value', null);

            // split method
            $method_parts = explode(':', $method, 2);

            // were definitions valid?
            if (!$method || count($method_parts) !== 2 || !is_array($parameters)) {
                return false;
            }

            // load API
            try {
                $api = Resource::loadAPI($method_parts[0]);

                // can this method be called?
                if (!is_callable(array($api, $method_parts[1]), false)) {
                    return false;
                }

                // get the result of calling the method
                $result_value = call_user_func_array(array($api, $method_parts[1]), $parameters);

                // now compare the expected value with the actual value
                $result = $this->compareValues($value, $result_value, $comparison);

                // set optional user error
                if (!$result && $error) {
                    $this->flash->set('error', $error);
                }

                return $result;
            } catch (Exception $e) {
                // something went wrong, this fails
                rd($e->getMessage());
                return false;
            }
        } elseif ($rule === '_field') {
            // this is a complex field match
            // grab field definitions
            $field       = array_get($value, 'field', null);
            $comparison  = array_get($value, 'comparison', '==');
            $value       = array_get($value, 'value', null);

            // were definitions valid?
            if (!$field) {
                return false;
            }

            return $this->compareValues($value, $member->get($field, null), $comparison);
        } elseif ($rule === '_logged_in') {
            // this is checking if member is logged in
            return (Auth::isLoggedIn() === $value);
        } elseif ($rule === '_ip_address') {
            // this is one or more IP address            
            return $this->compareValues(Helper::ensureArray($value), Request::getIP(), '==');
        } else {
            // this is a simple field match
            return $this->compareValues($value, $member->get($rule, null), '==');
        }
    }


    /**
     * Compares two values based on the given $comparison
     *
     * @param mixed  $allowed  Allowed value(s)
     * @param mixed  $test_value  Value(s) to check
     * @param string  $comparison  Type of comparison to make
     * @return bool
     */
    public function compareValues($allowed, $test_value, $comparison="==")
    {
        $allowed     = Helper::ensureArray($allowed);
        $test_value  = Helper::ensureArray($test_value);
        $comparison  = strtolower($comparison);

        // loop through each allowed value
        foreach ($allowed as $allowed_value) {
            // loop through each test value
            foreach ($test_value as $sub_test_value) {
                switch ($comparison) {
                    case "=":
                    case "==":
                        if (strtolower($sub_test_value) == strtolower($allowed_value)) {
                            return true;
                        }
                        break;

                    case "!=":
                    case "<>":
                    case "not":
                        // backwards-from-standard check, returning false if found
                        if (strtolower($sub_test_value) == strtolower($allowed_value)) {
                            return false;
                        }
                        break;

                    case "<":
                        if ($sub_test_value < $allowed_value) {
                            return true;
                        }
                        break;

                    case "<=":
                        if ($sub_test_value <= $allowed_value) {
                            return true;
                        }
                        break;

                    case ">":
                        if ($sub_test_value > $allowed_value) {
                            return true;
                        }
                        break;

                    case ">=":
                        if ($sub_test_value >= $allowed_value) {
                            return true;
                        }
                        break;

                    case "has":
                    case "exists":
                        if (!empty($sub_test_value)) {
                            return true;
                        }
                        break;

                    case "lacks":
                    case "missing":
                        // backwards-from-standard check, returning false if found
                        if (!empty($sub_test_value)) {
                            return false;
                        }
                        break;

                    default:
                        return false;
                }
            }
        }
        
        // if we're looking for a negative match (`not` or `lacks`) and we got here, return true
        if (in_array($comparison, array('!=', '<>', 'not', 'lacks', 'missing'))) {
            return true;
        }

        // in all other cases, return false
        return false;
    }
}
