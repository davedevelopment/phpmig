<?php

namespace Phpmig\Api;

use Phpmig\Api\PhpmigApplication;
use Symfony\Component\Console\Output;

/**
 * @group unit
 */
class PhpmigApplicationTest extends \PHPUnit_Framework_TestCase
{
    private $app;
    private $prev_version = '20141104210000';
    private $current_version = '20141104220000';
    private $next_version = '20141104230000';
    private $output;
    
    public function setup()
    {
        $this->output = new Output\NullOutput();
        
        $this->app = new PhpmigApplication(
            $this->getContainer(
                $this->getAdapter(array($this->current_version)),
                $this->getMigrations(),
                '**.php'
            ),
            $this->output
        );
    }
    
    protected function getAdapter(array $versions = array())
    {
        $adapter = $this->getMock('Phpmig\Adapter\AdapterInterface');
        $adapter->expects($this->any())
            ->method('fetchAll')
            ->will($this->returnValue($versions));
        return $adapter;
    }
    
    protected function getContainer($adapter, $migrations, $migrations_path)
    {
        return new \ArrayObject(array(
            'phpmig.adapter' => $adapter,
            'phpmig.migrations' => $migrations,
            'phpmig.migrations_path' => $migrations_path
        ));
    }
    
    protected function getMigrations()
    {
        return array(
            $this->prev_version . "_TestOne.php",
            $this->current_version . "_TestTwo.php",
            $this->next_version . "_TestThree.php"
        );
    }
    
    public function test__construct()
    {
        $this->assertInstanceOf("Phpmig\Api\PhpmigApplication", $this->app);
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
        //$this->assertCount(2, $this->object->getMigrations($this->prev_version, $this->next_version));
    }
    
    public function testGetVersion()
    {
        $this->assertEquals($this->current_version, $this->app->getVersion());
        
        $app = new PhpmigApplication(
            $this->getContainer(
                $this->getAdapter(),
                array(),
                '**.php'
            ),
            $this->output
        );
        
        $this->assertEquals(0, $app->getVersion());
    }
}