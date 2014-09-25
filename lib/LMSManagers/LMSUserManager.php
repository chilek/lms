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
 * LMSUserManager
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class LMSUserManager extends LMSManager
{
    /**
     * Returns user name
     * 
     * @param int $id User id
     * @return string User name
     */
    public function getUserName($id = null)
    {
        if ($id === null) {
            $id = $this->auth->id;
        } else if (!$id) {
            return '';
        }

        if (!($name = $this->cache->getCache('users', $id, 'name'))) {
            if ($this->auth && $this->auth->id == $id) {
                $name = $this->auth->logname;
            } else {
                $name = $this->db->GetOne('SELECT name FROM users WHERE id=?', array($id));
            }
            $this->cache->setCache('users', $id, 'name', $name);
        }
        return $name;
    }

}
