<?php
/**
 * Plugin_cache
 * The main front-end interface to caching arbitrary HTML
 * 
 * @author  Statamic <gentlemen@statamic.com>
 */
class Plugin_cache extends Plugin
{
	public function index()
	{
		// if disabled, do nothing
		if (!$this->core->isEnabled()) {
			return Parse::contextualTemplate($this->content, array(), $this->context);
		}

		// grab and prepare parameters
		$key  = $this->fetchParam('key', null, null, false, false);
		$age  = time() - Date::resolve('-' . $this->fetch(array('for', 'default_cache_length'), '12 hours', null, false, false));
		$hash = ($this->fetch(array('scope', 'default_scope'), 'site') === 'page') ? Helper::makeHash(URL::getCurrent(), $this->content) : Helper::makeHash($this->content);
		$path = 'troves/' . $hash;

		// deal with keys
		if ($key) {
			// split on pipes
			$keys = explode('|', $key);

			// loop through keys, storing this hash to this key
			foreach ($keys as $key) {
				$key_path = 'keys/' . trim($key);

				// get existing keys
				$hashes     = $this->cache->getYAML($key_path, array());
				$new_hashes = array();

				// check that the hashes are all still valid
				foreach ($hashes as $new_hash) {
					if ($this->cache->exists('troves/' . $new_hash)) {
						$new_hashes[] = $new_hash;
					}
				}

				// add this one
				$new_hashes[] = $hash;

				// append new ones
				$this->cache->putYAML($key_path, array_unique($new_hashes));
			}
		}

		// check for pre-existing cache
		if (!$this->cache->exists($path) || $this->cache->getAge($path) > $age) {
			// cache doesn't exist or has expired, so parse this contextually...
			$html = Parse::contextualTemplate($this->content, array(), $this->context);

			// ...and store the HTML
			$this->cache->put($path, $html);
		}

		// garbage collection
		$this->core->collectGarbage();

		// return what we know
		return $this->cache->get($path);
	}
}