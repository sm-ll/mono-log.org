<?php
/**
 * Core_revisions
 * Creates tunnels for core functionality for revisions
 *
 * @author  Statamic <gentlemen@statamic.com>
 */
class Core_revisions extends Core
{
	/**
	 * Checks to see if revisions is enabled and extension used is valid
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		$extension = $this->getExtension();

		if (!$extension) {
			// _revisions is turned off
			return false;
		}

		// extension is a thing, check that it exists and implements interface
		if ($this->addon->implementsInterface($extension, 'Interface_revisions') && $this->addon->api($extension)->isProperlyConfigured()) {
			return true;
		}

		return false;
	}


	/**
	 * Get the extension selected to manage revisions
	 *
	 * @return string
	 */
	public function getExtension()
	{
		return Config::get('revisions');
	}


	/**
	 * Checks to see if a revision has been selected
	 *
	 * @return bool
	 */
	public function isRevisionSelected()
	{
		return (bool)Request::get('revision');
	}


	/**
	 * Gets the currently selected revision if one exists
	 *
	 * @return bool
	 */
	public function getCurrentRevision()
	{
		return Request::get('revision', null);
	}


	/**
	 * Normalize a path to what we need to work with.
	 * Essentially, remove the starting slash and ensure there is an extension
	 *
	 * @param  string $path
	 * @return string
	 */
	public function normalizePath(&$path)
	{
		$path = Path::removeStartingSlash($path);

		$ext = '.' . Config::getContentType();
		if (!Pattern::endsWith($path, $ext)) {
			$path .= $ext;
		}

		return $path;
	}


	/**
	 * Get the path of the current file
	 *
	 * @return string Full path
	 */
	public function getPath()
	{
		// Get the query string and remove the ordering
		$url = Path::pretty(Request::get('path'));

		// Remove the 'page' if it's a page.md
		$url = (Pattern::endsWith($url, 'page'))
			? URL::popLastSegment($url)
			: $url;

		// Get the content
		$content = Content::get($url);

		// Path is inside the content
		return ltrim($content['_local_path'], '/');
	}


	/**
	 * Get revisions for a file
	 *
	 * @param string $file
	 * @return array
	 */
	public function getRevisions($file)
	{
		// check that revisions are enabled
		if (!$this->isEnabled()) {
			return;
		}

		// normalize
		$this->normalizePath($file);

		// pass through extended API method
		return $this->addon->api($this->getExtension())->getRevisions($file);
	}


	/**
	 * Returns the timestamp for when a given $revision of a $file was stored
	 *
	 * @param string $file     The file to look up
	 * @param string $revision The specific revision to grab content from
	 * @return string
	 */
	public function getRevisionTimestamp($file, $revision)
	{
		// check that revisions are enabled
		if (!$this->isEnabled()) {
			return;
		}

		// normalize
		$this->normalizePath($file);

		// pass through extended API method
		return $this->addon->api($this->getExtension())->getRevisionTimestamp($file, $revision);
	}


	/**
	 * Returns the author committing the given $revision for $file
	 *
	 * @param string $file     The file to look up
	 * @param string $revision The specific revision to grab content from
	 * @return string
	 */
	public function getRevisionAuthor($file, $revision)
	{
		// check that revisions are enabled
		if (!$this->isEnabled()) {
			return;
		}

		// normalize
		$this->normalizePath($file);

		// pass through extended API method
		return $this->addon->api($this->getExtension())->getRevisionAuthor($file, $revision);
	}


	/**
	 * Get the content data from a specific revision
	 *
	 * @param string $file The file in question (without content root)
	 * @param string $revision Revision identifier. Eg. the SHA or index
	 * @return string            The file's contents
	 */
	public function getRevision($file, $revision)
	{
		// check that revisions are enabled
		if (!$this->isEnabled()) {
			return;
		}

		// normalize
		$this->normalizePath($file);

		// pass through extended API method
		return $this->addon->api($this->getExtension())->getRevision($file, $revision);
	}


	/**
	 * Deletes revisions for a given $file
	 *
	 * @param string $file File to delete revisions for
	 * @return void
	 */
	public function deleteRevisions($file)
	{
		if (!$this->isEnabled()) {
			return;
		}

		// normalize
		$this->normalizePath($file);

		// pass through extended API method
		$this->addon->api($this->getExtension())->deleteRevisions($file);
	}


	/**
	 * Deletes revisions for a given $file
	 *
	 * @param string  $old_file  File being moved
	 * @param string  $new_file  New location for the file
	 * @return void
	 */
	public function moveFile($old_file, $new_file)
	{
		if (!$this->isEnabled()) {
			return;
		}

		// normalize
		$this->normalizePath($file);

		// pass through extended API method
		$this->addon->api($this->getExtension())->moveFile($old_file, $new_file);
	}


	/**
	 * Save a revision of the file to the repo
	 *
	 * @param string $file File to be committed (without content root)
	 * @param string $content Contents of the file
	 * @param string $message Commit message
	 * @param int $timestamp Optional timestamp to use
	 * @param bool $is_new Whether the file is new or not
	 * @return void
	 */
	public function saveRevision($file, $content, $message, $timestamp = null, $is_new = false)
	{
		// check that revisions are enabled
		if (!$this->isEnabled()) {
			return;
		}

		// normalize
		$this->normalizePath($file);

		// pass through extended API method
		$this->addon->api($this->getExtension())->saveRevision($file, $content, $message, $timestamp, $is_new);
	}


	/**
	 * Is the given $revision a true revision of $file?
	 *
	 * @param string $file File to check revision against
	 * @param string $revision Revision to check for
	 * @return bool
	 */
	public function isRevision($file, $revision)
	{
		// check that revisions are enabled
		if (!$this->isEnabled()) {
			return;
		}

		// normalize
		$this->normalizePath($file);

		// pass through extended API method
		return $this->addon->api($this->getExtension())->isRevision($file, $revision);
	}


	/**
	 * Does the given $file have revisions associated with it?
	 *
	 * @param string $file File to check for revisions
	 * @return bool
	 */
	public function hasRevisions($file)
	{
		// check that revisions are enabled
		if (!$this->isEnabled()) {
			return;
		}

		// normalize
		$this->normalizePath($file);

		// pass through extended API method
		return $this->addon->api($this->getExtension())->hasRevisions($file);
	}


	/**
	 * Is the given $revision the latest revisions for $file?
	 *
	 * @param string $file File to check
	 * @param string $revision Revision to check as latest
	 * @return bool
	 */
	public function isLatestRevision($file, $revision)
	{
		// check that revisions are enabled
		if (!$this->isEnabled()) {
			return;
		}

		// normalize
		$this->normalizePath($file);

		// pass through extended API method
		return $this->addon->api($this->getExtension())->isLatestRevision($file, $revision);
	}


	/**
	 * Save a revision if you're opening an existing file with no revisions.
	 *
	 * @param  string $file Name of file to save
	 * @return void
	 */
	public function saveFirstRevision($file)
	{
		// check that revisions are enabled
		if (!$this->isEnabled()) {
			return;
		}

		// normalize
		$this->normalizePath($file);

		// pass through extended API method
		$this->addon->api($this->getExtension())->saveFirstRevision($file);
	}
}