<?php
/**
 * @package    Phpmig
 * @subpackage Phpmig\Console
 */
namespace Phpmig\Console\Command;

use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Config\FileLocator;

/**
 * This file is part of phpmig
 *
 * Copyright (c) 2011 Dave Marshall <dave.marshall@atstsolutuions.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Generate command
 *
 * @author      Dave Marshall <david.marshall@atstsolutions.co.uk>
 */
class GenerateCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('generate')
             ->addArgument('name', InputArgument::REQUIRED, 'The name for the migration')
             ->addArgument('path', InputArgument::REQUIRED, 'The directory in which to put the migration')
             ->setDescription('Generate a new migration')
             ->setHelp(<<<EOT
The <info>generate</info> command creates a new migration with the name and path specified 

<info>phpmig generate Dave ./migrations</info>

EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path    = $input->getArgument('path');
        $locator = new FileLocator(array());
        $path    = $locator->locate($path, getcwd(), $first = true);

        if (!is_writeable($path)) {
            throw new \InvalidArgumentException(sprintf(
                'The directory "%s" is not writeable',
                $path
            ));
        }

        $path = realpath($path);

        $className = $input->getArgument('name');
        $basename  = date('YmdHis') . '_' . $className . '.php';

        $path = $path . DIRECTORY_SEPARATOR . $basename;

        if (file_exists($path)) {
            throw new \InvalidArgumentException(sprintf(
                'The file "%s" already exists',
                $path
            ));
        }

        $contents = <<<PHP
<?php

use Phpmig\Migration\Migration;

class $className extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {

    }

    /**
     * Undo the migration
     */
    public function down()
    {

    }
}

PHP;
        
        if (false === file_put_contents($path, $contents)) {
            throw new \RuntimeException(sprintf(
                'The file "%s" could not be written to',
                $path
            ));
        }

        $output->writeln(
            '<info>+f</info> ' .
            '.' . str_replace(getcwd(), '', $path)
        );

        return;
    }
}



