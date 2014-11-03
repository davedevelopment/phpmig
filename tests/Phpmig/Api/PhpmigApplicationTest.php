<?php

namespace Phpmig\Api;

use Phpmig\Api\PhpmigApplication;
use Symfony\Component\Console\Output;

/**
 * @group unit
 */
class PhpmigApplicationTest extends \PHPUnit_Framework_TestCase
{
    protected $object;
    
    public function setup()
    {
        $this->container = array(
            'phpmig.adapter' => new \stdClass(), // mock adapter
            'phpmig.migrations' => "",
            'phpmig.migrations_path' => ""
        );
        
        $this->output = new Output\NullOutput();
        #
        # TODO:
        #
        //$this->object = new PhpmigApplication($this->container, $this->output);
    }
    
    public function test__construct()
    {
        #
        # TODO:
        #
        $this->markTestIncomplete('TODO');
        //$this->assertInstanceOf("PhpmigApplication", new PhpmigApplication($this->container, $this->output));
    }
    
    public function testUp()
    {
        #
        # TODO:
        #
        $this->markTestIncomplete('TODO');
    }
    
    public function testDown()
    {
        #
        # TODO:
        #
        $this->markTestIncomplete('TODO');
    }
    
    public function testGetMigrations()
    {
        #
        # TODO:
        #
        $this->markTestIncomplete('TODO');
    }
    
    public function testGetVersion()
    {
        #
        # TODO:
        #
        $this->markTestIncomplete('TODO');
    }
}