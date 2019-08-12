<?php
class Plugin_redirect extends Plugin
{
  public function index()
  {
    $app = \Slim\Slim::getInstance();

    $url = $this->fetchParam('to', false);
    $url = $url ? $url : $this->fetchParam('url', false);

    $response = $this->fetchParam('response', 302);

    if ($url) {
      $app->redirect($url, $response);
    }

  }

}
