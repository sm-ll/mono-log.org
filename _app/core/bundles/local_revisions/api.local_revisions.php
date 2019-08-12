<?php
/**
 * API_local_revisions
 * A local implementation of revisions
 */
class API_local_revisions extends API implements Interface_revisions
{
	private $revision_cache = array();


	/**
	 * There are no configurations needed for local revisions.
	 * 
	 * @return bool
	 */
	public function isProperlyConfigured()
	{
		return true;
	}


	/**
	 * Check to see if a $revision exists for a $file
	 * 
	 * @param string $file  File to look up
	 * @param string $revision  Revision to look up
	 * @return bool
	 */
	public function isRevision($file, $revision)
	{
		return $this->storage->exists(Helper::makeHash($file) . '/' . $revision);
	}


	/**
	 * Is the given $revision the very latest revision for $file?
	 * 
	 * @param string $file  File to check
	 * @param string $revision  Revision to check as being latest
	 * @return bool
	 */
	public function isLatestRevision($file, $revision)
	{
		// grab list of revisions for a given $file
		$list = $this->storage->listAll(Helper::makeHash($file));

		// if it's empty, this can't be the latest
		if (empty($list)) {
			return false;
		}

		// sort
		rsort($list);

		// compare
		return ($revision === $list[0]);
	}


	/**
	 * Does $file have revisions stored in the system?
	 * 
	 * @param string $file  File to check for revisions
	 * @return bool
	 */
	public function hasRevisions($file)
	{
		$list = $this->storage->listAll(Helper::makeHash($file));

		return (!empty($list));
	}


	/**
	 * Store a revision for a given $file
	 * 
	 * @param string $file  The file to save a revision for
	 * @param string $content  The content to store as data for this revision
	 * @param string $message  The commit message
	 * @param null   $timestamp  An optional timestamp for when this revision occurred
	 * @param bool   $is_new  Whether the file is new or not
	 */
	public function saveRevision($file, $content, $message, $timestamp = null, $is_new = false)
	{
		// store in folder of hashed file name
		$hash = Helper::makeHash($file);

		// determine revision number
		$nth_revision = count($this->storage->listAll($hash)) + 1;

		$revision_data = array(
			'file'       => $file,
			'revision'   => $nth_revision,
			'message'    => $message,
			'timestamp'  => ($timestamp) ? Date::resolve($timestamp) : time(),
			'author_uid' => Auth::getCurrentMember()->getUID(),
			'data'       => $content
		);

		$location = $hash . '/' . str_pad($nth_revision, 4, '0', STR_PAD_LEFT);
		$this->storage->putYAML($location, $revision_data);
	}


	/**
	 * Store the first revision for a given $file
	 * 
	 * @param string $file  The file needing a first revision
	 */
	public function saveFirstRevision($file)
	{
		// if this has revisions, abort
		if ($this->hasRevisions($file)) {
			return;
		}

		// get file contents
		$full_path        = Path::assemble(BASE_PATH, Config::getContentRoot(), $file);
		$existing_content = File::get($full_path);

		// save revision
		$this->saveRevision($file, $existing_content, __('first_save'), File::getLastModified($full_path));
	}


	/**
	 * Delete all revisions for a given $file
	 * 
	 * @param string $file
	 */
	public function deleteRevisions($file)
	{
		$this->storage->delete(Helper::makeHash($file));
	}


	/**
	 * Re-hash $old_file hash into $new_file hash for revisions
	 * 
	 * @param string $old_file  The file's currently-existing path
	 * @param string $new_file  The file's new path
	 */
	public function moveFile($old_file, $new_file)
	{
		$this->storage->move(Helper::makeHash($old_file), Helper::makeHash($new_file));
	}


	/**
	 * Loads all information about a given revision
	 * 
	 * @param string  $file  The file to look up a revision for
	 * @param string  $revision  The revision to grab
	 * @return array|mixed
	 */
	public function loadRevision($file, $revision)
	{
		if (!$this->isRevision($file, $revision)) {
			$this->flash->set('error', 'Could not find the revision requested.');
			return array();
		}

		$hash         = Helper::makeHash($file);
		$storage_path = $hash . '/' . str_pad($revision, 4, '0', STR_PAD_LEFT);

		return $this->storage->getYAML($storage_path, array());
	}


	/**
	 * Returns revision data for a given $revision on a given $file
	 * 
	 * @param string $file  The file to return revision data for
	 * @param string $revision  The specific revision
	 * @return mixed|string
	 */
	public function getRevision($file, $revision)
	{		
		$file_hash = Helper::makeHash($file);
		
		if (!isset($this->revision_cache[$file_hash . '_' . $revision])) {
			$this->revision_cache[$file_hash . '_' . $revision] = $this->loadRevision($file, $revision);
		}
		
		return array_get($this->revision_cache[$file_hash . '_' . $revision], 'data', array());
	}


	/**
	 * Returns the timestamp for a given $revision on a given $file
	 * 
	 * @param string $file  The file to return the revision timestamp for
	 * @param string $revision  The specific revision
	 * @return mixed|string
	 */
	public function getRevisionTimestamp($file, $revision)
	{
		$file_hash = Helper::makeHash($file);

		if (!isset($this->revision_cache[$file_hash . '_' . $revision])) {
			$this->revision_cache[$file_hash . '_' . $revision] = $this->loadRevision($file, $revision);
		}
		
		return array_get($this->revision_cache[$file_hash . '_' . $revision], 'timestamp', false);
	}


	/**
	 * Returns the author for a given $revision on a given $file
	 * 
	 * @param string $file  The file to return the revision author for 
	 * @param string $revision  The specific revision
	 * @return mixed|string
	 */
	public function getRevisionAuthor($file, $revision)
	{
		$file_hash = Helper::makeHash($file);

		if (!isset($this->revision_cache[$file_hash . '_' . $revision])) {
			$this->revision_cache[$file_hash . '_' . $revision] = $this->loadRevision($file, $revision);
		}

		$author_uid = array_get($this->revision_cache[$file_hash . '_' . $revision], 'author_uid', false);
		if ($author_uid) {
			$member = Member::getProfileByUID($author_uid);
			return array_get($member, 'username', null);
		}
		
		return null;
	}


	/**
	 * Gets a list of all revisions for the given $file
	 * 
	 * @param string $file  The file to retrieve revisions for
	 * @return array|mixed
	 */
	public function getRevisions($file)
	{
		$hash             = Helper::makeHash($file);
		$revisions        = array();
		$stored_revisions = array_reverse($this->storage->listAll($hash));
		$current          = $this->addon->api('revisions')->getCurrentRevision();
		$i                = 0;

		foreach ($stored_revisions as $revision) {
			$storage_filename = $hash . '/' . $revision;
			$data             = $this->storage->getYAML($storage_filename);
			$this_revision    = str_pad($data['revision'], 4, '0', STR_PAD_LEFT);

			$revisions[] = array(
				'revision'   => $this_revision,
				'message'    => $data['message'],
				'timestamp'  => $this->getRevisionTimestamp($file, $this_revision),
				'author'     => $this->getRevisionAuthor($file, $this_revision),
				'is_current' => ($current === $revision || is_null($current) && $i === 0)
			);

			$i++;
		}

		return $revisions;
	}
}