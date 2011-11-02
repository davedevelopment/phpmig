<?php
/**
 * @package    Phpmig
 * @subpackage Console
 */
namespace Phpmig\Console;

use Symfony\Component\Console\Application,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Yaml\Yaml,
    Symfony\Component\Config\FileLocator,
    Phpmig\Console\Command;

/**
 * This file is part of phpmig
 *
 * Copyright (c) 2011 Dave Marshall <dave.marshall@atstsolutuions.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * The main phpmig application
 *
 * @author      Dave Marshall <david.marshall@bskyb.com>
 */
class PhpmigApplication extends Application
{
    /**
     * @var \ArrayAccess
     */
    protected $container = null;

    /**
     * Constructor
     */
    public function __construct($version = 'dev') {
        parent::__construct('phpmig', $version);

        $this->addCommands(array(
            new Command\InitCommand(),
            new Command\StatusCommand(),
            new Command\GenerateCommand(),
            new Command\UpCommand(),
            new Command\DownCommand(),
            new Command\MigrateCommand(),
            new Command\RollbackCommand(),
        ));
    }

    /**
     * Runs the current application.
     *
     * Lots of logic in here that needs abstracting out.
     *
     * @param InputInterface  $input  An Input instance
     * @param OutputInterface $output An Output instance
     *
     * @return integer 0 if everything went fine, or an error code
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        return parent::doRun($input, $output);
    }

}

