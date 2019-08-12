<?php
class API_file extends API
{

	public function getServerFiles($config, $destination)
	{
		return $this->tasks->getServerFiles($config, $destination);
	}


	public function generateModal($config, $destination)
	{
		return $this->tasks->generateModal($config, $destination);
	}


	public function deleteFile()
	{
		return $this->tasks->deleteFile();
	}

}