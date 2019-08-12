<?php
class Plugin_get extends Plugin
{
  public static function __callStatic($method, $args)
  {
    return isset($_GET[$method]) ? $_GET[$method] : false;
  }

}
