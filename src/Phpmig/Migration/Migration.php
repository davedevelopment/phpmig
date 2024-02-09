<?php
/**
 * @package    Phpmig
 * @subpackage Phpmig\Migration
 */
namespace Phpmig\Migration;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;

/**
 * This file is part of phpmig
 *
 * Copyright (c) 2011 Dave Marshall <dave.marshall@atstsolutuions.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Migration
 *
 * A migration describes the changes that should be made (or unmade)
 *
 * @author      Dave Marshall <david.marshall@atstsolutions.co.uk>
 */
class Migration
{
    /**
     * @var int
     */
    protected $version = null;

    /**
     * @var \ArrayAccess
     */
    protected $container = null;

    /**
     * @var OutputInterface
     */
    protected $output = null;

    /**
     * @var InputInterface
     */
    protected $input = null;

    /**
     * @var QuestionHelper
     */
    protected $dialogHelper = null;

    /**
     * Constructor
     *
     * @param int $version
     */
    final public function __construct($version)
    {
        $this->version = $version;
    }

    /**
     * init
     *
     * @return void
     */
    public function init()
    {
        return;
    }

    /**
     * Do the migration
     *
     * @return void
     */
    public function up()
    {
        return;
    }

    /**
     * Undo the migration
     *
     * @return void
     */
    public function down()
    {
        return;
    }

    /**
     * Get Version
     *
     * @return int
     */
    public function getVersion(): ?int
    {
        return $this->version;
    }

    /**
     * Set version
     *
     * @param int $version
     * @return Migration
     */
    public function setVersion(int $version): Migration
    {
        $this->version = $version;
        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName(): string
    {
        return get_class($this);
    }

    /**
     * Get Container
     *
     * @return \ArrayAccess
     */
    public function getContainer(): ?\ArrayAccess
    {
        return $this->container;
    }

    /**
     * Set Container
     *
     * @param \ArrayAccess $container
     * @return Migration
     */
    public function setContainer(\ArrayAccess $container): Migration
    {
        $this->container = $container;
        return $this;
    }

    /**
     * Get Output
     *
     * @return OutputInterface
     */
    public function getOutput(): ?OutputInterface
    {
        return $this->output;
    }

    /**
     * Get Output
     *
     * @return InputInterface
     */
    public function getInput(): ?InputInterface
    {
        return $this->input;
    }

    /**
     * Set Output
     *
     * @param OutputInterface $output
     * @return Migration
     */
    public function setOutput(OutputInterface $output): Migration
    {
        $this->output = $output;
        return $this;
    }

    /**
     * Set Output
     *
     * @param InputInterface $output
     * @return Migration
     */
    public function setInput(InputInterface $input): Migration
    {
        $this->input = $input;
        return $this;
    }

    /**
     * Ask for input
     *
     * @param Question $question
     * @return string The users answer
     */
    public function ask(Question $question): string
    {
        return $this->getDialogHelper()->ask($this->getInput(), $this->getOutput(), $question);
    }

    /**
     * Ask for confirmation
     *
     * @param Question $question
     * @return string The users answer
     */
    public function confirm(Question $question): string
    {
        return $this->getDialogHelper()->ask($this->getInput(), $this->getOutput(), $question);
    }

    /**
     * Get something from the container
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        $c = $this->getContainer();
        return $c[$key];
    }

    /**
     * Get Dialog Helper
     *
     * @return QuestionHelper
     */
    public function getDialogHelper(): ?QuestionHelper
    {
        if ($this->dialogHelper) {
            return $this->dialogHelper;
        }

        return $this->dialogHelper = new QuestionHelper();
    }

    /**
     * Set Dialog Helper
     *
     * @param QuestionHelper $dialogHelper
     * @return Migration
     */
    public function setDialogHelper(QuestionHelper $dialogHelper): Migration
    {
        $this->dialogHelper = $dialogHelper;
        return $this;
    }
}



