<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2016 LMS Developers
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

class LMSUserGroupManager extends LMSManager implements LMSUserGroupManagerInterface
{

    public function UsergroupGetId($name)
    {
        return $this->db->GetOne('SELECT id FROM usergroups WHERE name=?', array($name));
    }

    public function UsergroupAdd($usergroupdata)
    {
        if ($this->db->Execute('INSERT INTO usergroups (name, description) VALUES (?, ?)', array($usergroupdata['name'], $usergroupdata['description']))) {
            $id = $this->db->GetLastInsertID('usergroups');
            if ($this->syslog) {
                $args = array(
                    SYSLOG::RES_USERGROUP => $id,
                    'name' => $usergroupdata['name'],
                    'description' => $usergroupdata['description']
                );
                $this->syslog->AddMessage(SYSLOG::RES_USERGROUP, SYSLOG::OPER_ADD, $args);
            }
            return $id;
        } else {
            return false;
        }
    }

    public function UsergroupGetList()
    {
        if ($usergrouplist = $this->db->GetAll('SELECT u.id, u.name, u.description,
				(SELECT COUNT(*)
					FROM userassignments
					WHERE usergroupid = u.id
				) AS userscount,
				' . $this->db->GroupConcat('ua.userid') . ' AS users
				FROM usergroups u
				LEFT JOIN userassignments ua ON ua.usergroupid = u.id
				GROUP BY u.id, u.name, u.description
				ORDER BY u.name ASC')) {
            $totalcount = 0;

            foreach ($usergrouplist as $row) {
                $totalcount += $row['userscount'];
            }

            $usergrouplist['total'] = count($usergrouplist);
            $usergrouplist['totalcount'] = $totalcount;
        }

        return $usergrouplist;
    }

    public function UsergroupGet($id)
    {
        $result = $this->db->GetRow('SELECT id, name, description FROM usergroups WHERE id=?', array($id));
        $result['users'] = $this->db->GetAll('SELECT vu.id AS id, vu.name AS username FROM userassignments, vusers vu '
                . 'WHERE vu.id = userid AND usergroupid = ? '
                . ' GROUP BY vu.id, vu.name, vu.lastname ORDER BY vu.lastname', array($id));

        $result['userscount'] = empty($result['users']) ? 0 : count($result['users']);
        $result['count'] = $result['userscount'];
        return $result;
    }

    public function UsergroupExists($id)
    {
        return ($this->db->GetOne('SELECT id FROM usergroups WHERE id=?', array($id)) ? true : false);
    }

    public function GetUserWithoutGroupNames($groupid)
    {
        return $this->db->GetAll('SELECT vu.id AS id, vu.name AS username FROM vusers vu WHERE vu.deleted = 0
	    AND vu.id NOT IN (
		SELECT userid FROM userassignments WHERE usergroupid = ?)
	    GROUP BY vu.id, vu.name
	    ORDER BY vu.name', array($groupid));
    }

    public function UserassignmentDelete($userassignmentdata)
    {
        if ($this->syslog) {
            $assign = $this->db->GetRow('SELECT id, userid FROM userassignments WHERE usergroupid = ? AND userid = ?', array($userassignmentdata['usergroupid'], $userassignmentdata['userid']));
            if ($assign) {
                $args = array(
                    SYSLOG::RES_USERASSIGN => $assign['id'],
                    SYSLOG::RES_USER => $assign['userid'],
                    SYSLOG::RES_USERGROUP => $userassignmentdata['usergroupid']
                );
                $this->syslog->AddMessage(SYSLOG::RES_USERASSIGN, SYSLOG::OPER_DELETE, $args);
            }
        }
        return $this->db->Execute('DELETE FROM userassignments WHERE usergroupid=? AND userid=?', array($userassignmentdata['usergroupid'], $userassignmentdata['userid']));
    }

    public function UserassignmentExist($groupid, $userid)
    {
        return $this->db->GetOne('SELECT 1 FROM userassignments WHERE usergroupid=? AND userid=?', array($groupid, $userid));
    }

    public function UserassignmentAdd($userassignmentdata)
    {
        $res = $this->db->Execute('INSERT INTO userassignments (usergroupid, userid) VALUES (?, ?)', array($userassignmentdata['usergroupid'], $userassignmentdata['userid']));
        if ($this->syslog && $res) {
            $id = $this->db->GetLastInsertID('userassignments');
            $args = array(
                SYSLOG::RES_USERASSIGN => $id,
                SYSLOG::RES_USER => $userassignmentdata['userid'],
                SYSLOG::RES_USERGROUP => $userassignmentdata['usergroupid']
            );
            $this->syslog->AddMessage(SYSLOG::RES_USERASSIGN, SYSLOG::OPER_ADD, $args);
        }
        return $res;
    }

    public function UsergroupDelete($id)
    {
        if (!$this->UsergroupWithUserGet($id)) {
            if ($this->syslog) {
                $userassigns = $this->db->GetAll('SELECT id, userid, usergroupid FROM userassignments WHERE usergroupid = ?', array($id));
                if (!empty($userassigns)) {
                    foreach ($userassigns as $userassign) {
                        $args = array(
                        SYSLOG::RES_USERASSIGN => $userassign['id'],
                        SYSLOG::RES_USER => $userassign['userid'],
                        SYSLOG::RES_USERGROUP => $userassign['usergroupid']
                        );
                        $this->syslog->AddMessage(SYSLOG::RES_USERASSIGN, SYSLOG::OPER_DELETE, $args);
                    }
                }
                $this->syslog->AddMessage(SYSLOG::RES_USERGROUP, SYSLOG::OPER_DELETE, array(SYSLOG::RES_USERGROUP => $id));
            }
            $this->db->Execute('DELETE FROM usergroups WHERE id=?', array($id));
            return true;
        } else {
            return false;
        }
    }

    public function UsergroupWithUserGet($id)
    {
        return $this->db->GetOne('SELECT COUNT(*) FROM userassignments WHERE usergroupid = ?', array($id));
    }

    public function UsergroupUpdate($usergroupdata)
    {
        $args = array(
            'name' => $usergroupdata['name'],
            'description' => $usergroupdata['description'],
            SYSLOG::RES_USERGROUP => $usergroupdata['id']
        );
        if ($this->syslog) {
            $this->syslog->AddMessage(SYSLOG::RES_USERGROUP, SYSLOG::OPER_UPDATE, $args);
        }
        return $this->db->Execute('UPDATE usergroups SET name=?, description=? WHERE id=?', array_values($args));
    }

    public function UsergroupGetAll()
    {
        return $this->db->GetAll('SELECT g.id, g.name, g.description FROM usergroups g ORDER BY g.name ASC');
    }
}
