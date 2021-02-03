<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2020 LMS Developers
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
 * ConfigLoader
 */
class ConfigLoader
{
    /**
     * Loads config
     *
     * Avaliable options are:
     * provider - config provider name
     * parser - config parser name
     * ini_file_path - path to ini file
     *
     * Retrieves raw config from provider, and passes it to parser. Returns
     * parser output.
     *
     * @param array $options Associative array of options
     * @return ConfigContainer Config
     */
    public function loadConfig(array $options = array())
    {
        $raw_config = $this->loadRawConfig($options);
        return $this->parseRawConfig($raw_config, $options);
    }

    /**
     * Loads raw config
     *
     * Avaliable options are:
     * provider - config provider name
     * ini_file_path - path to ini file
     *
     * Choses provider and passes options to it.
     *
     * @param array $options Associative array of options
     * @return array Raw config
     * @throws Exception Throws exception when config provider is unknown
     */
    private function loadRawConfig(array $options = array())
    {
        if (!isset($options['provider'])) {
            throw new Exception('Config provider not set!');
        }

        $provider = null;

        switch ($options['provider']) {
            case IniConfigProvider::NAME:
                $provider = new IniConfigProvider();
                break;
            case UiConfigProvider::NAME:
                $provider = new UiConfigProvider();
                break;
            case UserRightsConfigProvider::NAME:
                $provider = new UserRightsConfigProvider();
                break;
            default:
                throw new Exception('Unknown config provider!');
        }

        return $provider->load($options);
    }

    /**
     * Parses raw config
     *
     * Avaliable options are:
     * parser - config parser name
     *
     * Choses provider, passes raw config to it and returns it's output.
     *
     * @param array $raw_config Raw config
     * @param array $options Associative array of options
     * @return ConfigContainer Config
     * @throws Exception Throws exception when config parser is unknown
     */
    private function parseRawConfig(array $raw_config, array $options = array())
    {
        if (!isset($options['parser'])) {
            throw new Exception('Config parser not set!');
        }

        $parser = null;

        switch ($options['parser']) {
            case IniConfigParser::NAME:
                $parser = new IniConfigParser();
                break;
            case UiConfigParser::NAME:
                $parser = new UiConfigParser();
                break;
            case UserRightsConfigParser::NAME:
                $parser = new UserRightsConfigParser();
                break;
            default:
                throw new Exception('Unknown config parser!');
        }

        return $parser->objectify($raw_config, $options);
    }
}
