<?php

namespace LMS\Tests;

class ConfigContainerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var ConfigContainer
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new \ConfigContainer;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @covers ConfigContainer::addSection
     * @todo   Implement testAddSection().
     */
    public function testAddSection()
    {
        $section_name = 'some_name';
        $section = new \ConfigSection($section_name);
        
        $this->addSomeSectionsToConfig($section, 1, false);
        
        $returning_section = $this->object->getSection($section_name);
        
        $this->assertInstanceOf('ConfigSection', $returning_section);
    }

    /**
     * @covers ConfigContainer::addSections
     */
    public function testAddSections()
    {
        $section_name = 'some_name';
        $section = new \ConfigSection($section_name);
        
        $amount = 3;
        
        $this->addSomeSectionsToConfig($section, $amount, true);
        
        $sections = $this->object->getSections();
        
        $this->assertCount($amount, $sections);
    }
    
    /**
     * @covers ConfigContainer::addSections
     */
    public function testAddSectionsOverridesExistingSections()
    {
        $section_name = 'some_name';
        $section = new \ConfigSection($section_name);
        
        $amount = 3;
        
        $this->addSomeSectionsToConfig($section, $amount, false);
        
        $sections = $this->object->getSections();
        
        $this->assertCount(1, $sections);
    }

    /**
     * @covers ConfigContainer::getSection
     * @todo   Implement testGetSection().
     */
    public function testGetSection()
    {
        $section_name = 'some_name';
        $section = new \ConfigSection($section_name);
        
        $this->addSomeSectionsToConfig($section, 1, false);
        
        $returning_section = $this->object->getSection($section_name);
        
        $this->assertInstanceOf('ConfigSection', $returning_section);
    }

    /**
     * @covers ConfigContainer::getSections
     * @todo   Implement testGetSections().
     */
    public function testGetSections()
    {
        $section_name = 'some_name';
        $section = new \ConfigSection($section_name);
        
        $amount = 3;
        
        $this->addSomeSectionsToConfig($section, $amount, true);
        
        $sections = $this->object->getSections();
        
        $this->assertCount($amount, $sections);
        
        foreach ($sections as $section) {
            $this->assertInstanceOf('ConfigSection', $section);
        }
    }
    
    /**
     * @covers ConfigContainer::getSections
     */
    public function testGetSectionsReturnsEmptyArrayWhenNoSectionWasAddedToConfig()
    {
        $sections = $this->object->getSections();
        
        $this->assertEquals(array(), $sections);
    }
    
    /**
     * @expectedException Exception
     * @covers ConfigContainer::getSection
     */
    public function testGetSectionThrowsExceptionWhenSectionIsNotInConfig()
    {
        $this->object->getSection('non-existent');
    }

    /**
     * @covers ConfigContainer::hasSection
     */
    public function testHasSection()
    {
        $section_name = 'non-existent';
        
        $this->assertFalse($this->object->hasSection($section_name));
        
        $section = new \ConfigSection($section_name);
        
        $this->object->addSection($section);
        
        $this->assertTrue($this->object->hasSection($section_name));
    }

    private function addSomeSectionsToConfig(\ConfigSection $section, $amount = 0, $mutable = false)
    {
        for ($i = 0; $i < $amount; $i++) {
            if ($mutable) {
                $name = $section->getSectionName() . '_' . $i;
                $section = new \ConfigSection($name);
            }
            $this->object->addSection($section);
        }
    }
}
