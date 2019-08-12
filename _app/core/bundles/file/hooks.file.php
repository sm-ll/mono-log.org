<?php
class Hooks_file extends Hooks
{

	public function file__thumbnail()
	{
		if ( ! $path = Request::get('path')) {
			exit('No path specified');
		}

		exit($this->getTransformedImage($path));
	}


	public function file__render_thumbnail()
	{
		if ( ! $path = Request::get('path')) {
			exit('No path specified');
		}

		$url = Path::toAsset($this->getTransformedImage($path));
		$url = (Config::getSiteRoot() !== '/') ? str_replace(Config::getSiteRoot(), '', $url) : $url;
		$file = Path::assemble(BASE_PATH, $url);

		header('Content-type: image/jpeg');
		header('Content-length: '.filesize($file));
		if ($file = fopen($file, 'rb')) {
			fpassthru($file);
		}
		exit;
	}


	private function getTransformedImage($path, $width = 125, $height = 125)
	{
		$path = URL::prependSiteRoot($path);

		$template = "{{ transform src='$path' width='$width' height='$height' action='smart' }}";

		return Parse::template($template, array());
	}

}