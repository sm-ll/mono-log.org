<?php

use Symfony\Component\Finder\Finder as Finder;

class Tasks_file extends Tasks
{

	public function generateModal($files, $destination)
	{
		$vars = array(
			'server_files' => $files,
			'destination'  => $destination,
			'secure_destination' => urlencode(Helper::encrypt($destination)),
			'cp_url' => URL::assemble(Config::getSiteRoot(), Config::get('admin_path') . '.php'),
			'default_thumbnail' => $this->defaultFileThumbnail(),
			'allow_delete' => (bool) Config::get('allow_file_field_deletions')
		);
		$template = File::get($this->getAddonLocation() . 'views/modal.html');

		return Parse::contextualTemplate($template, $vars, Config::getAll());
	}


	public function getServerFiles($config, $destination)
	{
		$path = Path::assemble(BASE_PATH, $destination);

		$finder = new Finder();

		// Set folder location
		$finder->in($path);

		// Limit by depth
		$finder->depth('<' . array_get($config, 'depth', '1'));

		// Limit by file extension
		foreach (array_get($config, array('allowed', 'types'), array()) as $ext) {
			$finder->name("/\.{$ext}/i");
		}

		// Fetch matches
		$matches = $finder->files()->followLinks();

		// Build array
		$files = array();
		foreach ($matches as $file) {
			$filename = Path::trimSubdirectory(Path::toAsset($file->getPathname(), false));
			$display_name = ltrim(str_replace($path, '', $file->getPathname()), '/');

			$image = in_array(strtolower($file->getExtension()), array('jpg', 'png', 'gif'));

			$value = (Config::get('prepend_site_root_to_uploads', false)) 
			         ? '{{ _site_root }}' . ltrim($filename, '/')
			         : $filename;

			$files[] = compact('value', 'display_name', 'image', 'default_thumbnail');
		}

		return $files;
	}


	public function defaultFileThumbnail()
	{
		return URL::assemble(Config::getSiteRoot(), Config::get('admin_path'), 'themes', Config::get('admin_theme'), '/img/file.png');
	}


	public function deleteFile()
	{
		if ( ! Config::get('allow_file_field_deletions')) {
			return $this->abortDeletion('file_deleting_not_permitted');
		}

		if ( ! $path = Request::get('path')) {
			return $this->abortDeletion('file_no_path');
		}

		if ( ! $destination = Request::get('config')) {
			return $this->abortDeletion('file_no_config');
		}

		$destination = Path::addStartingSlash(Helper::decrypt(urldecode($destination)));

		if (
			// File path not in destination
			! Pattern::startsWith($path, $destination)
			// or path is trying to get out of the destination. sneaky.
			|| strpos($path, '../'))
		{
			return $this->abortDeletion('error');
		}

		$full_path = Path::assemble(BASE_PATH, $path);

		if ( ! File::exists($full_path)) {
			return $this->abortDeletion('file_doesnt_exist');
		}

		File::delete($full_path);

		return array(
			'success' => true,
			'message' => Localization::fetch('file_deleted')
		);
	}


	private function abortDeletion($error)
	{
		return array(
			'success' => false,
			'message'   => Localization::fetch($error)
		);
	}

}