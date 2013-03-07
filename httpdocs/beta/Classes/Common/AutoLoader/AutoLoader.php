<?php

namespace Classes\Common\AutoLoader;

use \Exception;

/**
 * Class for the sample autoloader provided by the fig-standards group.
 *
 * @author Felix Kastner <felix@chapterfain.com>
 */
class AutoLoader
{
    /**
     * Constructor.
     */
    public function __construct() {
        spl_autoload_register(array($this, 'load'));
    }

    /**
     * load is a sample autoloader provided by the fig-standards group.
     * It uses namespaces and the spl_autoload_register function to automatically load classes as needed.
     *
     * @author fig-standards, modified by Felix Kastner <felix@chapterfain.com>
     * @param string $className contains the name of the class
     * @throws Exception if file can't be included
     */
    private function load($className)
    {
        $className = ltrim($className, '\\');
        $fileName  = '';
        $lastNsPos = strrpos($className, '\\');
        if ($lastNsPos) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
        if(!@include $fileName) {
            throw new Exception('AutoLoader failed to load "' . $fileName . '"');
        }
    }
}
?>