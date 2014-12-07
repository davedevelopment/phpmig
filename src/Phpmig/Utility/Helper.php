<?php
namespace Phpmig\Utility;

use Symfony\Component\Config\FileLocator;

class Helper
{

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

    function getProperties($propertiesFile)
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