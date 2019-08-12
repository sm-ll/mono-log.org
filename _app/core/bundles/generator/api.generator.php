<?php
class API_generator extends API
{

	public function generateFileList()
	{
		return $this->core->generateFileList();
	}


	public function generatePage($url)
	{
		return $this->core->generatePage($url);
	}


	public function copyAssets()
	{
		return $this->core->copyAssets();
	}


	public function download()
	{
		return $this->core->download();
	}


	public function folderExists()
	{
		return Folder::exists(Path::assemble(BASE_PATH, $this->config['destination']));
	}

}