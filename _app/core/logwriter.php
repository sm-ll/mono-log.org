<?php
/**
 * Statamic LogWriter
 * API for logging messages to the site's error log
 *
 * @author  Fred LeBlanc
 */
class Statamic_Logwriter {

	protected $resource;
	protected $settings = array();

	/**
	 * __construct
	 * Constructor
	 *
	 * @
	 */
	public function __construct($settings) {
		$this->settings = array_merge(array(
			'extension' => 'log',
			'path' => './_logs',
			'file_prefix' => 'site'
		), $settings);

		$this->settings['path'] = rtrim($this->settings['path'], DIRECTORY_SEPARATOR);

		if (!in_array(substr($this->settings['path'], 0, 1), array("/", "."))) {
			$this->settings['path'] = Path::tidy(BASE_PATH . DIRECTORY_SEPARATOR . $this->settings['path']);
		}
	}

	/**
	 * write
	 *
	 * @param object  $message  Message object to write
	 * @param int  $level  Level to log
	 * @return void
	 */
	public function write($message, $level=NULL) {
		if (!$this->resource) {
			$filename = $this->settings['file_prefix'] . "_" . Date::format("Y-m-d");

			if ($this->settings['extension']) {
				$filename .= "." . $this->settings['extension'];
			}

			try {
				$this->resource = fopen($this->settings['path'] . DIRECTORY_SEPARATOR . $filename, 'a');
			} catch (Exception $e) {
                // do nothing
			}
		}

		if (is_resource($this->resource)) {
            fwrite($this->resource, $message . PHP_EOL);
        }
	}
}
?>