<?php

namespace LMS\Tests;

use PHPUnit\Framework\TestCase;

class ConfigContainerTest extends TestCase
{

    /**
     * @var ConfigContainer
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() : void
    {
        $this->object = new \ConfigContainer;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() : void
    {
    }

    /**
     * @covers ConfigContainer::addSection
     * @todo   Implement testAddSection().
     */
    public function testAddSection() : void
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
    public function testAddSections() : void
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
    public function testAddSectionsOverridesExistingSections() : void
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
    public function testGetSection() : void
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
    public function testGetSections() : void
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
    public function testGetSectionsReturnsEmptyArrayWhenNoSectionWasAddedToConfig() : void
    {
        $sections = $this->object->getSections();
        
        $this->assertEquals(array(), $sections);
    }
    
    /**
     * @expectedException Exception
     * @covers ConfigContainer::getSection
     */
    public function testGetSectionThrowsExceptionWhenSectionIsNotInConfig() : void
    {
        $this->object->getSection('non-existent');
    }

    /**
     * @covers ConfigContainer::hasSection
     */
    public function testHasSection() : void
    {
        $section_name = 'non-existent';
        
        $this->assertFalse($this->object->hasSection($section_name));
        
        $section = new \ConfigSection($section_name);
        
        $this->object->addSection($section);
        
        $this->assertTrue($this->object->hasSection($section_name));
    }

    private function addSomeSectionsToConfig(\ConfigSection $section, $amount = 0, $mutable = false) : void
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
