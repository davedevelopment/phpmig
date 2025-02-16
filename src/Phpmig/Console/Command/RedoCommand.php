<?php
/**
 * @package    Phpmig
 * @subpackage Phpmig\Console
 */
namespace Phpmig\Console\Command;

use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Output\OutputInterface;

/**
 * This file is part of phpmig
 *
 * Copyright (c) 2011 Dave Marshall <dave.marshall@atstsolutuions.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Redo command
 *
 * @author      nagodon <nagodon@gmail.com>
 */
class RedoCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('redo')
             ->addArgument('version', InputArgument::REQUIRED, 'The version number for the migration')
             ->setDescription('Redo a specific migration')
             ->setHelp(<<<EOT
The <info>redo</info> command redo a specific migration

<info>phpmig redo 20111018185412</info>

EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->bootstrap($input, $output);

        $migrations = $this->getMigrations();
        $versions   = $this->getAdapter()->fetchAll();

        $version = $input->getArgument('version');

        if (!in_array($version, $versions)) {
            return 0;
        }

        if (!isset($migrations[$version])) {
            return 0;
        }

        $container = $this->getContainer();
        $container['phpmig.migrator']->down($migrations[$version]);
        $container['phpmig.migrator']->up($migrations[$version]);

        return 0;
    }
}



