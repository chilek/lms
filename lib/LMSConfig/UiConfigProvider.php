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
 * UiConfigProvider
 */
class UiConfigProvider implements ConfigProviderInterface
{
    const NAME = 'UI_CONFIG_PROVIDER';

    /**
     * Return uiconfig database table
     *
     * @param array $options Associative array of options
     * @return array
     */
    public function load(array $options = array())
    {
        $db = LMSDB::getInstance();
        $userid = (isset($options['user_id'])) ? $options['user_id'] : false;
        if (!$userid) {
            $result = $db->GetAll('SELECT section, var, value, description FROM uiconfig WHERE disabled = 0 AND userid is null');
        } else {
            $result = $db->GetAll(
                'SELECT u1.section, u1.var, u1.value, u1.description
            FROM uiconfig u1
            WHERE u1.disabled = 0
            AND u1.id = (SELECT u2.id
                FROM uiconfig u2
                WHERE u2.section = u1.section
                AND u2.var = u1.var
                AND (u2.userid = ? OR u2.userid is null)
                ORDER BY COALESCE(u2.userid, 0) DESC LIMIT 1)',
                array($userid)
            );
        }

        return is_array($result) ? $result : array();
    }
}
