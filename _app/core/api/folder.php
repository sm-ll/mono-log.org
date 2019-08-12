<?php

use FilesystemIterator as fIterator;
use Symfony\Component\Finder\Finder as Finder;
use Symfony\Component\Filesystem\Filesystem as Filesystem;

/**
 * Folder
 * API for interacting with folders (directories) on the server
 *
 * @author      JStatamic
 * @package     API
 * @copyright   2014 Statamic
 */
class Folder
{
    /**
     * Create a directories recursively
     *
     * @param string|array  $dirs The directory path
     * @param integer       $mode The directory mode
     */
    public static function make($dirs, $mode = 0777)
    {
        Debug::increment('folders', 'created');
        $fs = new Filesystem();

        $fs->mkdir($dirs, $mode);
    }


    /**
     * Move a directory from one location to another.
     *
     * @param  string  $source  Path of source folder to move
     * @param  string  $destination  Destination path of folder
     * @param  int     $options  Options
     * @return boolean
     */
    public static function move($source, $destination, $options = fIterator::SKIP_DOTS)
    {
        Debug::increment('folders', 'moved');
        return self::copy($source, $destination, TRUE, $options);
    }


    /**
     * Recursively copy directory contents to another directory.
     *
     * @param  string  $source  Path of source folder to copy
     * @param  string  $destination  Destination path for new copy of folder
     * @param  bool    $delete  Delete the origin copy?
     * @param  int     $options  Options
     * @return bool
     */
    public static function copy($source, $destination, $delete = FALSE, $options = fIterator::SKIP_DOTS)
    {
        if ( ! is_dir($source)) return FALSE;
        Debug::increment('folders', 'copied');

        // First we need to create the destination directory if it doesn't already exist.
        // Our make() method takes care of the check.
        self::make($destination);

        $items = new fIterator($source, $options);

        foreach ($items as $item)
        {
            $location = $destination.DIRECTORY_SEPARATOR.$item->getBasename();

            // If the file system item is a directory, we will recurse the
            // function, passing in the item directory. To get the proper
            // destination path, we'll add the basename of the source to
            // to the destination directory.
            if ($item->isDir())
            {
                $path = $item->getRealPath();

                if (! static::copy($path, $location, $delete, $options)) return FALSE;

                if ($delete) @rmdir($item->getRealPath());
            }
            // If the file system item is an actual file, we can copy the
            // file from the bundle asset directory to the public asset
            // directory. The "copy" method will overwrite any existing
            // files with the same name.
            else
            {
                if(! copy($item->getRealPath(), $location)) return FALSE;

                if ($delete) @unlink($item->getRealPath());
            }
        }

        unset($items);
        if ($delete) @rmdir($source);

        return TRUE;
    }


    /**
     * Recursively delete a directory.
     *
     * @param string|array $directories A folder name or an array of folder to remove
     * @return void
     */
    public static function delete($directories, $preserve = FALSE)
    {
        Debug::increment('folders', 'deleted');
        $fs = new Filesystem();

        $fs->remove($directories);

        if ($preserve) {
            $fs->mkdir($directories);
        }
    }


    /**
     * Empty the specified directory of all files and folders.
     *
     * @param  string|array  $directory folder(s) to empty
     * @return void
     */
    public static function wipe($directory)
    {
        Debug::increment('folders', 'emptied');
        self::delete($directory, TRUE);
    }


    /**
     * Get the most recently modified file in a directory.
     *
     * @param  string       $directory  Path of directory to query
     * @param  int          $options  Options
     * @return SplFileInfo
     */
    public static function latest($directory, $options = fIterator::SKIP_DOTS)
    {
        $latest = NULL;

        $time = 0;

        $items = new fIterator($directory, $options);

        // To get the latest created file, we'll simply loop through the
        // directory, setting the latest file if we encounter a file
        // with a UNIX timestamp greater than the latest one.
        foreach ($items as $item)
        {
            if ($item->getMTime() > $time)
            {
                $latest = $item;
                $time = $item->getMTime();
            }
        }

        return $latest;
    }


    /**
     * Checks to see if a given $folder is writable
     *
     * @param string  $folder  Folder to check
     * @return bool
     */
    public static function isWritable($folder)
    {
        return self::exists($folder) && is_writable($folder);
    }


    /**
     * Checks the existence of files or directories.
     *
     * @param string|array $files A filename, an array of files to check
     * @return Boolean true if the file exists, false otherwise
     */
    public static function exists($files)
    {
        $fs = new Filesystem();

        return $fs->exists($files);
    }
    
    
    /**
     * Checks if a given $folder matches a given $pattern
     * 
     * @param string  $folder  Folder to check
     * @param string  $pattern  Wildcard-string pattern to match against
     * @return bool
     */
    public static function matchesPattern($folder, $pattern)
    {
        $star_position = strpos($pattern, '*');

        if ($pattern === '*') {
            // the pattern is only star, return it
            return true;
        } elseif ($star_position !== false) {
            // looking for a wildcard
            return (strpos($folder, rtrim(substr($pattern, 0, $star_position))) !== false);
        } else {
            // just matching a string
            return ($folder == $pattern);
        }
    }
}