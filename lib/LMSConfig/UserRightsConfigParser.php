<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2015 LMS Developers
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
 * @author Tomasz Chiliński <tomasz.chilinski@chilan.com>
 */
class UserRightsConfigParser implements ConfigParserInterface
{
    const NAME = 'USER_RIGHTS_CONFIG_PARSER';
    
    /**
     * Converts user rights array into it's object representation
     *
     * @param array $raw_config Raw config
     * @param array $options Associative array of options
     * @return \ConfigContainer Config object
     */
    public function objectify(array $raw_config = array(), array $options = array())
    {
        $config = new ConfigContainer();

        $rights = $raw_config[0];

        $access = AccessRights::getInstance();
        $variables = array();

        foreach ($rights as $right) {
            if ($right === 'full_access') {
                $variables[] = new ConfigVariable('superuser', true);
            }
            if ($access->checkPrivilege($right)) {
                $variables[] = new ConfigVariable($right, true);
            }
        }

        $section = new ConfigSection('privileges');
        $section->addVariables($variables);
        $config->addSection($section);

        return $config;
    }
}
