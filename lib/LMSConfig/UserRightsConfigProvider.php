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
 * UserRightsConfigProvider
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 * @author Tomasz Chiliński <tomasz.chilinski@chilan.com>
 */
class UserRightsConfigProvider implements ConfigProviderInterface
{
    const NAME = 'USER_RIGHTS_CONFIG_PROVIDER';
    
    /**
     * Return user rights array
     *
     * @param array $options Associative array of options
     * @return array
     */
    public function load(array $options = array())
    {
        if (!isset($options['user_id'])) {
            throw new Exception('User id not set. Cannot find user rights config!');
        }
        $id = $options['user_id'];
        $db = LMSDB::getInstance();
        $rights = $db->GetOne('SELECT rights FROM users WHERE id = ?', array($id));
        $rights = empty($rights) ? array() : explode(',', $rights);

        return array($rights);
    }
}
