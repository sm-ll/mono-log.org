<?php
class Avatar
{
    private static $gravatar;
    private static $is_gravatar_loaded;
    
    /**
     * Gets avatar from Gravatar
     * 
     * @param string  $email  Email address of user to look for
     * @param int  $size  Size of the avatar to retrieve
     * @return string
     */
    public static function getGravatar($email, $size)
    {
        if (!self::$is_gravatar_loaded) {
            self::$is_gravatar_loaded = true;
            self::$gravatar = new \emberlabs\GravatarLib\Gravatar();
            self::$gravatar->setDefaultImage('http://f.cl.ly/items/3M1e2a0x423F2w312J1M/buck.jpg');
        }
        
        self::$gravatar->setAvatarSize($size);
        self::$gravatar->enableSecureImages();
        return self::$gravatar->buildGravatarURL($email);
    }
}