<?php
class UserAuth
{
    /**
     * Returns a given user's profile
     * 
     * @param string  $username  Username's profile to return
     * @return array
     */
    public static function getUserProfile($username)
    {
        if (!UserAuth::isUser($username)) {
            return null;
        }
        
        $content       = substr(File::get(Config::getConfigPath() . "/users/" . $username . ".yaml"), 3);
        $divide        = strpos($content, "\n---");
        $front_matter  = trim(substr($content, 0, $divide));
        $content_raw   = trim(substr($content, $divide + 4));
        
        $profile                  = YAML::parse($front_matter);
        $profile['biography_raw'] = $content_raw;
        $profile['biography']     = Content::transform($content_raw);
        $profile['username']      = $username;
        
        return $profile;
    }
}