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
 * ConfigHelper
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class ConfigHelper
{
    /**
     * Returns config cariable value
     * 
     * @param string $name Config variable name in section.variable format
     * @param string $default Default value
     * @return string
     */
    public static function getConfig($name, $default = null)
    {
        list($section_name, $variable_name) = explode('.', $name, 2);

        if (empty($variable_name)) {
            return $default;
        }

        if (!LMSConfig::getConfig()->hasSection($section_name)) {
            return $default;
        }

        if (!LMSConfig::getConfig()->getSection($section_name)->hasVariable($variable_name)) {
            return $default;
        }

        $value = LMSConfig::getConfig()->getSection($section_name)->getVariable($variable_name)->getValue();

        return $value == '' ? $default : $value;
    }
    
    /**
     * Checks if config variable exists
     * 
     * @param string $name Config variable name in section.variable format
     * @return boolean
     */
    public static function checkConfig($name)
    {
        list($section_name, $variable_name) = explode('.', $name, 2);

        if (empty($variable_name)) {
            return false;
        }
        
        if ($section_name === 'privileges' && !self::getConfig($name)) {
            return preg_match('/^hide/', $variable_name) ? false : true;
        }

        if (!LMSConfig::getConfig()->hasSection($section_name)) {
            return false;
        }

        if (!LMSConfig::getConfig()->getSection($section_name)->hasVariable($variable_name)) {
            return false;
        }
        
        return self::checkValue(LMSConfig::getConfig()->getSection($section_name)->getVariable($variable_name)->getValue());
    }
    
    /**
     * Determines if value equals true or false
     * 
     * @param string $value Value to check
     * @param boolean $default Default flag
     * @return boolean
     */
    public static function checkValue($value, $default = false)
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === '') {
            return $default;
        }

        if (preg_match('/^(1|y|on|yes|true|tak|t|enabled)$/i', $value)) {
            return true;
        }

        if (preg_match('/^(0|n|no|off|false|nie|disabled)$/i', $value)) {
            return false;
        }

        trigger_error('Incorrect option value: '.$value);
    }
    
}
