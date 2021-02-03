<?php

namespace LMS\Tests;

class LMSDBTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var LMSDB
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        if (!defined('CONFIG_FILE')) {
            define('CONFIG_FILE', '../lms.ini');
        }
        if (!is_readable(CONFIG_FILE)) {
            $this->markTestSkipped('lms.ini is not readable!');
        }
        if (!extension_loaded('mysqli')) {
            $this->markTestSkipped('mysqli extension is not loaded!');
        }
        if (!extension_loaded('mysql')) {
            $this->markTestSkipped('mysql extension is not loaded!');
        }
        if (!extension_loaded('pgsql')) {
            $this->markTestSkipped('pgsql extension is not loaded!');
        }
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        \LMSDB::destroyInstance();
    }

    /**
     * @covers LMSDB::getInstance
     */
    public function testGetInstance()
    {
        $this->assertInstanceOf('LMSDBInterface', \LMSDB::getInstance());
    }

    /**
     * @covers LMSDB::getDB
     */
    public function testGetDB()
    {
        $this->assertInstanceOf('LMSDBInterface', \LMSDB::getInstance());
    }

    /**
     * @covers LMSDB::destroyInstance
     */
    public function testDestroyInstance()
    {
        \LMSDB::getInstance();
        $db = \LMSDB::destroyInstance();
        $this->assertEquals($db, null);
    }
}
