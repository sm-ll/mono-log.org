<?php
class Plugin_post extends Plugin
{
  public static function __callStatic($method, $args)
  {
    return isset($_POST[$method]) ? $_POST[$method] : false;

  }

}
