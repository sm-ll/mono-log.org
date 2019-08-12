<?php


class Hooks_cache extends Hooks
{
	public function control_panel__post_publish($publish_data)
	{
		// check that caching is turned on
		if (!$this->core->isEnabled()) {
			return;
		}

		// we only need one key from the hook's value
		$file = $publish_data['file'];
		
		// update the cache
		_Cache::update();
		ContentService::loadCache(true);

		// grab data
		$triggers = $this->fetchConfig('publish_invalidation', array(), 'is_array', false, false);
		$content  = Content::find(Path::tidy(str_replace(Config::getContentRoot(), '/', $file)));

		if ($triggers && $content) {
			foreach ($triggers as $trigger) {
				$folders = Parse::pipeList(array_get($trigger, 'folder', null));
				$key     = array_get($trigger, 'key', null);

				if (!$folders || !$key) {
					// not checking this
					continue;
				}

				// check
				$invalidate = false;
				foreach ($folders as $folder) {
					if ($folder === "*" || $folder === "/*") {
						// include all
						$invalidate = true;
						break;
					} elseif (substr($folder, -1) === "*") {
						// wildcard check
						if (strpos($content['_folder'], substr($folder, 0, -1)) === 0) {
							$invalidate = true;
							break;
						}
					} else {
						// plain check
						if ($folder == $content['_folder']) {
							$invalidate = true;
							break;
						}
					}
				}

				// invalidate if needed
				if ($invalidate) {
					$this->core->deleteByKey(Parse::pipeList($key));
				}
			}
		}
	}
	
	
	public function _routes__before()
	{
		// check that caching is turned on
		if (!$this->core->isEnabled()) {
			return;
		}

		$today  = Date::format('F j, Y');
		$keys   = $this->fetchConfig('new_day_invalidation', null, null, false, false);
		
		if (empty($keys) || $this->cache->get('last_new_day', null) == $today) {
			// don't need to do this
			return;
		}
		
		// set that we did this today
		$this->cache->put('last_new_day', $today);
		
		// invalidate if needed
		$this->core->deleteByKey(Parse::pipeList($keys));
	}
}