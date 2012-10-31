<?php

namespace Phpmig\Test\Migration;

use Mockery as m;
use Phpmig\Migration\Migration;

class MigrationTest extends \PHPUnit_Framework_Testcase
{
    public function setup()
    {
        $this->output = m::mock("Symfony\Component\Console\Output\OutputInterface")->shouldIgnoreMissing();
        $this->dialogHelper = m::mock("Symfony\Component\Console\Helper\DialogHelper")->shouldIgnoreMissing();
        $this->object = new Migration(1);
        $this->object->setOutput($this->output);
        $this->object->setDialogHelper($this->dialogHelper);
    } 


    /**
     * @test
     */
    public function shouldAskForInput()
    {
        $this->dialogHelper->shouldReceive("ask")
            ->with($this->output, $question = "Wat?", $default = "huh?")
            ->once();

        $this->object->ask($question, $default);
    }

    /**
     * @test
     */
    public function shouldAskForConfirmation()
    {
        $this->dialogHelper->shouldReceive("askConfirmation")
            ->with($this->output, $question = "Wat?", $default = true)
            ->once();

        $this->object->confirm($question, $default);
    }
    
    /**
     * @test
     */
    public function shouldAskForHiddenResponse()
    {
        $this->dialogHelper->shouldReceive("askHiddenResponse")
            ->with($this->output, $question = "Wat?", $default = true)
            ->once();

        $this->object->askForHiddenResponse($question, $default);
    }
}
