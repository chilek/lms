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
 * LMSMessageManager
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class LMSMessageManager extends LMSManager implements LMSMessageManagerInterface
{

    public function GetMessages($customerid, $limit = NULL)
    {
        return $this->db->GetAll('SELECT i.messageid AS id, i.status, i.error,
		        i.destination, i.lastdate, i.lastreaddate, m.subject, m.type, m.cdate,
		        u.name AS username, u.id AS userid
			FROM messageitems i
			JOIN messages m ON (m.id = i.messageid)
			LEFT JOIN vusers u ON u.id = m.userid
			WHERE i.customerid = ?
			ORDER BY m.cdate DESC'
                        . ($limit ? ' LIMIT ' . $limit : ''), array($customerid));
    }

    public function AddMessageTemplate($type, $name, $subject, $message)
    {
        $args = array(
            'type' => $type,
            'name' => $name,
            'subject' => $subject,
            'message' => $message,
        );
        if ($this->db->Execute('INSERT INTO templates (type, name, subject, message)
			VALUES (?, ?, ?, ?)', array_values($args))) {
            $id = $this->db->GetLastInsertID('templates');
            if ($this->syslog) {
                $args[SYSLOG::RES_TMPL] = $id;
                $this->syslog->AddMessage(SYSLOG::RES_TMPL, SYSLOG::OPER_ADD, $args);
            }
            return $id;
        }
        return false;
    }

    public function UpdateMessageTemplate($id, $type, $name, $subject, $message)
    {
        $args = array(
            'type' => $type,
            'name' => $name,
            'subject' => $subject,
            'message' => $message,
            SYSLOG::RES_TMPL => intval($id),
        );
        if (empty($name)) {
            unset($args['name']);
            $res = $this->db->Execute('UPDATE templates SET type = ?, subject = ?, message = ?
				WHERE id = ?', array_values($args));
        } else
            $res = $this->db->Execute('UPDATE templates SET type = ?, name = ?, subject = ?, message = ?
				WHERE id = ?', array_values($args));
        if ($res && $this->syslog) {
            $args[SYSLOG::RES_TMPL] = $id;
            $this->syslog->AddMessage(SYSLOG::RES_TMPL, SYSLOG::OPER_UPDATE, $args);
        }
        return $res;
    }

    public function GetMessageTemplates($type)
    {
        return $this->db->GetAll('SELECT id, name FROM templates
			WHERE type = ? ORDER BY name', array(intval($type)));
    }

}
