<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2013 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

/**
 * ConfigContainer
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class ConfigContainer
{
    /**
     * @var ConfigSection[] Config sections
     */
    private $config_sections;
    
    /**
     * Constructs config sections container
     */
    public function __construct()
    {
        $this->config_sections = array();
    }
    
    /**
     * Adds config section to sections container
     *
     * @param ConfigSection $section Config sections to add
     */
    public function addSection(ConfigSection $section)
    {
        $this->config_sections[$section->getSectionName()] = $section;
    }
    
    /**
     * Adds multiple config sections to sections container
     *
     * @param ConfigSection[] $sections Config sections to add
     */
    public function addSections(array $sections)
    {
        foreach ($sections as $section) {
            $this->addSection($section);
        }
    }
    
    /**
     * Returns config section
     *
     * @param string $section_name
     * @return ConfigSection
     * @throws Exception Throws exception when section is unknown
     */
    public function getSection($section_name)
    {
        if (isset($this->config_sections[$section_name])) {
            return $this->config_sections[$section_name];
        } else {
            throw new Exception('Unknown config section!');
        }
    }
    
    /**
     * Return config sections
     *
     * @return ConfigSection[]
     */
    public function getSections()
    {
        return $this->config_sections;
    }
    
    /**
     * Checks if section exists
     *
     * @param string $section_name
     * @return boolean
     */
    public function hasSection($section_name)
    {
        return isset($this->config_sections[$section_name]);
    }
}
