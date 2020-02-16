<?php
/**
 * @package    Phpmig
 * @subpackage Phpmig\Console
 */
namespace Phpmig\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
 * Init command
 *
 * @author      Dave Marshall <david.marshall@bskyb.com>
 */
class InitCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addOption('--directory', '-d', InputArgument::OPTIONAL, 'The directory to create the initialisation in.');
        $this->setName('init')
             ->setDescription('Initialise this directory for use with phpmig')
             ->setHelp(<<<EOT
The <info>init</info> command creates a skeleton bootstrap file, a propertyfile file and a migrations directory

<info>phpmig init</info>

EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cwd = getcwd();
        $directory = $input->getOption('directory');
        if (null === $directory) {
            $directory = $cwd;
        }

        $bootstrap = $directory . DIRECTORY_SEPARATOR . 'phpmig.php';
        $propertyfile = $directory . DIRECTORY_SEPARATOR . 'build.properties';
        $relative = 'migrations';
        $migrations = $directory . DIRECTORY_SEPARATOR . $relative;

        $this->initMigrationsDir($migrations, $output);
        $this->initBootstrap($bootstrap, $directory, $output);
        $this->initPropertiesFile($propertyfile, $directory, $output);
    }

    /**
     * Create migrations dir
     *
     * @param $path
     * @return void
     */
    protected function initMigrationsDir($migrations, OutputInterface $output)
    {
        if (file_exists($migrations) && is_dir($migrations)) {
            $output->writeln(
                '<info>--</info> ' .
                str_replace(getcwd(), '.', $migrations) . ' already exists -' .
                ' <comment>Place your migration files in here</comment>'
            );
            return;
        }

        if (false === mkdir($migrations, 0777, true)) {
            throw new \RuntimeException(sprintf('Could not create directory "%s"', $migrations));
        }

        $output->writeln(
            '<info>+d</info> ' .
            str_replace(getcwd(), '.', $migrations) .
            ' <comment>Place your migration files in here</comment>'
        );
    }

    /**
     * Create bootstrap
     *
     * @param string $bootstrap where to put bootstrap file
     * @param string $migrations path to migrations dir relative to bootstrap
     * @return void
     */
    protected function initBootstrap($bootstrap, $migrations, OutputInterface $output)
    {
        if (file_exists($bootstrap)) {
            $output->writeln(
                '<info>--</info> ' .
                str_replace(getcwd(), '.', $bootstrap) . ' already exists -' .
                ' <comment>Create services in here</comment>'
            );
            return;
        }

        if (!is_writeable(dirname($bootstrap))) {
            throw new \RuntimeException(sprintf('The file "%s" is not writeable', $bootstrap));
        }

        $contents = <<<PHP
<?php

define('TRACK_MIGRATIONS_IN_DB', true);

use \Phpmig\Utility,
    \Phpmig\Adapter,
    \Pimple;

\$container = new Pimple();


if (TRACK_MIGRATIONS_IN_DB) {

    \$container['db'] = \$container->share(function() use (\$container) {
        \$p = \$container['properties'];
        \$dbh = new PDO(sprintf('pgsql:dbname=%s;host=%s;password=%s', \$p['db.name'], \$p['db.host'], \$p['db.password']), \$p['db.user'], '');
        \$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return \$dbh;
    });

    \$container['phpmig.adapter'] = \$container->share(function() use (\$container) {
        return new Adapter\PDO\Sql(\$container['db'], 'migrations');
    });

    //\$container['phpmig.adapter'] = \$container->share(function() use (\$container) {
    //    \$p = \$container['properties'];
    //    return new Adapter\PDO\SqlPgsql(\$container['db'], 'migrations', \$p['db.migration.schema']);
    //});


} else {

    \$container['phpmig.adapter'] = \$container->share(function () use (\$container) {
        \$p = \$container['properties'];
        return new Adapter\File\Flat(\$p['flatfile.migration.logfile']);
    });
}

// \$container['phpmig.migrations_path'] = __DIR__ . DIRECTORY_SEPARATOR . 'migrations';

// You can also provide an array of migration files
\$container['phpmig.migrations'] = function () use (\$container) {
    \$p = \$container['properties'];
    return array_merge(
        glob(\$p['migration.app.folder'].'/*.php'),
        glob(\$p['migration.site.folder'].'/*.php')
    );
};

return \$container;

PHP;

        if (false === file_put_contents($bootstrap, $contents)) {
            throw new \RuntimeException('The file "%s" could not be written to', $bootstrap);
        }

        $output->writeln(
            '<info>+f</info> ' .
            str_replace(getcwd(), '.', $bootstrap) .
            ' <comment>Create services in here</comment>'
        );
    }

    /**
     * Create propertyfile
     *
     * @param string $propertyfile where to put propertyfile file
     * @param string $migrations path to migrations dir relative to propertyfile
     * @return void
     */
    protected function initPropertiesFile($propertyfile, $migrations, OutputInterface $output)
    {
        if (file_exists($propertyfile)) {
            throw new \RuntimeException(sprintf('The file "%s" already exists', $propertyfile));
        }

        if (!is_writeable(dirname($propertyfile))) {
            throw new \RuntimeException(sprintf('THe file "%s" is not writeable', $propertyfile));
        }

        $contents = <<<PHP
db.user=username
db.name=your_database_name
db.host=localhost
db.password=password
db.migration.schema=migrations_schema

flatfile.migration.logfile=migrations/migrations.log

migration.app.folder=migrations
migration.site.folder=migrations.site
PHP;

        if (false === file_put_contents($propertyfile, $contents)) {
            throw new \RuntimeException('The file "%s" could not be written to', $propertyfile);
        }

        $output->writeln(
            '<info>+f</info> ' .
            str_replace(getcwd(), '.', $propertyfile) .
            ' <comment>Specify the properties here</comment>'
        );
        return;
    }
}



