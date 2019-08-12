<?php
/**
 * Hooks_revisions
 * Hooks where the core revisions functionality integrates into Statamic
 *
 * @author  Statamic <gentlemen@statamic.com>
 */
class Hooks_revisions extends Hooks
{
	/**
	 * Add the modal to the footer
	 *
	 * @return string
	 */
	public function control_panel__add_to_foot()
	{
		// don't do anything on the login screen
		if (Request::getResourceURI() == '/login') {
			return false;
		}

		// master switch for revisions
		if (!$this->core->isEnabled()) {
			return false;
		}

		if (!URL::getCurrent() == '/publish') {
			return false;
		}

		$path = $this->core->getPath();

		$view_data = array(
			'revisions' => $this->core->getRevisions($path),
			'path'      => $path
		);

		// revision selector
		$view = File::get($this->getAddonLocation() . 'views/modal.html');
		$html = Parse::template($view, $view_data);

		// revision message
		$view = File::get($this->getAddonLocation() . 'views/commit_modal.html');
		$html .= Parse::template($view, $view_data);

		return $html;
	}


	/**
	 * Access the entry/page data when its published
	 *
	 * @param  array $publish_data
	 * @return void
	 */
	public function control_panel__post_publish($publish_data)
	{
		// master switch for revisions
		if (!$this->core->isEnabled()) {
			return;
		}

		// The hook sends an array of info, we just need the data key
		$publish_data = $publish_data['data'];

		// Get content of existing file and of what is about to be saved
		$path                  = Path::tidy('/' . $publish_data['file']);
		$file_content          = File::buildContent($publish_data['yaml'], $publish_data['content']);

		// Generate the commit message
		$msg_prefix = Config::get('_revisions_message_prefix', '') . ' ';
		$msg        = ($msg = Request::post('revisions__commit_message')) ? $msg : __('published_on') . " " . Date::format(Config::getDateFormat());
		$full_msg   = trim($msg_prefix . $msg);

		// Strip off the content root
		$pattern = '/^\/' . Config::getContentRoot() . '\//';
		$path    = preg_replace($pattern, '', $path);

		$this->core->saveRevision($path, $file_content, $full_msg, null, $publish_data['new']);
	}


	/**
	 * Add to the top of the publish form
	 *
	 * @return string
	 */
	public function control_panel__add_to_publish_form_header()
	{
		// master switch for revisions
		if (!$this->core->isEnabled()) {
			return false;
		}

		$app = \Slim\Slim::getInstance();

		// hidden message field
		$html = '<input type="hidden" name="revisions__commit_message" id="revision-message">';
		$html .= '<div id="revision-default-serialized" style="display: none !important;"></div>';

		if ($this->blink->get('is_revision') && !$this->blink->get('is_latest_revision')) {
			// get the revision data
			$timestamp  = $this->core->getRevisionTimestamp($this->core->getPath(), $this->blink->get('current_revision'));

			$html .= '<div class="revisions-feedback">';
			$html .= '  <span class="ss-icon">help</span>';
			$html .= '  ' . sprintf(__('viewing_revision'), Date::format(Config::getDateFormat(), $timestamp)) . ' ';
			$html .= '  <a href="' . $app->urlFor('publish') . '?path=' . URL::sanitize(Request::get('path')) . '">' . __('viewing_revision_link') . '.';
			$html .= '</div>';
		}

		return $html;
	}


	/**
	 * Add revisions button to the status bar
	 *
	 * @param string $file Name of file being viewed
	 * @return null|string
	 */
	public function control_panel__add_to_status_bar($file)
	{
		if (!$this->core->isEnabled() || !$this->core->hasRevisions($file)) {
			return null;
		}

		$html = '<li id="revisions-rollback">';
		$html .= '<a href="" class="revisions-rollback">';
		$html .= '<span class="ss-icon">refresh</span> ' . __('view_page_history');
		$html .= '</a>';
		$html .= '</li>';

		return $html;
	}


	/**
	 * Swap out form content if necessary
	 *
	 * @param array $data The form data that is going to be used unless changed
	 * @return array
	 */
	public function control_panel__form_content($data)
	{
		// master switch for revisions
		if (!$this->core->isEnabled()) {
			return $data;
		}

		if ($this->core->isRevisionSelected()) {
			$revision = Request::get('revision');
			$file     = Request::get('path');
			$this->core->normalizePath($file);

			// does this revision exist?
			if ($this->core->isRevision($file, $revision)) {
				$this->blink->set('is_revision', true);
				$this->blink->set('is_latest_revision', $this->core->isLatestRevision($file, $revision));
				$this->blink->set('current_revision', $revision);

				// load revision content
				$raw_file = $this->core->getRevision($file, $revision);
				$raw_data = Parse::frontMatter($raw_file);

				$data = $raw_data['data'] + array('content' => $raw_data['content']);
			}
		} else {
			// save first revision is none are set
			$file = $this->core->getPath();

			if (!$this->core->hasRevisions($file)) {
				$this->core->saveFirstRevision($file);
			}
		}

		return $data;
	}


	/**
	 * Watch for deleted content
	 *
	 * @param string $file File that is being deleted
	 * @return void
	 */
	public function control_panel__delete($file)
	{
		// master switch for revisions
		if (!$this->core->isEnabled()) {
			return;
		}
		
		$file = rtrim(Path::makeRelative($file, Path::tidy(BASE_PATH . '/' . Config::getContentRoot())), '/');
		$file = $this->core->normalizePath($file);

		$this->core->deleteRevisions($file);
	}
	
	
	/**
	 * Watch for moved content
	 * 
	 * @param array  $files  Array of old and new files
	 * @return void
	 */
	public function control_panel__move($files)
	{
		// master switch for revisions
		if (!$this->core->isEnabled()) {
			return;
		}

		// get files
		$old_file = $files['old_file'];
		$new_file = $files['new_file'];
		
		// normalize
		$old_file = rtrim(Path::makeRelative($old_file, Path::tidy(Config::getContentRoot())), '/');
		$old_file = $this->core->normalizePath($old_file);
		$new_file = rtrim(Path::makeRelative($new_file, Path::tidy(Config::getContentRoot())), '/');
		$new_file = $this->core->normalizePath($new_file);
		
		$this->core->moveFile($old_file, $new_file);
	}
}