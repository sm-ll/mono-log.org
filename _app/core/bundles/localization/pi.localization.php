<?php
class Plugin_localization extends Plugin
{

	public function index()
	{
		$str = $this->fetchParam('fetch', null, null, false, false);
		return Localization::fetch($str);
	}

}