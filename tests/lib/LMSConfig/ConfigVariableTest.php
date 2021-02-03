<?php

namespace LMS\Tests;

class ConfigVariableTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var ConfigVariable
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @covers ConfigVariable::getVariable
     * @todo   Implement testGetVariable().
     */
    public function testGetVariable()
    {
        $variable_name = 'some_variable';
        $variable_value = 'some_value';
        $variable_comment = 'some_comment';
        
        $config_variable = new \ConfigVariable($variable_name, $variable_value, $variable_comment);
        
        $this->assertEquals($config_variable->getVariable(), $variable_name);
    }

    /**
     * @covers ConfigVariable::getValue
     * @todo   Implement testGetValue().
     */
    public function testGetValue()
    {
        $variable_name = 'some_variable';
        $variable_value = 'some_value';
        $variable_comment = 'some_comment';
        
        $config_variable = new \ConfigVariable($variable_name, $variable_value, $variable_comment);
        
        $this->assertEquals($config_variable->getValue(), $variable_value);
    }

    /**
     * @covers ConfigVariable::getComment
     * @todo   Implement testGetComment().
     */
    public function testGetComment()
    {
        $variable_name = 'some_variable';
        $variable_value = 'some_value';
        $variable_comment = 'some_comment';
        
        $config_variable = new \ConfigVariable($variable_name, $variable_value, $variable_comment);
        
        $this->assertEquals($config_variable->getComment(), $variable_comment);
    }
}
