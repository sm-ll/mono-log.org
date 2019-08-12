<?php

use Intervention\Image\Image;

class Hooks_redactor extends Hooks
{

    public function redactor__upload()
    {
        $this->authCheck();

        $files = _Upload::uploadBatch(Request::get('path'), 'file');
        $return = array(
	        'filename' => $files[0]['name'],
	        'filelink' => $files[0]['path']
        );

        echo stripslashes(json_encode($return));
    }

    public function redactor__fetch_images()
    {
        $this->authCheck();

        $dir = Path::tidy(ltrim(Request::get('path'), '/').'/');
        $image_list = glob($dir."*.{jpg,jpeg,gif,png}", GLOB_BRACE);

        $images = array();
        if (count($image_list) > 0) {
            foreach ($image_list as $image) {
                $image = Path::toAsset($image);
                $images[] = array(
                    'thumb' => $image,
                    'image' => $image,
	                'title' => basename($image)
                );
            }
        }

        echo json_encode($images);
    }

    public function redactor__fetch_files()
    {
        $this->authCheck();

        $dir = Path::tidy(ltrim(Request::get('path'), '/').'/');
        $file_list = glob($dir."*.*", GLOB_BRACE);

        $files = array();
        if (count($file_list) > 0) {
            foreach ($file_list as $file) {
                $pi = pathinfo($file);
                $files[] = array(
                    'link'  => Path::toAsset($file),
                    'title' => $pi['filename'],
                    'name'  => $pi['basename'],
                    'size'  => File::getHumanSize(File::getSize(Path::assemble(BASE_PATH, $file)))
                );
            }
        }

        echo json_encode($files);
    }

    function authCheck($role = 'admin')
    {
        $app = \Slim\Slim::getInstance();
        $user = Auth::getCurrentMember();

        if ($user) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        exit("Invalid Request");
    }
}
