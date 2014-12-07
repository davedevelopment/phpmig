<?php
namespace Phpmig\Utility;

use Symfony\Component\Config\FileLocator;

class Helper {

    protected $properties = null;

    /**
     * @param string $filename
     * @return array|string
     */
    function findPropertiesFile($filename)
    {
        if (null === $filename) {
            $filename = 'build.properties';
        }

        $cwd = getcwd();

        $locator = new FileLocator(array(
            $cwd . DIRECTORY_SEPARATOR . 'config',
            $cwd
        ));

        return $locator->locate($filename);
    }

    function getProperties($propertiesFile) {
        if($this->properties == null ) {
            $this->properties = $this->loadPropertiesFile($propertiesFile);
        }
        return $this->properties;
    }


    function loadPropertiesFile($propertiesFile)
    {
        $directory = getcwd();

        $locator = new FileLocator(array(
            dirname($propertiesFile),
            $directory . DIRECTORY_SEPARATOR . 'config',
        ));

        $propertiesFile = $locator->locate(basename($propertiesFile));
        return parse_ini_file($propertiesFile);

    }

} 