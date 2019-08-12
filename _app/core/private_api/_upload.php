<?php

class _Upload
{
    private static $files = array();
    
    /**
     * Takes a $_FILES array and standardizes it to be the same regardless of number of uploads
     * 
     * @param array  $files  Files array to standardize
     * @return void
     */
    public static function standardizeFileUploads($files=array())
    {
        if (!count($files)) {
            return $files;
        }
        
        // loop through files to standardize
        foreach ($files as $field => $data) {
            if (!isset(self::$files[$field]) || !is_array(self::$files[$field])) {
                self::$files[$field] = array();
            }

            $data = array(
                'name'      => $data['name'],
                'type'      => $data['type'],
                'tmp_name'  => $data['tmp_name'],
                'size'      => $data['size'],
                'error'     => $data['error']
            );
            
            // loop through _FILES to standardize
            foreach ($data as $key => $value) {
                self::buildFileArray($key, $value, self::$files[$field], $field);
            }
        }
        
        // return our cleaner version
        return self::$files;
    }


    /**
     * Recursively builds an array of files
     * 
     * @param string  $key  Upload key that we're processing
     * @param mixed  $value  Either a string or an array of the value
     * @param array  $output  The referenced array object for manipulation
     * @param string  $path  A string for colon-delimited path searching
     * @return void
     */
    private static function buildFileArray($key, $value, &$output, $path)
    {
        if (is_array($value)) {
            foreach ($value as $sub_key => $sub_value) {
                if (!isset($output[$sub_key]) || !is_array($output[$sub_key])) {
                    $output[$sub_key] = array();
                }
                $new_path = (empty($path)) ? $sub_key : $path . ':' . $sub_key;
                self::buildFileArray($key, $sub_value, $output[$sub_key], $new_path);
            }
        } else {
            $output[$key] = $value;

            // add error message
            if ($key === 'error') {
                $error_message   = self::getFriendlyErrorMessage($value);
                $success_status  = ($value === UPLOAD_ERR_OK);
                    
                $output['error_message'] = $error_message;
                $output['success']       = $success_status;
            } elseif ($key === 'size') {
                $human_readable_size = File::getHumanSize($value);
                $output['size_human_readable'] = $human_readable_size;
            }
        }
    }
    
    
    /**
     * Create friendly error messages for upload issues
     * 
     * @param int  $error  Error int
     * @return string
     */
    private static function getFriendlyErrorMessage($error)
    {
        // these errors are PHP-based
        if ($error === UPLOAD_ERR_OK) {
            return '';
        } elseif ($error === UPLOAD_ERR_INI_SIZE) {
            return Localization::fetch('upload_error_ini_size');
        } elseif ($error === UPLOAD_ERR_FORM_SIZE) {
            return Localization::fetch('upload_error_form_size');
        } elseif ($error === UPLOAD_ERR_PARTIAL) {
            return Localization::fetch('upload_error_err_partial');
        } elseif ($error === UPLOAD_ERR_NO_FILE) {
            return Localization::fetch('upload_error_no_file');
        } elseif ($error === UPLOAD_ERR_NO_TMP_DIR) {
            return Localization::fetch('upload_error_no_temp_dir');
        } elseif ($error === UPLOAD_ERR_CANT_WRITE) {
            return Localization::fetch('upload_error_cant_write');
        } elseif ($error === UPLOAD_ERR_EXTENSION) {
            return Localization::fetch('upload_error_extension');
        } else {
            // we should never, ever see this
            return Localization::fetch('upload_error_unknown');
        }
    }


    /**
     * Upload file(s)
     * 
     * @param  string $destination  Where the file is going
     * @param  string $id           The field took look at in the files array
     * @return array
     */
    public static function uploadBatch($destination = null, $id = null)
    {
        $destination = $destination ?: Request::get('destination');
        $id          = $id ?: Request::get('id');
        $files       = self::standardizeFileUploads($_FILES);
        $results     = array();
  
        // Resizing configuration
        if ($resize = Request::get('resize')) {
            $width   = Request::get('width', null);
            $height  = Request::get('height', null);
            $ratio   = Request::get('ratio', true);
            $upsize  = Request::get('upsize', false);
            $quality = Request::get('quality', '75'); 
        }
  
        // If $files[$id][0] exists, it means there's an array of images.
        // If there's not, there's just one. We want to change this to an array.
        if ( ! isset($files[$id][0])) {
            $tmp = $files[$id];
            unset($files[$id]);
            $files[$id][] = $tmp;
        }
  
        // Process each image
        foreach ($files[$id] as $file) {
  
            // Image data
            $path = File::upload($file, $destination);
            $name = basename($path);
    
            // Resize
            if ($resize) {
                $image = \Intervention\Image\Image::make(Path::assemble(BASE_PATH, $path));
                $resize_folder = Path::assemble($image->dirname, 'resized');
                if ( ! Folder::exists($resize_folder)) {
                    Folder::make($resize_folder);
                }
                $resize_path = Path::assemble($resize_folder, $image->basename);
                $path = Path::toAsset($resize_path);
                $name = basename($path);
                $image->resize($width, $height, $ratio, $upsize)->save($resize_path, $quality);
            }
  
            $results[] = compact('path', 'name');
        }

        return $results;
    }
}