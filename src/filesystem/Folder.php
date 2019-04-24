<?php

namespace j\filesystem;

/**
 * Folder structure browser, lists folders and files.
 *
 * Long description for class
 *
 * @package        cake
 * @subpackage    cake.cake.libs
 */
class Folder {
    public $path = null;
    public $sort = false;

    /**
     * Folder constructor.
     * @param bool|false $path
     * @param bool|false $create
     * @param bool|false $mode
     */
    function __construct($path = false, $create = false, $mode = false) {
        if (empty($path)) {
            $path = getcwd();
        }

        if (!file_exists($path) && $create == true) {
            $this->mkdirr($path, $mode);
        }

        $this->cd($path);
    }

    function pwd() {
        return $this->path;
    }

    /**
     * @param $desiredPath
     * @return bool|string
     */
    function cd($desiredPath) {
        $desiredPath = realpath($desiredPath);
        $newPath = $this->isAbsolute($desiredPath) 
            ? $desiredPath 
            : $this->addPathElement($this->path, $desiredPath);
        $isDir = (is_dir($newPath) && file_exists($newPath)) 
            ? $this->path = $newPath 
            : false;
        return $isDir;
     }

    function isWindowsPath($path) {
        $match = preg_match('#^[A-Z]:\\\#i', $path) ? true : false;
        return $match;
    }

    function isAbsolute($path) {
        $match = preg_match('#^\/#', $path) || preg_match('#^[A-Z]:\\\#i', $path);
        return $match;
    }

    function isSlashTerm($path) {
        $match = preg_match('#[\\\/]$#', $path) ? true : false;
        return $match;
    }

    function correctSlashFor($path) {
        return $this->isWindowsPath($path) ? '\\' : '/';
    }

    function slashTerm($path) {
          return $path . ($this->isSlashTerm($path) ? null : $this->correctSlashFor($path));
    }

    function addPathElement($path, $element) {
        return $this->slashTerm($path) . $element;
    }

    /**
     * @param $pathname
     * @param int $mode
     * @return bool
     */
    function mkdirr($pathname, $mode = 0755) {
        if (is_dir($pathname) || empty($pathname)) {
            return true;
        }

        if (is_file($pathname)) {
            trigger_error('mkdirr() File exists', E_USER_WARNING);
            return false;
        }

        $nextPathname = substr($pathname, 0, strrpos($pathname, DIRECTORY_SEPARATOR));

        if ($this->mkdirr($nextPathname, $mode)) {
            if (!file_exists($pathname)) {
                $old  = umask(0);
                $mkdir = mkdir($pathname, $mode);
                umask($old);
                return $mkdir;
            }
        }

        return false;
    }

    static function getFiles($path, $pattern, $recursion = false){
        if($recursion){
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        } else {
            $it = new \FilesystemIterator($path);
        }

        return new \RegexIterator($it, $pattern);
    }
}
