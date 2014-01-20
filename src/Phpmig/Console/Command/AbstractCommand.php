<?php
/**
 * @package
 * @subpackage
 */
namespace Phpmig\Console\Command;

use Phpmig\Adapter\AdapterInterface;
use Phpmig\Migration\Migration;
use Phpmig\Migration\Migrator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @const ADAPTER_NAME_SEPARATOR
     */
    const ADAPTER_NAME_SEPARATOR = ".";

    /**
     * @var \ArrayAccess
     */
    protected $container = null;

    /**
     * @var mixed
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
        $this->addOption('--adapter-name', '-a', InputOption::VALUE_REQUIRED, 'The adapter name for the migration');
        $this->addOption('--bootstrap', '-b', InputOption::VALUE_REQUIRED, 'The bootstrap file to load');
    }

    /**
     * Bootstrap phpmig
     *
     * @return void
     */
    protected function bootstrap(InputInterface $input, OutputInterface $output)
    {
        $this->setBootstrap($this->findBootstrapFile($input->getOption('bootstrap')));

        $container = $this->bootstrapContainer();
        $this->setContainer($container);
        $this->setAdapter($this->bootstrapAdapter($input->getOption('adapter-name')));
        $this->setMigrations($this->bootstrapMigrations($input, $output));

        $container['phpmig.migrator'] = $this->bootstrapMigrator($output);

    }

    /**
     * @param string $filename
     * @return array|string
     */
    protected function findBootstrapFile($filename)
    {
        if (null === $filename) {
            $filename = 'phpmig.php';
        }

        $cwd = getcwd();

        $locator = new FileLocator(array(
            $cwd . DIRECTORY_SEPARATOR . 'config',
            $cwd
        ));

        return $locator->locate($filename);
    }

    /**
     * @return \ArrayAccess The container
     * @throws \RuntimeException
     */
    protected function bootstrapContainer()
    {
        $bootstrapFile = $this->getBootstrap();

        $func = function () use ($bootstrapFile) {
            return require $bootstrapFile;
        };

        $container = $func();

        if (!($container instanceof \ArrayAccess)) {
            throw new \RuntimeException($bootstrapFile . " must return object of type \ArrayAccess");
        }

        return $container;
    }

    /**
     * @param  mixed $adapterName
     * @return AdapterInterface
     * @throws \RuntimeException
     */
    protected function bootstrapAdapter($adapterName)
    {
        $container = $this->getContainer();

        if (!isset($container['phpmig.adapter'])) {
            throw new \RuntimeException(
                $this->getBootstrap() . " must return container with service at phpmig.adapter"
            );
        }

        $adapter = $container['phpmig.adapter'];
        if (
            ! (is_array($adapter) and (isset($adapter[$adapterName]) and ($adapter[$adapterName] instanceof AdapterInterface)))
           and
            ! ($adapter instanceof AdapterInterface)
        ) {
            throw new \RuntimeException("phpmig.adapter must be an instance of \\Phpmig\\Adapter\\AdapterInterface");
        }

        if (is_array($adapter)) {
            $current_adapter = $adapter[$adapterName];
        } else {
            $current_adapter = $adapter;
        }

        if (!$current_adapter->hasSchema()) {
            $current_adapter->createSchema();
        }

        return $current_adapter;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    protected function bootstrapMigrations(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

        $migrationsConfigured = isset($container['phpmig.migrations']) || isset($container['phpmig.migrations_path']);
        $validMigrationFiles = !isset($container['phpmig.migrations']) || is_array($container['phpmig.migrations']);
        $validMigrationPath = !isset($container['phpmig.migrations_path']) || is_dir($container['phpmig.migrations_path']);

        if (!$migrationsConfigured || !$validMigrationFiles || !$validMigrationPath) {
            throw new \RuntimeException(
                $this->getBootstrap()
                . ' must return container with array at phpmig.migrations or migrations default path at '
                . 'phpmig.migrations_path'
            );
        }

        $migrations = array();
        if (isset($container['phpmig.migrations'])) {
            $migrations = $container['phpmig.migrations'];
        }
        if (isset($container['phpmig.migrations_path'])) {
            $migrationsPath = realpath($container['phpmig.migrations_path']);
            $migrations = array_merge($migrations, glob($migrationsPath . DIRECTORY_SEPARATOR . '*.php'));
        }
        $migrations = array_unique($migrations);

        list($adapterName,) = explode(self::ADAPTER_NAME_SEPARATOR, $input->getOption('adapter-name'));
        $versions = array();
        $names = array();
        foreach ($migrations as $path) {
            if (!preg_match('/^([0-9]+)_(([a-zA-Z0-9]+)_)?/', basename($path), $matches)) {
                throw new \InvalidArgumentException(sprintf('The file "%s" does not have a valid migration filename', $path));
            }

            $version = $matches[1];
            $guessAdapter = '';
            if (isset($matches[3])) {
                $guessAdapter = $matches[3];
            }

            if ($adapterName != $guessAdapter) {
                continue;
            }

            if (isset($versions[$version])) {
                throw new \InvalidArgumentException(sprintf('Duplicate migration, "%s" has the same version as "%s"', $path, $versions[$version]->getName()));
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
            $migration->setAdapter($this->getAdapter()); // inject adapter

            $versions[$version] = $migration;
        }

        ksort($versions);

        return $versions;
    }

    /**
     * @param OutputInterface $output
     * @return mixed
     */
    protected function bootstrapMigrator(OutputInterface $output)
    {
        return new Migrator($this->getAdapter(), $this->getContainer(), $output);
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
    public function setAdapter($adapter)
    {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * Get adapter
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
    protected function migrationToClassName($migrationName)
    {
        $class = str_replace('_', ' ', $migrationName);
        $class = ucwords($class);
        return str_replace(' ', '', $class);
    }
}


