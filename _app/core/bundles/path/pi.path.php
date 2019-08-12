<?php
class Plugin_path extends Plugin
{
	public function index()
	{
		$src = $this->fetchParam('src', '', NULL, FALSE, FALSE);

		return Path::toAsset($src);
	}

}
