<?php
class Hooks_markitup extends Hooks
{

	public function control_panel__add_to_head()
	{
		if (URL::getCurrent(false) != '/publish' && URL::getCurrent(false) != '/member') {
			return;
		}

		$config = array(
			'max_files'   => 1,
			// these will get changed by JS
			'allowed'     => array(),
			'destination' => 'UPLOAD_PATH', 
			'browse'      => false
		);

		$fieldtype = Fieldtype::render_fieldtype('file', 'markituploader', $config, null, null, 'markituploader', 'markituploader');

		$template = File::get($this->getAddonLocation() . 'views/modal.html');

		return $this->js->inline('Statamic.markituploader = ' . json_encode(Parse::template($template, compact('fieldtype'))) . ';');
	}
}
