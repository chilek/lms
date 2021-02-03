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
 * IniConfigProvider
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class IniConfigProvider implements ConfigProviderInterface
{
    const NAME = 'INI_CONFIG_PROVIDER';
    
    /**
     * Loads ini file and returns it as raw config
     *
     * Avaliable options are:
     * ini_file_path - path to ini file
     *
     * By default searches for ini file in defined CONFIG_FILE path.
     *
     * @param array $options Associative array of options
     * @return array Raw config
     * @throws Exception Throws exception when unable to read ini file
     */
    public function load(array $options = array())
    {
        $ini_file_path = CONFIG_FILE;
        
        if (isset($options['ini_file_path'])) {
            $ini_file_path = $options['ini_file_path'];
        }
        
        if (!is_readable($ini_file_path)) {
            throw new Exception('Unable to read ini file!');
        }
        
        return parse_ini_file($ini_file_path, true);
    }
}
