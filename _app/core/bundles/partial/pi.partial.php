<?php

/**
 * Parse and return partials via shorthand syntax
 *
 * @param string  $method  partial name
 * @return string
 */

class Plugin_partial extends Plugin
{
	public function __call($method, $arguments)
	{
		$extensions = array(".html", ".md", ".markdown", ".textile");

		$html = null;

		foreach ($extensions as $extension) {
			$full_src = Path::assemble(BASE_PATH, Config::getCurrentThemePath(), 'partials', ltrim($method . $extension, '/'));

			if (File::exists($full_src)) {
				
				// Merge additional variables passed as parameters
				Statamic_View::$_dataStore = $arguments + Statamic_View::$_dataStore;

				if ($this->fetchParam('use_context', false, false, true, false)) {
					$html = Parse::contextualTemplate(File::get($full_src), Statamic_View::$_dataStore, $this->context, 'Statamic_View::callback');
				} else {
					$html = Parse::template(File::get($full_src), Statamic_View::$_dataStore, 'Statamic_View::callback');
				}

				// parse contents if needed
				if ($extension == ".md" || $extension == ".markdown") {
					$html = Parse::markdown($html);
				} elseif ($extension == ".textile") {
					$html = Parse::textile($html);
				}
			}
		}

		if (Config::get('enable_smartypants', TRUE)) {
			$html = Parse::smartypants($html);
		}

		return $html;
	}

}
