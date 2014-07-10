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
 * UserRightsConfigParser
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class UserRightsConfigParser implements ConfigParserInterface
{
    const NAME = 'USER_RIGHTS_CONFIG_PARSER';
    
    /**
     * Converts user rights mask into it's object representation
     * 
     * @param array $raw_config Raw config
     * @param array $options Associative array of options
     * @return \ConfigContainer Config object
     */
    public function objectify(array $raw_config = array(), array $options = array())
    {
        if (!isset($options['access_table'])) {
            throw new Exception('Access table not provided. Cannot find user rights config!');
        }
        
        $config = new ConfigContainer();
        
        $access_table = $options['access_table'];
        
        $mask = $raw_config[0];
        
        $len = strlen($mask);
        $bin = '';
        $result = array();

        for ($cnt = $len; $cnt > 0; $cnt--) {
            $bin = sprintf('%04b', hexdec($mask[$cnt - 1])) . $bin;
        }

        $len = strlen($bin);
        for ($cnt = $len - 1; $cnt >= 0; $cnt--) {
            if ($bin[$cnt] == '1') {
                $result[] = $len - $cnt - 1;
            }
        }
        
        $variables = array();
        
        foreach ($result as $level)
        {
            if ($level === 0) {
                $variables[] = new ConfigVariable('superuser', true);
            }
            if (isset($access_table[$level]['privilege'])) {
                $variables[] = new ConfigVariable($access_table[$level]['privilege'], true);
            }
        }
        
        $section = new ConfigSection('privileges');
        $section->addVariables($variables);
        $config->addSection($section);
        
        return $config;
    }
}
