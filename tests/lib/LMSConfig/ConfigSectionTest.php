<?php

namespace LMS\Tests;

class ConfigSectionTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var ConfigSection
     */
    protected $object;

    /**
     * @var string
     */
    protected $section_name;


    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->section_name = 'some_section';
        $this->object = new \ConfigSection($this->section_name);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @covers ConfigSection::addVariable
     */
    public function testAddVariable()
    {
        $variable_name = 'some_name';
        $variable_value = 'some_value';
        $variable_comment = 'some_comment';
        $variable = new \ConfigVariable($variable_name, $variable_value, $variable_comment);
        
        $this->addSomeVariablesToSection($variable, 1, false);
        
        $returning_variable = $this->object->getVariable($variable_name);
        
        $this->assertInstanceOf('ConfigVariable', $returning_variable);
        
        $this->assertEquals($variable->getVariable(), $returning_variable->getVariable());
        $this->assertEquals($variable->getValue(), $returning_variable->getValue());
        $this->assertEquals($variable->getComment(), $returning_variable->getComment());
    }

    /**
     * @covers ConfigSection::addVariables
     */
    public function testAddVariables()
    {
        $variable_name = 'some_name';
        $variable_value = 'some_value';
        $variable_comment = 'some_comment';
        $variable = new \ConfigVariable($variable_name, $variable_value, $variable_comment);
        
        $amount = 3;
        
        $mutable = true;
        
        $this->addSomeVariablesToSection($variable, $amount, $mutable);
        
        $returning_variables = $this->object->getVariables();
        
        $this->assertCount($amount, $returning_variables);
    }
    
    /**
     * @covers ConfigSection::addVariables
     */
    public function testAddVariablesOverridesExistingVariables()
    {
        $variable_name = 'some_name';
        $variable_value = 'some_value';
        $variable_comment = 'some_comment';
        $variable = new \ConfigVariable($variable_name, $variable_value, $variable_comment);
        
        $amount = 3;
        
        $mutable = false;
        
        $this->addSomeVariablesToSection($variable, $amount, $mutable);
        
        $returning_variables = $this->object->getVariables();
        
        $this->assertCount(1, $returning_variables);
    }

    /**
     * @covers ConfigSection::getVariable
     */
    public function testGetVariable()
    {
        $variable_name = 'some_name';
        $variable_value = 'some_value';
        $variable_comment = 'some_comment';
        $variable = new \ConfigVariable($variable_name, $variable_value, $variable_comment);
        
        $this->addSomeVariablesToSection($variable, 1, false);
        
        $returning_variable = $this->object->getVariable($variable_name);
        
        $this->assertInstanceOf('ConfigVariable', $returning_variable);
        
        $this->assertEquals($variable->getVariable(), $returning_variable->getVariable());
        $this->assertEquals($variable->getValue(), $returning_variable->getValue());
        $this->assertEquals($variable->getComment(), $returning_variable->getComment());
    }

    /**
     * @covers ConfigSection::getVariables
     */
    public function testGetVariables()
    {
        $variable_name = 'some_name';
        $variable_value = 'some_value';
        $variable_comment = 'some_comment';
        $variable = new \ConfigVariable($variable_name, $variable_value, $variable_comment);
        
        $amount = 3;
        
        $this->addSomeVariablesToSection($variable, $amount, true);
        
        $variables = $this->object->getVariables();
        
        $this->assertCount($amount, $variables);
        
        foreach ($variables as $variable) {
            $this->assertInstanceOf('ConfigVariable', $variable);
        }
    }
    
    /**
     * @covers ConfigSection::getVariables
     */
    public function testGetVariablesReturnsEmptyArrayWhenNoVariableWasAddedToSection()
    {
        $variables = $this->object->getVariables();
        
        $this->assertEquals(array(), $variables);
    }
    
    /**
     * @expectedException Exception
     * @covers ConfigSection::getVariable
     */
    public function testGetVariableThrowsExceptionWhenVariableIsNotInSection()
    {
        $this->object->getVariable('non-existent');
    }
    
    public function testHasVariable()
    {
        $variable_name = 'non-existent';
        
        $this->assertFalse($this->object->hasVariable($variable_name));
        
        $variable = new \ConfigVariable($variable_name, 'some_value', 'some_comment');
        
        $this->object->addVariable($variable);
        
        $this->assertTrue($this->object->hasVariable($variable_name));
    }
    
    private function addSomeVariablesToSection(\ConfigVariable $variable, $amount = 0, $mutable = false)
    {
        for ($i = 0; $i < $amount; $i++) {
            if ($mutable) {
                $name = $variable->getVariable() . '_' . $i;
                $variable = new \ConfigVariable($name, $variable->getValue(), $variable->getComment());
            }
            $this->object->addVariable($variable);
        }
    }
}
