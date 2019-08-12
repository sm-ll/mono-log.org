<?php
class Hooks_generator extends Hooks {

	public function generator__fire($key = null)
	{
		if (array_get($key, 0) === $this->fetchConfig('secret_key')) {

			$this->cache->purgeOlderThan($this->fetchConfig('throttle', 1800));

			if ( ! $this->cache->exists('last_generated')) {

				$this->core->generateAllTheThings();
				$this->cache->put('last_generated', time());

				echo 'ok';

			} else {
				// Shut it down
				\Slim\Slim::getInstance()->halt(404, ob_get_clean());
			}
		}
	}
}