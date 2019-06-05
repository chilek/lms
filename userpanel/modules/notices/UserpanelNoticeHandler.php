<?php

/**
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

class UserpanelNoticeHandler
{
    private $db;
    private $smarty;
    private $customerid;
    private static $instance = null;

    public function __construct($db, $smarty, $customerid)
    {
        $this->db = $db;
        $this->smarty = $smarty;
        $this->customerid = $customerid;
        self::$instance = $this;
    }

    public static function getInstance()
    {
        return self::$instance;
    }

    public function getUrgentNotice()
    {
        return $this->db->GetRow(
            'SELECT m.subject, m.cdate, m.body, m.type, mi.id, mi.messageid, mi.destination, mi.status
				FROM messageitems mi, messages m
				WHERE m.id = mi.messageid
                    AND m.type = ?
                    AND mi.status = ?
                    AND mi.customerid = ?
                ORDER BY m.cdate ASC
                LIMIT 1',
            array(MSG_USERPANEL_URGENT, MSG_SENT, $this->customerid)
        );
    }

    public function getUnreadNotices()
    {
        return $this->db->GetOne(
            'SELECT COUNT(*)
        FROM messages m
        JOIN messageitems mi ON mi.messageid = m.id
        WHERE m.type IN (?, ?)
            AND mi.status = ?
            AND mi.customerid = ?
            AND mi.lastreaddate = 0
        ',
            array(MSG_USERPANEL, MSG_USERPANEL_URGENT, MSG_SENT, $this->customerid)
        );
    }

    public function markNoticeAsRead($id)
    {
        $this->db->Execute(
            'UPDATE messageitems SET lastreaddate = ?NOW? WHERE id = ?',
            array($id)
        );
    }

    public function markNoticeAsDelivered($id)
    {
        $this->db->Execute(
            'UPDATE messageitems SET status = ?, lastdate = ?NOW? WHERE id = ?',
            array(MSG_DELIVERED, $id)
        );
    }
}
