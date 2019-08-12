<?php
/**
 * Interface_revisions
 * The interface that must be supported to extend revisions
 *
 * @author  Statamic <gentlemen@statamic.com>
 */
interface Interface_revisions
{
	/**
	 * Determines if this implementation of revisions has been properly configured,
	 * this is a good place to check for API keys, access keys, etc. as necessary
	 *
	 * @return bool
	 */
	public function isProperlyConfigured();

	/**
	 * Checks that a given $revision exists for a given $file in the system
	 *
	 * @param string $file     File to check for
	 * @param string $revision Revision key to check
	 * @return bool
	 */
	public function isRevision($file, $revision);

	/**
	 * Checks that a given $revision exists and is the latest revisions for a given $file
	 *
	 * @param string $file     File to check through
	 * @param string $revision Revision to consider as latest
	 * @return bool
	 */
	public function isLatestRevision($file, $revision);

	/**
	 * Checks to see that a given $file has revisions stored for it
	 *
	 * @param string $file File to check for revisions
	 * @return bool
	 */
	public function hasRevisions($file);

	/**
	 * Saves a revision for the given $file with the $content provided, includes a
	 * commit $message and optional $timestamp for back-dating (not required to support)
	 *
	 * @param string $file      File to be saved
	 * @param string $content   The content to be stored to the file
	 * @param string $message   The commit message for this post
	 * @param int    $timestamp An optional timestamp for backdating (not required to support)
	 * @param bool   $is_new    Whether the file is new or not
	 * @return void
	 */
	public function saveRevision($file, $content, $message, $timestamp = null, $is_new = false);

	/**
	 * Attempts to save the first revision for a given $file, this won't always be possible,
	 * but if no revisions exist, revisions will attempt to create a first revision each time;
	 * it's OK if this does nothing
	 *
	 * @param string $file File that is attempting to save
	 * @return void
	 */
	public function saveFirstRevision($file);

	/**
	 * Deletes all revision history for a given $file
	 *
	 * @param string $file File to delete revisions for
	 * @return void
	 */
	public function deleteRevisions($file);

	/**
	 * Accounts for an $old_file being renamed to $new_file
	 * 
	 * @param string  $old_file  Old file name
	 * @param string  $new_file  New file name
	 * @return void
	 */
	public function moveFile($old_file, $new_file);


	/**
	 * Returns the contents of a $file at the given $revision
	 *
	 * @param string $file     The file to look up
	 * @param string $revision The specific revision to grab content from
	 * @return string
	 */
	public function getRevision($file, $revision);


	/**
	 * Returns the timestamp for when a given $revision of a $file was stored
	 *
	 * @param string $file     The file to look up
	 * @param string $revision The specific revision to grab content from
	 * @return string
	 */
	public function getRevisionTimestamp($file, $revision);

	
	/**
	 * Returns the author committing the given $revision for $file
	 *
	 * @param string $file     The file to look up
	 * @param string $revision The specific revision to grab content from
	 * @return string
	 */
	public function getRevisionAuthor($file, $revision);


	/**
	 * Should return an array of revisions for a given $file, the expects array returned is an
	 * array of arrays that look like this:
	 *
	 * return array(
	 *    array(
	 *       'revision'       => // (string) revision identifier,
	 *       'message'        => // (string) commit message
	 *       'timestamp'      => // (int) timestamp of revision
	 *       'author'         => // (string) [optional] name of author saving content
	 *       'is_current'     => // (bool) if this is the revision currently being viewed
	 *    ),
	 *    ...
	 * );
	 *
	 * You can get the revision currently being viewed with:
	 * $this->addon->api('revisions')->getCurrentRevision();
	 *
	 * This will be either the revision identifier, or null.
	 *
	 * @param $file
	 * @return array
	 */
	public function getRevisions($file);
} 
