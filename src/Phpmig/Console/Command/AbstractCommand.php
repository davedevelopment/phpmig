<?php
/**
 * @package
 * @subpackage
 */
namespace Phpmig\Console\Command;

use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Config\FileLocator,
    Phpmig\Migration\Migration,
    Phpmig\Migration\Migrator,
    Phpmig\Adapter\AdapterInterface;

/**
 * This file is part of phpmig
 *
 * Copyright (c) 2011 Dave Marshall <dave.marshall@atstsolutuions.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Abstract command, contains bootstrapping info
 *
 * @author      Dave Marshall <david.marshall@atstsolutions.co.uk>
 */
abstract class AbstractCommand extends Command
{
    /**
     * @var \ArrayAccess
     */
    protected $container = null;

    /**
     * @var \Phpmig\Adapter\AdapterInterface
     */
    protected $adapter = null;

    /**
     * @var string
     */
    protected $bootstrap = null;

    /**
     * @var array
     */
    protected $migrations = array();

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addOption('--bootstrap', '-b', InputArgument::OPTIONAL, 'The bootstrap file to load');
    }

    /**
     * Bootstrap phpmig
     *
     * @return void
     */
    protected function bootstrap(InputInterface $input, OutputInterface $output)
    {
        /**
         * Bootstrap
         */
        $bootstrap = $input->getOption('bootstrap');

        if (null === $bootstrap) {
            $bootstrap = 'phpmig.php';
        }

        $cwd = getcwd();

        $locator = new FileLocator(array(
            $cwd . DIRECTORY_SEPARATOR . 'config',
        ));

        $bootstrap = $locator->locate($bootstrap, $cwd, $first = true);
        $this->setBootstrap($bootstrap);

        /**
         * Prevent scope clashes
         */
        $func = function() use ($bootstrap) {
            return require $bootstrap;
        };

        $container = $func();

        if (!($container instanceof \ArrayAccess)) {
            throw new RuntimeException($bootstrap . " must return object of type \ArrayAccess");
        }
        $this->setContainer($container);

        /**
         * Adapter
         */
        if (!isset($container['phpmig.adapter'])) {
            throw new RuntimeException($bootstrap . " must return container with service at phpmig.adapter");
        }

        $adapter = $container['phpmig.adapter'];

        if (!($adapter instanceof \Phpmig\Adapter\AdapterInterface)) {
            throw new RuntimeException("phpmig.adapter must be an instance of \Phpmig\Adapter\AdapterInterface");
        }

        if (!$adapter->hasSchema()) {
            $adapter->createSchema();
        }

        $this->setAdapter($adapter);

        /**
         * Migrations
         */
        if (!isset($container['phpmig.migrations'])) {
            throw new RuntimeException($bootstrap . " must return container with array at phpmig.migrations");
        }

        $migrations = $container['phpmig.migrations'];

        if (!is_array($migrations)) {
            throw new RuntimeException("phpmig.migrations must be an array of paths to migrations");
        }

        $versions = array();
        $names = array();
        foreach($migrations as $path) {
            if (!preg_match('/^[0-9]+/', basename($path), $matches)) {
                throw new \InvalidArgumentException(sprintf('The file "%s" does not have a valid migration filename', $path));
            }

            $version = $matches[0];

            if (isset($versions[$version])) {
                throw new \InvalidArgumentException(sprintf('Duplicate migration, "%s" has the same version as "%s"', $path, $versions[$version]));
            }

            $class = preg_replace('/^[0-9]+_/', '', basename($path));
            $class = str_replace('_', ' ', $class);
            $class = ucwords($class);
            $class = str_replace(' ', '', $class);
            if (false !== strpos($class, '.')) {
                $class = substr($class, 0, strpos($class, '.'));
            }

            if (isset($names[$class])) {
                throw new \InvalidArgumentException(sprintf(
                    'Migration "%s" has the same name as "%s"', 
                    $path,
                    $names[$class]
                ));
            }
            $names[$class] = $path;

            require_once $path;
            if (!class_exists($class)) {
                throw new \InvalidArgumentException(sprintf(
                    'Could not find class "%s" in file "%s"', 
                    $class,
                    $path
                ));
            }

            $migration = new $class($version);

            if (!($migration instanceof Migration)) {
                throw new \InvalidArgumentException(sprintf(
                    'The class "%s" in file "%s" must extend \Phpmig\Migration\Migration',
                    $class,
                    $path
                ));
            }

            $migration->setOutput($output); // inject output

            $versions[$version] = $migration;
        }

        ksort($versions);

        /**
         * Setup migrator
         */
        $container['phpmig.migrator'] = $container->share(function() use ($container, $adapter, $output) {
            return new Migrator($adapter, $container, $output);
        });

        $this->setMigrations($versions);
    }

    /**
     * Set bootstrap
     *
     * @var string
     * @return AbstractCommand
     */
    public function setBootstrap($bootstrap) 
    {
        $this->bootstrap = $bootstrap;
        return $this;
    }

    /**
     * Get bootstrap
     *
     * @return string 
     */
    public function getBootstrap()
    {
        return $this->bootstrap;
    }

    /**
     * Set migrations
     *
     * @param array $migrations
     * @return AbstractCommand
     */
    public function setMigrations(array $migrations) 
    {
        $this->migrations = $migrations;
        return $this;
    }

    /**
     * Get migrations
     *
     * @return array
     */
    public function getMigrations()
    {
        return $this->migrations;
    }

    /**
     * Set container
     *
     * @var \ArrayAccess
     * @return AbstractCommand
     */
    public function setContainer(\ArrayAccess $container) 
    {
        $this->container = $container;
        return $this;
    }

    /**
     * Get container
     *
     * @return \ArrayAccess
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Set adapter
     *
     * @param AdapterInterface $adapter
     * @return AbstractCommand
     */
    public function setAdapter(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * Get Adapter
     *
     * @return AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

}


