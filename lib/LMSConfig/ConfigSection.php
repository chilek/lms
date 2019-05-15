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
 * ConfigSection
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class ConfigSection
{
    /**
     * @var string Section name
     */
    private $section_name;
    
    /**
     * @var ConfigVariable[] Section variables
     */
    private $section_variables;
    
    /**
     * Constructs config section variables container
     *
     * @param string $section_name Section name
     */
    public function __construct($section_name)
    {
        $this->section_name = $section_name;
        $this->section_variables = array();
    }
    
    /**
     * Returns section name
     *
     * @return string
     */
    public function getSectionName()
    {
        return $this->section_name;
    }
    
    /**
     * Appends config variable to section variables container
     *
     * @param ConfigVariable $config_variable Config variable
     */
    public function addVariable(ConfigVariable $config_variable)
    {
        $this->section_variables[$config_variable->getVariable()] = $config_variable;
    }
    
    /**
     * Appends multiple config variables to section variables container
     *
     * @param ConfigVariable[] $config_variables Array of config variables
     */
    public function addVariables(array $config_variables)
    {
        if ($config_variables !== array()) {
            foreach ($config_variables as $config_variable) {
                $this->addVariable($config_variable);
            }
        }
    }
    
    /**
     * Returns config variable if in section
     *
     * @param string $variable_name Config variable name
     * @return ConfigVariable Config variable
     * @throws Exception Throws exception when variable is unknown
     */
    public function getVariable($variable_name)
    {
        if (isset($this->section_variables[$variable_name])) {
            return $this->section_variables[$variable_name];
        } else {
            throw new Exception('Unknown config variable "'.$variable_name.'" in section "'.$this->section_name.'"!');
        }
    }
    
    /**
     * Returns all config variables in section
     *
     * @return ConfigVariable[]
     */
    public function getVariables()
    {
        return $this->section_variables;
    }
    
    /**
     * Checks if variable exists
     *
     * @param string $variable_name
     * @return boolean
     */
    public function hasVariable($variable_name)
    {
        return isset($this->section_variables[$variable_name]);
    }
}
