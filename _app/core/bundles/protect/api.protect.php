<?php

class API_protect extends API
{
    /**
     * Check to see if the current member has the appropriate passwords needed 
     * to view this given $url
     * 
     * @param string  $url  URL to check passwords for
     * @return bool
     */
    public function hasPassword($url)
    {
        return $this->tasks->hasPassword($url);
    }

    
    /**
     * Check to see if the current member has the appropriate IP addresses 
     * needed to view this given $url
     *
     * @param string  $url  URL to check IP addresses for
     * @return bool
     */
    public function hasIP($url)
    {
        return $this->tasks->hasIP($url);
    }
    
    
    /**
     * Does the current member have access to a given $url?
     * 
     * @param string  $url  URL to check
     * @return boolean
     * @throws Exception
     */
    public function hasAccess($url=null)
    {
        // load data for the given $url
        $data = Content::get($url);

        if (!isset($data['_protect']) || !$data['_protect']) {
            return true;
        }

        // grab the protection scheme
        $scheme = $data['_protect'];

        // determine URLs
        $login_url       = URL::prependSiteRoot(array_get($scheme, 'login_url', $this->fetchConfig('login_url', '/', null, false, false)));
        $no_access_url   = URL::prependSiteRoot(array_get($scheme, 'no_access_url', $this->fetchConfig('no_access_url', '/', null, false, false)));
        $password_url    = URL::prependSiteRoot(array_get($scheme, 'password_form_url', $this->fetchConfig('password_url', '/', null, false, false)));
        
        // support external log-in systems
        $require_member  = array_get($scheme, 'require_member', $this->fetchConfig('require_member', true, null, true, false));
        $return_variable = array_get($scheme, 'return_variable', $this->fetchConfig('return_variable', 'return', null, false, false));
        $use_full_url    = array_get($scheme, 'use_full_url', $this->fetchConfig('use_full_url', false, null, true, false));
        
        // get the current URL
        $current_url     = ($use_full_url) ? URL::tidy(Config::getSiteURL() . '/' . URL::getCurrent()) : URL::getCurrent();
        
        // append query string
        if (!empty($_GET)) {
            $current_url .= '?' . http_build_query($_GET, '', '&');
        }

        // store if we've matched
        $match = false;

        if (isset($scheme['password'])) {
            // this is a password-check
            
            // get the form URL
            $form_url = array_get(  // check the password settings
                $scheme['password'],
                'form_url',
                Helper::pick(
                    $password_url,  // nothing? check the password_form_url setting in _protect
                    $no_access_url  // *still* nothing? no-access time
                )
            );

            // check for passwords
            if (!$this->evaluatePassword($url)) {
                URL::redirect(URL::appendGetVariable($form_url, $return_variable, $current_url), 302);
                exit();
            }

            // we're good
            return true;
        } elseif (isset($scheme['ip_address'])) {
            // this is an IP-address-check
            if (!$this->evaluateIP($url)) {
                URL::redirect($no_access_url, 302);
                exit();
            }
        } else {
            try {
                // are we going to allow or deny people?
                if (isset($scheme['allow']) && is_array($scheme['allow'])) {
                    $type = 'allow';
                    $rules = $scheme['allow'];
                } elseif (isset($scheme['deny']) && is_array($scheme['deny'])) {
                    $type = 'deny';
                    $rules = $scheme['deny'];
                } else {
                    throw new Exception('The `_protect` field is set for [' . $data['url'] . '](' . $data['url'] . '), but the configuration given could not be parsed. For caution’s sake, *everyone* is being blocked from this content.');
                }

                // if $require_member is true, do a check up-front to see if
                // this user is currently logged in
                if ($require_member && !Auth::isLoggedIn()) {
                    URL::redirect(URL::appendGetVariable($login_url, $return_variable, $current_url), 302);
                    exit();
                }

                // parse the rules
                foreach ($rules as $key => $value) {
                    if ($this->tasks->evaluateRule($key, $value)) {
                        $match = true;
                        break;
                    }
                }

                // send to no access page if user didn't match and needed to, or did and shouldn't have
                if ((!$match && $type === 'allow') || ($match && $type === 'deny')) {
                    URL::redirect($no_access_url, 302);
                    exit();
                }
            } catch (\Slim\Exception\Stop $e) {
                throw $e;
            } catch (Exception $e) {
                // something has gone wrong, log the message
                Log::error($e->getMessage(), "api", "security");

                // always return false
                URL::redirect($no_access_url, 302);;
            }
        }
    }


    /**
     * Evaluates a password for a given $url
     * 
     * @param string  $url  URL to evaluate passwords for
     * @return bool
     */
    public function evaluatePassword($url)
    {
        return $this->hasPassword($url);
    }
    

    /**
     * Evaluates an IP address for a given $url
     * 
     * @param string  $url  URL to evaluate IP addresses for
     * @return bool
     */
    public function evaluateIP($url)
    {
        return $this->hasIP($url);
    }
}