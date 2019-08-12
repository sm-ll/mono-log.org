<?php
/**
 * API_revisions
 * An API for interacting with the core revisions functionality
 *
 * @author  Statamic <gentlemen@statamic.com>
 */
class API_revisions extends API
{
	/**
	 * Returns the current revision
	 *
	 * @return string|null
	 */
	public function getCurrentRevision()
	{
		return $this->core->getCurrentRevision();
	}
	
	
	/**
	 * Are revisions enabled?
	 * 
	 * @return bool
	 */
	public function isEnabled()
	{
		return $this->core->isEnabled();
	}
	
	
	/**
	 * Does a given $file have revisions associated with it?
	 * 
	 * @return bool
	 */
	public function hasRevisions($file)
	{
		return $this->core->hasRevisions($file);
	}

}