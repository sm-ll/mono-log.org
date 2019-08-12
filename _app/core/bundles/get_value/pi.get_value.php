<?php
class Plugin_get_value extends Plugin
{

	public function __call($method, $arguments)
	{
		// Get all the parameter/filters
		$filters = $this->attributes;

		// Default content to context
		$content = $this->context;

		// Override content by a specific page if needed
		if ($from = $this->fetchParam('from')) {
			$content = current(ContentService::getContentByURL($from)->get());
			unset($filters['from']);
		}

		// Grab the field data
		$field_data = array_get($content, $method);
		
		// Filter down to what we're looking for
		$values = array_values(array_filter($field_data, function($i) use ($filters) {			
			foreach ($filters as $key => $val) {
				$match = array_get($i, $key) == $val;
				if (!$match) break;
			}

			return $match;
		}));

		// No results?
		if (empty($values)) return array('no_results' => true);

		// Got something. Yay. Return it.
		return Parse::tagLoop($this->content, $values, true);
	}

}