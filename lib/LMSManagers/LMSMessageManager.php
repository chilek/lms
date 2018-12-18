<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2018 LMS Developers
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

	public function DeleteMessageTemplates(array $ids) {
		return $this->db->Execute('DELETE FROM templates WHERE id IN ?',
			array($ids));
	}

	public function GetMessageTemplates($type = 0)
    {
        return $this->db->GetAll('SELECT id, type, name, subject, message FROM templates
			' . (empty($type) ? '' : ' WHERE type = ' . intval($type)) . '
			ORDER BY name');
    }

	public function GetMessageList(array $params) {
		extract($params);
		foreach (array('search', 'cat', 'status') as $var)
			if (!isset($$var))
				$$var = null;
		if (!isset($order))
			$order = 'cdate,desc';
		if (!isset($type))
			$type = '';
		if (!isset($count))
			$count = false;

		if($order=='')
			$order='cdate,desc';

		list($order,$direction) = sscanf($order, '%[^,],%s');
		($direction=='desc') ? $direction = 'desc' : $direction = 'asc';

		switch($order)
		{
			case 'subject':
				$sqlord = ' ORDER BY m.subject';
				break;
			case 'type':
				$sqlord = ' ORDER BY m.type';
				break;
			case 'cnt':
				$sqlord = ' ORDER BY cnt';
				break;
			default:
				$sqlord = ' ORDER BY m.cdate';
				break;
		}

		if($search!='' && $cat)
		{
			switch($cat)
			{
				case 'userid':
					$where[] = 'm.userid = '.intval($search);
					break;
				case 'username':
					$where[] = 'UPPER(u.name) ?LIKE? UPPER(' . $this->db->Escape('%' . $search . '%') . ')';
					$userjoin = true;
					break;
				case 'subject':
					$where[] = 'UPPER(m.subject) ?LIKE? UPPER(' . $this->db->Escape('%' . $search . '%') . ')';
					break;
				case 'destination':
					$where[] = 'EXISTS (SELECT 1 FROM messageitems i
					WHERE i.messageid = m.id AND UPPER(i.destination) ?LIKE? UPPER(' . $this->db->Escape('%' . $search . '%') . '))';
					break;
				case 'customerid':
					$where[] = 'EXISTS (SELECT 1 FROM messageitems i
					WHERE i.customerid = '.intval($search).' AND i.messageid = m.id)';
					break;
				case 'name':
					$where[] = 'EXISTS (SELECT 1 FROM messageitems i
					JOIN customers c ON (c.id = i.customerid)
					WHERE i.messageid = m.id AND UPPER(c.lastname) ?LIKE? UPPER(' . $this->db->Escape('%' . $search . '%') . '))';
					break;
			}
		}

		if($type)
		{
			$type = intval($type);
			$where[] = 'm.type = '.$type;
		}

		if($status)
		{
			switch($status)
			{
				case MSG_NEW: $where[] = 'x.sent + x.delivered + x.error = 0'; break;
				case MSG_ERROR: $where[] = 'x.error > 0'; break;
				case MSG_SENT: $where[] = 'x.sent = x.cnt'; break;
				case MSG_DELIVERED: $where[] = 'x.delivered = x.cnt'; break;
			}
		}

		if(!empty($where))
			$where = 'WHERE '.implode(' AND ', $where);

		if ($count) {
			return $this->db->GetOne('SELECT COUNT(m.id)
				FROM messages m
				JOIN (
					SELECT i.messageid,
						COUNT(*) AS cnt,
						COUNT(CASE WHEN i.status = '.MSG_SENT.' THEN 1 ELSE NULL END) AS sent,
						COUNT(CASE WHEN i.status = '.MSG_DELIVERED.' THEN 1 ELSE NULL END) AS delivered,
						COUNT(CASE WHEN i.status = '.MSG_ERROR.' THEN 1 ELSE NULL END) AS error
					FROM messageitems i
					LEFT JOIN (
						SELECT DISTINCT a.customerid FROM customerassignments a
							JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
						WHERE e.userid = lms_current_user()
					) e ON (e.customerid = i.customerid)
					WHERE e.customerid IS NULL
					GROUP BY i.messageid
				) x ON (x.messageid = m.id) '
				.(!empty($userjoin) ? 'JOIN users u ON (u.id = m.userid) ' : '')
				.(!empty($where) ? $where : ''));
		}

		$result = $this->db->GetAll('SELECT m.id, m.cdate, m.type, m.subject,
			x.cnt, x.sent, x.error, x.delivered
			FROM messages m
			JOIN (
				SELECT i.messageid,
					COUNT(*) AS cnt,
					COUNT(CASE WHEN i.status = '.MSG_SENT.' THEN 1 ELSE NULL END) AS sent,
					COUNT(CASE WHEN i.status = '.MSG_DELIVERED.' THEN 1 ELSE NULL END) AS delivered,
					COUNT(CASE WHEN i.status = '.MSG_ERROR.' THEN 1 ELSE NULL END) AS error
				FROM messageitems i
				LEFT JOIN (
					SELECT DISTINCT a.customerid FROM customerassignments a
						JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
					WHERE e.userid = lms_current_user()
				) e ON (e.customerid = i.customerid)
				WHERE e.customerid IS NULL
				GROUP BY i.messageid
			) x ON (x.messageid = m.id) '
			.(!empty($userjoin) ? 'JOIN users u ON (u.id = m.userid) ' : '')
			.(!empty($where) ? $where : '')
			.$sqlord.' '.$direction
			. (isset($limit) ? ' LIMIT ' . $limit : '')
			. (isset($offset) ? ' OFFSET ' . $offset : ''));

		$result['type'] = $type;
		$result['status'] = $status;
		$result['order'] = $order;
		$result['direction'] = $direction;

		return $result;
	}
}
