<?php
/**
 * @package    Phpmig
 * @subpackage Phpmig\Adapter
 */
namespace Phpmig\Adapter\File;

use \Phpmig\Adapter\AdapterInterface,
    \Phpmig\Migration\Migration;

/**
 * This file is part of phpmig
 *
 * Copyright (c) 2011 Dave Marshall <dave.marshall@atstsolutuions.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Flat file adapter
 *
 * @author      Dave Marshall <david.marshall@atstsolutions.co.uk
 */
class Flat implements AdapterInterface
{
    /**
     * @var string
     */
    protected $filename;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll()
    {
        $versions = file($this->filename, FILE_IGNORE_NEW_LINES);
        sort($versions);
        return $versions;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Migration $migration)
    {
        $versions = $this->fetchAll();
        if (in_array($migration->getVersion(), $versions)) {
            return $this;
        }

        $versions[] = $migration->getVersion();
        $this->write($versions);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function down(Migration $migration)
    {
        $versions = $this->fetchAll();
        if (!in_array($migration->getVersion(), $versions)) {
            return $this;
        }

        unset($versions[array_search($migration->getVersion(), $versions)]);
        $this->write($versions);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasSchema()
    {
        return file_exists($this->filename);
    }

    /**
     * {@inheritdoc}
     */
    public function createSchema()
    {
        if (!is_writable(dirname($this->filename))) {
            throw new \InvalidArgumentException(sprintf('The file "%s" is not writeable', $this->filename));
        }

        if (false === touch($this->filename)) {
            throw new \InvalidArgumentException(sprintf('The file "%s" could not be written to', $this->filename));
        }

        return $this;
    }

    /**
     * Write to file
     *
     * @param array $versions
     */
    protected function write(array $versions)
    {
        if (false === file_put_contents($this->filename, implode("\n", $versions))) {
            throw new \RuntimeException(sprintf('The file "%s" could not be written to', $this->filename));
        }
    }
}



