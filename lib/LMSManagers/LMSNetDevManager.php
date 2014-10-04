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
 * LMSNetDevManager
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class LMSNetDevManager extends LMSManager implements LMSNetDevManagerInterface
{

    public function GetNetDevLinkedNodes($id)
    {
        return $this->db->GetAll('SELECT n.id AS id, n.name AS name, linktype, linktechnology, linkspeed,
			ipaddr, inet_ntoa(ipaddr) AS ip, ipaddr_pub, inet_ntoa(ipaddr_pub) AS ip_pub, 
			netdev, port, ownerid,
			' . $this->db->Concat('c.lastname', "' '", 'c.name') . ' AS owner,
			net.name AS netname
			FROM nodes n
			JOIN customersview c ON c.id = ownerid
			JOIN networks net ON net.id = n.netid
			WHERE netdev = ? AND ownerid > 0 
			ORDER BY n.name ASC', array($id));
    }

    public function NetDevLinkNode($id, $devid, $type = 0, $technology = 0, $speed = 100000, $port = 0)
    {
        global $SYSLOG_RESOURCE_KEYS;

        $args = array(
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $devid,
            'linktype' => intval($type),
            'linktechnology' => intval($technology),
            'linkspeed' => intval($speed),
            'port' => intval($port),
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $id,
        );
        $res = $this->db->Execute('UPDATE nodes SET netdev=?, linktype=?,
			linktechnology=?, linkspeed=?, port=?
			WHERE id=?', array_values($args));
        if ($this->syslog && $res) {
            $args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]] = $this->db->GetOne('SELECT ownerid FROM nodes WHERE id=?', array($id));
            $this->syslog->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE],
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV],
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
        }
        return $res;
    }

    public function SetNetDevLinkType($dev1, $dev2, $type = 0, $technology = 0, $speed = 100000)
    {
        global $SYSLOG_RESOURCE_KEYS;

        $res = $this->db->Execute('UPDATE netlinks SET type=?, technology=?, speed=? WHERE (src=? AND dst=?) OR (dst=? AND src=?)', array($type, $technology, $speed, $dev1, $dev2, $dev1, $dev2));
        if ($this->syslog && $res) {
            $netlink = $this->db->GetRow('SELECT id, src, dst FROM netlinks WHERE (src=? AND dst=?) OR (dst=? AND src=?)', array($dev1, $dev2, $dev1, $dev2));
            $args = array(
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETLINK] => $netlink['id'],
                'src_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $netlink['src'],
                'dst_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $netlink['dst'],
                'type' => $type,
                'speed' => $speed,
            );
            $this->syslog->AddMessage(SYSLOG_RES_NETLINK, SYSLOG_OPER_UPDATE, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETLINK],
                'src_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV],
                'dst_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV]));
        }
        return $res;
    }

    

}
