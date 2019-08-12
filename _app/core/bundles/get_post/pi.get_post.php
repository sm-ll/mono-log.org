<?php
class Plugin_get_post extends Plugin
{
  public static function __callStatic($method, $args)
  {
    $val = false;
    if (isset($_POST[$method])) {
      $val = $_POST[$method];
    } elseif (isset($_GET[$method])) {
      $val = $_GET[$method];
    }

    return $val;
  }

}
