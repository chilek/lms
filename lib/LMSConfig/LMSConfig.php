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
 * LMSConfig
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class LMSConfig
{
    const default_ui_merge_priority = 1;
    const default_ini_merge_priority = 2;

    /**
     * @var ConfigContainer Config
     */
    private static $config;
    
    /**
     * @var ConfigContainer UI Config
     */
    private static $ui_config;
    
    /**
     * @var ConfigContainer INI Config
     */
    private static $ini_config;
    
    /**
     * Returns ini config
     * 
     * Avaliable options are:
     * force - forces to reload whole ini config
     * ini_file_path - path to ini file
     * 
     * @param array $options Associative array of options
     * @return ConfigContainer Config
     */
    public static function getIniConfig(array $options = array())
    {
        $force = (isset($options['force'])) ? $options['force'] : false;
        if ($force || self::$ini_config === null) {
            $options['provider'] = IniConfigProvider::NAME;
            $options['parser'] = IniConfigParser::NAME;
            $config_loader = new ConfigLoader();
            self::$ini_config = $config_loader->loadConfig($options);
        }
        return self::$ini_config;
    }
    
    /**
     * Returns ui config
     * 
     * Avaliable options are:
     * force - forces to reload whole ini config
     * 
     * @param array $options Associative array of options
     * @return ConfigContainer Config
     */
    public static function getUiConfig(array $options = array())
    {
        $force = (isset($options['force'])) ? $options['force'] : false;
        if ($force || self::$ui_config === null) {
            $options['provider'] = UiConfigProvider::NAME;
            $options['parser'] = UiConfigParser::NAME;
            $config_loader = new ConfigLoader();
            self::$ui_config = $config_loader->loadConfig($options);
        }
        return self::$ui_config;
    }
    
    /**
     * Returns merged ini and ui configs
     * 
     * Avaliable options are:
     * force - forces to reload whole ini config
     * ini_file_path - path to ini file
     * ui_merge_priority - ui merge priority
     * ini_merge_priority - ini merge priority
     * 
     * @param array $options Associative array of options
     * @return ConfigContainer Config
     */
    public static function getConfig(array $options = array())
    {
        $force = (isset($options['force'])) ? $options['force'] : false;
        if ($force || self::$config === null) {
            self::$config = self::mergeConfigs($options);
        }
        return self::$config;
    }
    
    /**
     * Merges ini and ui configs
     * 
     * Avaliable options are:
     * force - forces to reload whole ini config
     * ini_file_path - path to ini file
     * ui_merge_priority - ui merge priority
     * ini_merge_priority - ini merge priority
     * 
     * @param array $options Associative array of options
     * @return \ConfigContainer Merged config
     */
    private static function mergeConfigs(array $options = array())
    {
        $ui_config = self::getIniConfig($options);
        $ini_config = self::getUiConfig($options);
        
        $ui_merge_priority = self::default_ui_merge_priority;
        if (isset($options['ui_merge_priority'])) {
            $ui_merge_priority = $options['ui_merge_priority'];
        }
        
        $ini_merge_priority = self::default_ini_merge_priority;
        if (isset($options['ini_merge_priority'])) {
            $ui_merge_priority = $options['ini_merge_priority'];
        }
        
        if ($ini_merge_priority < $ui_merge_priority) {
            return self::overrideConfigs($ui_config, $ini_config);
        } else {
            return self::overrideConfigs($ini_config, $ui_config);
        }
    }
    
    /**
     * Overrides secondary config variables by primary ones
     * 
     * @param ConfigContainer $primary_config Overriding config 
     * @param ConfigContainer $secondary_config Config to be overrided
     * @return \ConfigContainer Merged config
     */
    private static function overrideConfigs(ConfigContainer $primary_config, ConfigContainer $secondary_config)
    {
        $config = new ConfigContainer();
        
        $config->addSections($secondary_config->getSections());
        foreach ($primary_config->getSections() as $section) {
            if ($config->hasSection($section->getSectionName())) {
                $config->getSection($section->getSectionName())->addVariables($section->getVariables());
            } else {
                $config->addSection($section);
            }
        }
        
        return $config;
    }
    
}
