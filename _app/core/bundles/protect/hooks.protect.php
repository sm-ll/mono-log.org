<?php

class Hooks_protect extends Hooks
{
    public function protect__password()
    {
        // grab values
        $token     = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_STRING);
        $return    = filter_input(INPUT_POST, 'return', FILTER_SANITIZE_STRING);
        $password  = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
        $referrer  = $_SERVER['HTTP_REFERER'];
        
        // validate token
        if (!$this->tokens->validate($token)) {
            $this->flash->set('error', 'Invalid token passed, please try again.');
            URL::redirect($referrer);
        }
        
        // check password matches a password from return text
        if (!$this->tasks->isValidPassword($return, $password)) {
            $this->flash->set('error', 'Incorrect password.');
            URL::redirect($referrer);
        }
        
        // store this password in the session
        $this->tasks->addPassword($return, $password);
        
        // redirect to the URL
        URL::redirect($return);
    }
}