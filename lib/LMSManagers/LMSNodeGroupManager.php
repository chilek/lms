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

/**
 * LMSNodeGroupManager
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class LMSNodeGroupManager extends LMSManager implements LMSNodeGroupManagerInterface
{
    
    public function GetNodeGroupNames()
    {
        return $this->db->GetAllByKey('SELECT id, name, description FROM nodegroups
				ORDER BY name ASC', 'id');
    }

    public function GetNodeGroupNamesByNode($nodeid)
    {
        return $this->db->GetAllByKey('SELECT id, name, description FROM nodegroups
				WHERE id IN (SELECT nodegroupid FROM nodegroupassignments
					WHERE nodeid = ?)
				ORDER BY name', 'id', array($nodeid));
    }

    public function GetNodeGroupNamesWithoutNode($nodeid)
    {
        return $this->db->GetAllByKey('SELECT id, name FROM nodegroups
				WHERE id NOT IN (SELECT nodegroupid FROM nodegroupassignments
					WHERE nodeid = ?)
				ORDER BY name', 'id', array($nodeid));
    }

    public function GetNodesWithoutGroup($groupid, $network = NULL)
    {
        if ($network) {
            $network_manager = new LMSNetworkManager($this->db, $this->auth, $this->cache, $this->syslog);
            $net = $network_manager->GetNetworkParams($network);
        }

        return $this->db->GetAll('SELECT n.id AS id, n.name AS nodename, a.nodeid
			FROM nodes n
			JOIN customerview c ON (n.ownerid = c.id)
			LEFT JOIN nodegroupassignments a ON (n.id = a.nodeid AND a.nodegroupid = ?) 
			WHERE a.nodeid IS NULL '
                        . ($network ?
                                ' AND ((ipaddr > ' . $net['address'] . ' AND ipaddr < ' . $net['broadcast'] . ') 
					OR (ipaddr_pub > ' . $net['address'] . ' AND ipaddr_pub < ' . $net['broadcast'] . ')) ' : '')
                        . ' ORDER BY nodename', array($groupid));
    }

    public function GetNodesWithGroup($groupid, $network = NULL)
    {
        if ($network) {
            $network_manager = new LMSNetworkManager($this->db, $this->auth, $this->cache, $this->syslog);
            $net = $network_manager->GetNetworkParams($network);
        }

        return $this->db->GetAll('SELECT n.id AS id, n.name AS nodename, a.nodeid
			FROM nodes n
			JOIN customerview c ON (n.ownerid = c.id)
			JOIN nodegroupassignments a ON (n.id = a.nodeid) 
			WHERE a.nodegroupid = ?'
                        . ($network ?
                                ' AND ((ipaddr > ' . $net['address'] . ' AND ipaddr < ' . $net['broadcast'] . ') 
					OR (ipaddr_pub > ' . $net['address'] . ' AND ipaddr_pub < ' . $net['broadcast'] . ')) ' : '')
                        . ' ORDER BY nodename', array($groupid));
    }

    public function GetNodeGroup($id, $network = NULL)
    {
        $result = $this->db->GetRow('SELECT id, name, description, prio,
				(SELECT COUNT(*) FROM nodegroupassignments 
					WHERE nodegroupid = nodegroups.id) AS count
				FROM nodegroups WHERE id = ?', array($id));

        $result['nodes'] = $this->GetNodesWithGroup($id, $network);
        $result['nodescount'] = empty($result['nodes']) ? 0 : count($result['nodes']);

        return $result;
    }

    public function CompactNodeGroups()
    {
        $this->db->BeginTrans();
        $this->db->LockTables('nodegroups');
        if ($nodegroups = $this->db->GetAll('SELECT id, prio FROM nodegroups ORDER BY prio ASC')) {
            $prio = 1;
            foreach ($nodegroups as $idx => $row) {
                $this->db->Execute('UPDATE nodegroups SET prio=? WHERE id=?', array($prio, $row['id']));
                if ($this->syslog) {
                    $args = array(
                        SYSLOG::RES_NODEGROUP => $row['id'],
                        'prio' => $prio
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_NODEGROUP, SYSLOG::OPER_UPDATE, $args);
                }
                $prio++;
            }
        }
        $this->db->UnLockTables();
        $this->db->CommitTrans();
    }
}
