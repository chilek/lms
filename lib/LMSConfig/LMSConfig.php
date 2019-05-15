<?php

// phpcs:disable Generic.NamingConventions.UpperCaseConstantName

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
 * @author Tomasz Chiliński <tomasz.chilinski@chilan.com>
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
     * @var ConfigContainer User rights config (privileges)
     */
    private static $user_rights_config;

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
     * @throws Exception Exception if databse connection doesn't exist
     */
    public static function getUiConfig(array $options = array())
    {
        if (!LMSDB::checkIfInstanceExists()) {
            throw new Exception('Cannot load uiconfig while database connection does not exist!');
        }
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
     * Returns user rights configuration
     *
     * Avaliable options are:
     * force - forces to reload whole ini config
     * user_id - user id
     *
     * @param array $options Associative array of options
     * @return ConfigContainer User rights configuration
     * @throws Exception Throws exception when required parameters are not set
     */
    public static function getUserRightsConfig(array $options = array())
    {
        if (!LMSDB::checkIfInstanceExists()) {
            throw new Exception('Cannot load uiconfig while database connection does not exist!');
        }
        if (!isset($options['user_id'])) {
            throw new Exception('Cannot load user rights config without user id!');
        }
        $force = (isset($options['force'])) ? $options['force'] : false;
        if ($force || self::$user_rights_config === null) {
            $options['provider'] = UserRightsConfigProvider::NAME;
            $options['parser'] = UserRightsConfigParser::NAME;
            $config_loader = new ConfigLoader();
            self::$user_rights_config = $config_loader->loadConfig($options);
        }
        return self::$user_rights_config;
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
        $ini_config = null;
        $ui_config = null;
        $rights_config = null;

        if (isset($options['force_ini_only'])) {
            $ini_config = self::getUiConfig($options);
            $ui_config = self::$ui_config;
            $rights_config = self::$user_rights_config;
        } elseif (isset($options['force_ui_only'])) {
            $ui_config = self::getUiConfig($options);
            $ini_config = self::$ini_config;
            $rights_config = self::$user_rights_config;
        } elseif (isset($options['force_user_rights_only'])) {
            try {
                $rights_config = self::getUserRightsConfig($options);
            } catch (Exception $ex) {
                $rights_config = new ConfigContainer();
            }
            $ini_config = self::$ini_config;
            $ui_config = self::$ui_config;
        } else {
            $ini_config = self::getUiConfig($options);
            $ui_config = self::getIniConfig($options);
            try {
                $rights_config = self::getUserRightsConfig($options);
            } catch (Exception $ex) {
                $rights_config = new ConfigContainer();
            }
        }

        $ui_merge_priority = self::default_ui_merge_priority;
        if (isset($options['ui_merge_priority'])) {
            $ui_merge_priority = $options['ui_merge_priority'];
        }

        $ini_merge_priority = self::default_ini_merge_priority;
        if (isset($options['ini_merge_priority'])) {
            $ui_merge_priority = $options['ini_merge_priority'];
        }

        $config = null;

        if ($ini_merge_priority < $ui_merge_priority) {
            $config = self::overrideConfigs($ui_config, $ini_config);
        } else {
            $config = self::overrideConfigs($ini_config, $ui_config);
        }

        return self::appendOneConfigSectionsToAnother($config, $rights_config);
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

    /**
     * Adds sections from one config to another
     *
     * @param ConfigContainer $primary_config Primary config
     * @param ConfigContainer $secondary_config Secondary config
     * @return \ConfigContainer Primary config
     */
    private static function appendOneConfigSectionsToAnother(ConfigContainer $primary_config, ConfigContainer $secondary_config)
    {
        $primary_config->addSections($secondary_config->getSections());
        return $primary_config;
    }
}
