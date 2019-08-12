<?php
class Core_generator extends Core
{

	/**
	 * Site URL
	 * @var string
	 */
	private $site_url;

	/**
	 * Header context used in file_get_contents
	 * @var array
	 */
	private $request_context;


	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		$this->site_url = substr(URL::getSiteURL(), 0, -1);
		$this->request_context = stream_context_create(array('http' => array('header' => 'Connection: close\r\n')));
	}


	/**
	 * Copies folders
	 * 
	 * @return void
	 */
	public function copyAssets()
	{
		foreach (Helper::ensureArray($this->config['copy']) as $folder) {
			$folder = str_replace('{theme}', Config::getTheme(), $folder);

			$full_from_path = Path::assemble(BASE_PATH, $folder);
			$full_to_path = Path::assemble(BASE_PATH, $this->config['destination'], $folder);

			Folder::copy($full_from_path, $full_to_path);
		}
	}


	/**
	 * Generates a list of files/URLs that will need to be accessed
	 *
	 * @return array
	 */
	public function generateFileList()
	{
 		$files = $this->generateFilesFromCache() + 
 		         $this->generateManualFiles();

		return compact('files');
	}


	/**
	 * Generates a list of files from the content cache
	 * 
	 * @return array
	 */
	private function generateFilesFromCache()
	{
		$files = array();

		foreach ($this->getCache() as $url => $detail) {
			// Ignore drafts
			if (strpos($detail['path'], '/__')) {
				continue;
			}

			$files[] = $this->getFileData($url);
		}

		return $files;
	}


	/**
	 * Generates a list of files manually specified in the config.
	 * 
	 * @return array
	 */
	private function generateManualFiles()
	{
		$files = array();

		foreach (array_get($this->config, 'urls', array()) as $url) {

			// Taxonomy URL?
			if (strpos($url, '{taxonomy:')) {
				preg_match('/\{taxonomy:(.*)\}/', $url, $matches);
				$files += $this->generateFilesFromTaxonomy($matches[1], $url);
			}

			// Regular ol' URL
			else {
				$files[] = $this->getFileData($url);
			}

		}

		return $files;
	}


	/**
	 * Generate files from a specified taxonomy type
	 * 
	 * @param  string $type The taxonomy type/name, ie. tags or categories
	 * @param  string $url  The URL containing that needs modifying
	 * @return array
	 */
	private function generateFilesFromTaxonomy($type, $url)
	{
		$taxonomies = array_keys(ContentService::getTaxonomiesByType($type)->get());

		foreach ($taxonomies as $taxonomy) {
			$url = str_replace("{taxonomy:$type}", $taxonomy, $url);
			$files[] = $this->getFileData($url);
		}

		return $files;
	}


	/**
	 * Generates a static version of a page
	 * 
	 * @param  string $url The URL to be generated
	 * @return array       A response containing the success
	 */
	public function generatePage($url)
	{
		$filename = $this->filename($url);

		try {
			$this->write($filename, $this->getHtml($url));
		} catch (Exception $e) {
			return array('success' => false);
		}

		return array('success' => true);
	}

	/**
	 * Generates all the static things all at once
	 */
	public function generateAllTheThings() {
		$files = $this->generateFileList();

		foreach ($files['files'] as $file) {
		    $this->generatePage(array_get($file, 'url'));
		}

		$this->copyAssets();
	}


	/**
	 * Gets data from the Cache
	 *
	 * @return array
	 */
	private function getCache()
	{
		$cache = unserialize(File::get(BASE_PATH . '/_cache/_app/content/content.php'));

		unset($cache['urls']['/404']);

		return $cache['urls'];
	}


	/**
	 * Gets the HTML of a URL
	 *
	 * @param  string $uri  URI of the page to retrieve
	 * @return string
	 */
	private function getHtml($uri)
	{
		$url = $this->site_url . $uri;

		return file_get_contents($url, false, $this->request_context);
	}


	/**
	 * Generates the path the generated HTML file will be and 
	 * outputs an array used by the file listing
	 * 
	 * @param  string $url
	 * @return array
	 */
	private function getFileData($url)
	{
		$filename = $this->filename($url);
		$path = Path::assemble($this->config['destination'], $filename);

		return compact('path', 'url');
	}


	/**
	 * Takes a URL and responds the corresponding static html filename
	 * 
	 * @param  string $url    URL
	 * @param  array $detail  Optional details from cache. Saves another lookup if we already have it.
	 * @return string
	 */
	private function filename($url, $detail = null)
	{
		if ( ! $detail) {
			$detail = array_get($this->getCache(), $url);
		}

		$filename  = $url;
		$filename .= (Pattern::endsWith($detail['file'], '/page.md')) ? '/index' : '';
		$filename .= '.html';

		return $filename;
	}


	/**
	 * Write to a file
	 *
	 * @param  string $filename  File to be saved
	 * @param  string $content   The contents of the file
	 * @return void
	 */
	private function write($filename, $content)
	{
		$path = Path::assemble(BASE_PATH, $this->config['destination'], $filename);

		File::put($path, $content);
	}


	/**
	 * Creates and downloads a zip file of the static pages
	 * 
	 * @return void
	 */
	public function download()
	{
		$path = Path::assemble(BASE_PATH, $this->config['destination']);

		$zip_name = 'site-' . time() . '.zip';
		$zip_filename = Path::assemble(BASE_PATH, '_cache/_add-ons/', $this->addon_name, $zip_name);

		$zip = new ZipArchive();
		$zip->open($zip_filename, ZipArchive::CREATE);

		$ignore = array('.', '..', '.DS_Store');

		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file)
		{
			// Ignore ignored files
			if (in_array(substr($file, strrpos($file, '/')+1), $ignore)) {
                continue;
			}

			$filename = trim(Path::trimFilesystem($file), '_');
			$zip->addFile($file->getPathname(), $filename);
		}

		$zip->close();

		header('Content-Type: application/zip');
		header('Content-disposition: attachment; filename=' . $zip_name);
		header('Content-Length: ' . filesize($zip_filename));
		readfile($zip_filename);
	}

}