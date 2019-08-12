<?php

use Symfony\Component\Finder\Finder as Finder;

class Plugin_Get_Files extends Plugin
{
    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | Paramers
        |--------------------------------------------------------------------------
        |
        | Match overrides Extension. Exclusion applies in both cases.
        |
        */

        $match      = $this->fetchParam('match', false);
        $exclude    = $this->fetchParam('exclude', false);
        $extension  = $this->fetchParam(array('extension', 'type'), false);
        $in         = $this->fetchParam(array('in', 'folder', 'from'), false);
        $not_in     = $this->fetchParam('not_in', false);
        $file_size  = $this->fetchParam('file_size', false);
        $file_date  = $this->fetchParam('file_date', false);
        $depth      = $this->fetchParam('depth', false);
        $sort_by    = $this->fetchParam(array('sort_by', 'order_by'), false);
        $sort_dir   = $this->fetchParam(array('sort_dir', 'sort_direction'), 'asc');
        $limit      = $this->fetchParam('limit', false);

        if ($in) {
            $in = Helper::explodeOptions($in);
        }

        if ($not_in) {
            $not_in = Helper::explodeOptions($not_in);
        }

        if ($file_size) {
            $file_size = Helper::explodeOptions($file_size);
        }

        if ($extension) {
            $extension = Helper::explodeOptions($extension);
        }

        /*
        |--------------------------------------------------------------------------
        | Finder
        |--------------------------------------------------------------------------
        |
        | Get_Files implements most of the Symfony Finder component as a clean
        | tag wrapper mapped to matched filenames.
        |
        */

        $finder = new Finder();

        if ($in) {
            foreach ($in as $location) {
                $finder->in(Path::fromAsset($location));
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Name
        |--------------------------------------------------------------------------
        |
        | Match is the "native" Finder name() method, which is supposed to
        | implement string, glob, and regex. The glob support is only partial,
        | so "extension" is a looped *single* glob rule iterator.
        |
        */

        if ($match) {
            $finder->name($match);
        } elseif ($extension) {
            foreach ($extension as $ext) {
                $finder->name("*.{$ext}");
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Exclude
        |--------------------------------------------------------------------------
        |
        | Exclude directories from matching. Remapped to "not in" to allow more
        | intuitive differentiation between filename and directory matching.
        |
        */

        if ($not_in) {
            foreach ($not_in as $location) {
                $finder->exclude($location);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Not Name
        |--------------------------------------------------------------------------
        |
        | Exclude files matching a given pattern: string, regex, or glob.
        | By default we don't allow looking for PHP files. Be smart.
        |
        */

        if ($this->fetchParam('allow_php', false) !== TRUE) {
            $finder->notName("*.php");
        }

        if ($exclude) {
            $finder->notName($exclude);
        }

        /*
        |--------------------------------------------------------------------------
        | File Size
        |--------------------------------------------------------------------------
        |
        | Restrict files by size. Can be chained and allows comparison operators.
        |
        */

        if ($file_size) {
            foreach($file_size as $size) {
                $finder->size($size);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | File Date
        |--------------------------------------------------------------------------
        |
        | Restrict files by last modified date. Can use comparison operators, and
        | since/after is aliased to >, and until/before to <.
        |
        */

        if ($file_date) {
            $finder->date($file_date);
        }

        /*
        |--------------------------------------------------------------------------
        | Depth
        |--------------------------------------------------------------------------
        |
        | Recursively traverse directories, starting at 0.
        |
        */

        if ($depth) {
            $finder->depth($depth);
        }


        /*
        |--------------------------------------------------------------------------
        | Sort By
        |--------------------------------------------------------------------------
        |
        | Sort by name, file, or type
        |
        */

        if ($sort_by) {
            if ($sort_by === 'file' || $sort_by === 'name') {
                $finder->sortByName();
            } elseif ($sort_by === 'type') {
                $finder->sortByType();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Assemble File Array
        |--------------------------------------------------------------------------
        |
        | Select the important bits of data on the list of files.
        |
        */
        
        $matches = $finder->files()->followLinks();

        $files = array();
        foreach ($matches as $file) {
            $files[] = array(
                'extension' => $file->getExtension(),
                'filename' => $file->getFilename(),
                'file' => Path::toAsset($file->getPathname()),
                'name' => Path::toAsset($file->getPathname()),
                'size' => File::getHumanSize($file->getSize()),
                'size_bytes' => $file->getSize(),
                'size_kilobytes' => number_format($file->getSize() / 1024, 2),
                'size_megabytes' => number_format($file->getSize() / 1048576, 2),
                'size_gigabytes' => number_format($file->getSize() / 1073741824, 2),
                'is_image' => File::isImage($file->getPathname())
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Sort Direction
        |--------------------------------------------------------------------------
        |
        | Set the sort direction, defaulting to "asc" (ascending)
        |
        */

        if ($sort_dir === 'desc') {
            $files = array_reverse($files);
        }


        /*
        |--------------------------------------------------------------------------
        | Limit Files
        |--------------------------------------------------------------------------
        |
        | Limit the number of files returned. Needs to be run after sort_dir to 
        | ensure consistency.
        |
        */

        if ($limit) {
            $files = array_slice($files, 0, $limit);
        }

        return Parse::tagLoop($this->content, $files, true, $this->context);
    }
}
