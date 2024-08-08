<?php

namespace Phpmig\Migration;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;

class MigrationTest extends TestCase
{
    /**
     * @var OutputInterface|m\MockInterface
     */
    private $migrationOutput;

    /**
     * @var InputInterface|m\MockInterface
     */
    private $migrationInput;

    /**
     * @var QuestionHelper|m\MockInterface
     */
    private $migrationDialogHelper;

    /**
     * @var Migration
     */
    private $migration;

    public function setUp(): void
    {
        $this->migrationOutput = m::mock('Symfony\Component\Console\Output\OutputInterface')->shouldIgnoreMissing();
        $this->migrationInput = m::mock('Symfony\Component\Console\Input\InputInterface')->shouldIgnoreMissing();
        $this->migrationDialogHelper = m::mock('Symfony\Component\Console\Helper\QuestionHelper')->shouldIgnoreMissing();

        $this->migration = new Migration(1);
        $this->migration->setOutput($this->migrationOutput);
        $this->migration->setInput($this->migrationInput);
        $this->migration->setDialogHelper($this->migrationDialogHelper);
    }

    /**
     * @test
     */
    public function shouldAskForInput()
    {
        $this->migrationDialogHelper->shouldReceive('ask')
            ->with($this->migrationInput, $this->migrationOutput, $question = new Question('Wat?','huh?'))
            ->andReturn($ans = 'dave')
            ->once();

        $this->assertEquals($ans, $this->migration->ask($question));
    }

    /**
     * @test
     */
    public function shouldAskForConfirmation()
    {
        $this->migrationDialogHelper->shouldReceive('ask')
            ->with($this->migrationInput, $this->migrationOutput, $question = new Question('Wat?',true))
            ->andReturn($ans = 'dave')
            ->once();

        $this->assertEquals($ans, $this->migration->confirm($question));
    }

    /**
     * @test
     */
    public function shouldRetrieveServices()
    {
        $this->migration->setContainer(new \ArrayObject(array('service' => 123)));
        $this->assertEquals(123, $this->migration->get('service'));
    }

    public function testMigrationVersion()
    {
        $this->assertEquals(1, $this->migration->getVersion());
    }

}
