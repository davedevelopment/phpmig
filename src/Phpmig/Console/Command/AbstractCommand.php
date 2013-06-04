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
            $cwd
        ));

        $bootstrap = $locator->locate($bootstrap);
        $this->setBootstrap($bootstrap);

        /**
         * Prevent scope clashes
         */
        $func = function() use ($bootstrap) {
            return require $bootstrap;
        };

        $container = $func();

        if (!($container instanceof \ArrayAccess)) {
            throw new \RuntimeException($bootstrap . " must return object of type \ArrayAccess");
        }
        $this->setContainer($container);

        /**
         * Adapter
         */
        if (!isset($container['phpmig.adapter'])) {
            throw new \RuntimeException($bootstrap . " must return container with service at phpmig.adapter");
        }

        $adapter = $container['phpmig.adapter'];

        if (!($adapter instanceof \Phpmig\Adapter\AdapterInterface)) {
            throw new \RuntimeException("phpmig.adapter must be an instance of \Phpmig\Adapter\AdapterInterface");
        }

        if (!$adapter->hasSchema()) {
            $adapter->createSchema();
        }

        $this->setAdapter($adapter);

        /**
         * Migrations
         */
         $isMigrationsDefined = isset($container['phpmig.migrations']) || isset($container['phpmig.migrations_path']);
         $checkMigrationsFiles = !isset($container['phpmig.migrations']) || is_array($container['phpmig.migrations']);
         $checkMigrationsPath = !isset($container['phpmig.migrations_path']) || is_dir($container['phpmig.migrations_path']);
        if (!$isMigrationsDefined || !$checkMigrationsFiles || !$checkMigrationsPath ) {
            throw new \RuntimeException($bootstrap . " must return container with array at phpmig.migrations or migrations default path at phpmig.migrations_path");
        }
        
        $migrations = array();
        if ( isset($container['phpmig.migrations']) ){
            $migrations = $container['phpmig.migrations'];
        }
        if ( isset($container['phpmig.migrations_path']) ){
            $migrationsPath = realpath($container['phpmig.migrations_path']);
            $migrations = array_merge( $migrations, glob($migrationsPath . DIRECTORY_SEPARATOR . '*.php') );
        }
        $migrations = array_unique($migrations);

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

            $migrationName = preg_replace('/^[0-9]+_/', '', basename($path));
            if (false !== strpos($migrationName, '.')) {
                $migrationName = substr($migrationName, 0, strpos($migrationName, '.'));
            }
            $class = $this->migrationToClassName($migrationName);

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
    
    /**
     * transform create_table_user to CreateTableUser
     */
    protected function migrationToClassName( $migrationName )
    {
        $class = str_replace('_', ' ', $migrationName);
        $class = ucwords($class);
        return str_replace(' ', '', $class);
    }

}


