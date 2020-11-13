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
 * LMSMessageManager
 *
 */
class LMSMessageManager extends LMSManager implements LMSMessageManagerInterface
{

    public function GetMessages($customerid, $limit = null)
    {
        $result = $this->db->GetAll('SELECT i.messageid AS id, i.status, i.error,
		        i.destination, i.lastdate, i.lastreaddate, m.subject, m.type, m.cdate,
		        u.name AS username, u.id AS userid, fc.id AS filecontainerid
			FROM messageitems i
			JOIN messages m ON (m.id = i.messageid)
			LEFT JOIN filecontainers fc ON fc.messageid = m.id
			LEFT JOIN vusers u ON u.id = m.userid
			WHERE i.customerid = ?
			ORDER BY m.cdate DESC'
                        . ($limit ? ' LIMIT ' . $limit : ''), array($customerid));

        if (!empty($result)) {
            foreach ($result as &$message) {
                if (!empty($message['filecontainerid'])) {
                    if (!isset($file_manager)) {
                        $file_manager = new LMSFileManager($this->db, $this->auth, $this->cache, $this->syslog);
                    }
                    $file_containers = $file_manager->GetFileContainers('messageid', $message['id']);
                    $message['files'] = $file_containers[0]['files'];
                }
            }
            unset($message);
        }

        return $result;
    }

    public function MessageTemplateExists($type, $name)
    {
        return $this->db->GetOne(
            'SELECT id FROM templates WHERE type = ? AND name = ?',
            array($type, $name)
        );
    }

    public function AddMessageTemplate($type, $name, $subject, $helpdesk_queues, $helpdesk_message_types, $message)
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

            if ($type == TMPL_HELPDESK) {
                if (isset($helpdesk_queues) && !empty($helpdesk_queues)) {
                    foreach ($helpdesk_queues as $queueid) {
                        $this->db->Execute('INSERT INTO rttemplatequeues (templateid, queueid)
							VALUES (?, ?)', array($id, $queueid));
                    }
                }
                if (isset($helpdesk_message_types) && !empty($helpdesk_message_types)) {
                    foreach ($helpdesk_message_types as $message_type) {
                        $this->db->Execute('INSERT INTO rttemplatetypes (templateid, messagetype)
							VALUES (?, ?)', array($id, $message_type));
                    }
                }
            }

            return $id;
        }
        return false;
    }

    public function UpdateMessageTemplate($id, $type, $name, $subject, $helpdesk_queues, $helpdesk_message_types, $message)
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
        } else {
            $res = $this->db->Execute('UPDATE templates SET type = ?, name = ?, subject = ?, message = ?
				WHERE id = ?', array_values($args));
        }
        if ($res && $this->syslog) {
            $args[SYSLOG::RES_TMPL] = $id;
            $this->syslog->AddMessage(SYSLOG::RES_TMPL, SYSLOG::OPER_UPDATE, $args);
        }

        $helpdesk_manager = new LMSHelpdeskManager($this->db, $this->auth, $this->cache, $this->syslog);
        $queues = $helpdesk_manager->GetMyQueues();
        if (!empty($queues)) {
            $this->db->Execute(
                'DELETE FROM rttemplatequeues WHERE templateid = ? AND queueid IN ?',
                array($id, $queues)
            );
        }

        $this->db->Execute('DELETE FROM rttemplatetypes WHERE templateid = ?', array($id));

        if ($type == TMPL_HELPDESK) {
            if (isset($helpdesk_queues) && !empty($helpdesk_queues)) {
                foreach ($helpdesk_queues as $queueid) {
                    $this->db->Execute('INSERT INTO rttemplatequeues (templateid, queueid)
							VALUES (?, ?)', array($id, $queueid));
                }
            }
            if (isset($helpdesk_message_types) && !empty($helpdesk_message_types)) {
                foreach ($helpdesk_message_types as $message_type) {
                    $this->db->Execute('INSERT INTO rttemplatetypes (templateid, messagetype)
							VALUES (?, ?)', array($id, $message_type));
                }
            }
        }

        return $res;
    }

    public function DeleteMessageTemplates(array $ids)
    {
        return $this->db->Execute(
            'DELETE FROM templates WHERE id IN ?',
            array($ids)
        );
    }

    public function GetMessageTemplates($type = 0)
    {
        $helpdesk_manager = new LMSHelpdeskManager($this->db, $this->auth, $this->cache, $this->syslog);
        $queues = $helpdesk_manager->GetMyQueues();

        return $this->db->GetAll('SELECT t.id, t.type, t.name, t.subject, t.message,
				tt.messagetypes, tq.queues, tq.queuenames
			FROM templates t
			LEFT JOIN (
				SELECT templateid, ' . $this->db->GroupConcat('messagetype') . ' AS messagetypes
				FROM rttemplatetypes
				GROUP BY templateid
			) tt ON tt.templateid = t.id
			LEFT JOIN (
				SELECT templateid, ' . $this->db->GroupConcat('queueid') . ' AS queues,
					' . $this->db->GroupConcat('q.name') . ' AS queuenames
				FROM rttemplatequeues
				JOIN rtqueues q ON q.id = queueid
				WHERE ' . (empty($queues) ? '1=0' : 'queueid IN (' . implode(',', $queues) . ')') . '
				GROUP BY templateid
			) tq ON tq.templateid = t.id
			WHERE 1 = 1' . (empty($type) ? '' : ' AND t.type = ' . intval($type))
            . ' ORDER BY t.name');
    }

    public function GetMessageTemplatesByQueueAndType($queueid, $type)
    {
        return $this->db->GetAll(
            'SELECT DISTINCT t.id, t.name, t.subject, t.message
			FROM templates t
			LEFT JOIN rttemplatequeues tq ON tq.templateid = t.id AND tq.queueid ' . (is_array($queueid) ? 'IN' : '=') . ' ?
			LEFT JOIN rttemplatetypes tt ON tt.templateid = t.id AND tt.messagetype = ?
			LEFT JOIN (
				SELECT t2.id AS templateid
				FROM templates t2
				LEFT JOIN rttemplatequeues tq2 ON tq2.templateid = t2.id
				GROUP BY t2.id
				HAVING COUNT(tq2.templateid) = 0
			) t3 ON t3.templateid = t.id
			LEFT JOIN (
				SELECT t4.id AS templateid
				FROM templates t4
				LEFT JOIN rttemplatequeues tt2 ON tt2.templateid = t4.id
				GROUP BY t4.id
				HAVING COUNT(tt2.templateid) = 0
			) t5 ON t5.templateid = t.id
			WHERE t.type = ? AND (tq.templateid IS NOT NULL OR t.id = t3.templateid)
				AND (tt.templateid IS NOT NULL OR t.id = t5.templateid)  
			GROUP BY t.id, t.name, t.subject, t.message',
            array(is_array($queueid) ? $queueid : intval($queueid), $type, TMPL_HELPDESK)
        );
    }

    public function GetMessageList(array $params)
    {
        extract($params);
        foreach (array('search', 'cat', 'status') as $var) {
            if (!isset($$var)) {
                $$var = null;
            }
        }
        if (!isset($order)) {
            $order = 'cdate,desc';
        }

        if (isset($datefrom)) {
            $datefrom = intval($datefrom);
        } else {
            $datefrom = 0;
        }

        if (isset($dateto)) {
            $dateto = intval($dateto);
        } else {
            $dateto = 0;
        }

        if (!isset($type)) {
            $type = '';
        }
        if (!isset($count)) {
            $count = false;
        }

        if ($order=='') {
            $order='cdate,desc';
        }


        list($order,$direction) = sscanf($order, '%[^,],%s');
        ($direction=='desc') ? $direction = 'desc' : $direction = 'asc';

        switch ($order) {
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

        if ($search!='' && $cat) {
            switch ($cat) {
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

        if ($type) {
            $type = intval($type);
            $where[] = 'm.type = '.$type;
        }

        if ($datefrom) {
            $where[] = 'm.cdate >= ' . $datefrom;
        }

        if ($dateto) {
            $where[] = 'm.cdate <= ' . $dateto;
        }

        if ($status) {
            switch ($status) {
                case MSG_NEW:
                    $where[] = 'x.sent + x.delivered + x.error = 0';
                    break;
                case MSG_ERROR:
                    $where[] = 'x.error > 0';
                    break;
                case MSG_SENT:
                    $where[] = 'x.sent = x.cnt';
                    break;
                case MSG_DELIVERED:
                    $where[] = 'x.delivered = x.cnt';
                    break;
            }
        }

        if (!empty($where)) {
            $where = 'WHERE '.implode(' AND ', $where);
        }

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
                .(!empty($userjoin) ? 'JOIN vusers u ON (u.id = m.userid) ' : '')
                .(!empty($where) ? $where : ''));
        }

        $result = $this->db->GetAll('SELECT m.id, m.cdate, m.type, m.subject,
			x.cnt, x.sent, x.error, x.delivered, fc.id AS filecontainerid
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
			) x ON (x.messageid = m.id)
			LEFT JOIN filecontainers fc ON fc.messageid = m.id '
            .(!empty($userjoin) ? 'JOIN vusers u ON (u.id = m.userid) ' : '')
            .(!empty($where) ? $where : '')
            .$sqlord.' '.$direction
            . (isset($limit) ? ' LIMIT ' . $limit : '')
            . (isset($offset) ? ' OFFSET ' . $offset : ''));

        if (!empty($result)) {
            foreach ($result as &$message) {
                if (!empty($message['filecontainerid'])) {
                    if (!isset($file_manager)) {
                        $file_manager = new LMSFileManager($this->db, $this->auth, $this->cache, $this->syslog);
                    }
                    $file_containers = $file_manager->GetFileContainers('messageid', $message['id']);
                    $message['files'] = $file_containers[0]['files'];
                }
            }
            unset($message);
        }

        $result['type'] = $type;
        $result['status'] = $status;
        $result['order'] = $order;
        $result['direction'] = $direction;

        return $result;
    }

    public function addMessage(array $params)
    {
        $result = array();

        $this->db->Execute('INSERT INTO messages (type, cdate, subject, body, userid, sender, contenttype)
			VALUES (?, ?NOW?, ?, ?, ?, ?, ?)', array(
            $params['type'],
            $params['subject'],
            $params['body'],
            isset($params['userid']) ? $params['userid'] : Auth::GetCurrentUser(),
            $params['type'] == MSG_MAIL && isset($params['sender']) ? '"' . $params['sender']['name'] . '" <' . $params['sender']['mail'] . '>' : '',
            $params['contenttype'],
        ));

        $result['id'] = $msgid  = $this->db->GetLastInsertID('messages');

        $msgitems = array();

        foreach ($params['recipients'] as &$row) {
            switch ($params['type']) {
                case MSG_MAIL:
                    $row['destination'] = explode(',', $row['email']);
                    break;
                case MSG_WWW:
                    $row['destination'] = array(trans('www'));
                    break;
                case MSG_USERPANEL:
                    $row['destination'] = array(trans('userpanel'));
                    break;
                case MSG_USERPANEL_URGENT:
                    $row['destination'] = array(trans('userpanel urgent'));
                    break;
                default:
                    $row['destination'] = explode(',', $row['phone']);
            }

            $customerid = isset($row['id']) ? $row['id'] : 0;
            foreach ($row['destination'] as $destination) {
                $this->db->Execute(
                    'INSERT INTO messageitems (messageid, customerid,
					destination, status)
					VALUES (?, ?, ?, ?)',
                    array($msgid, empty($customerid) ? null : $customerid, $destination, MSG_NEW)
                );
                if (!isset($msgitems[$customerid])) {
                    $msgitems[$customerid] = array();
                }
                $msgitems[$customerid][$destination] = $this->db->GetLastInsertID('messageitems');
            }
        }
        unset($row);

        $result['items'] = $msgitems;

        return $result;
    }

    public function updateMessageItems(array $params)
    {
        if (strcmp($params['original_body'], $params['real_body'])) {
            return $this->db->Execute(
                'UPDATE messageitems SET body = ?
                WHERE messageid = ? AND customerid '
                . (isset($params['customerid']) && !empty($params['customerid']) ? '= ' . intval($params['customerid']) : 'IS NULL'),
                array($params['real_body'], $params['messageid'])
            );
        }
        return 0;
    }

    public function getSingleMessage($id, $details = false)
    {
        $message = $this->db->GetRow(
            'SELECT m.*, u.name AS username, u.id AS userid
            FROM messages m
            LEFT JOIN vusers u ON u.id = m.userid
            WHERE m.id = ?',
            array($id)
        );

        if ($details && !empty($message)) {
            $message['items'] = $this->db->GetAll(
                'SELECT i.messageid AS id, i.status, i.error,
                    i.destination, i.lastdate, i.lastreaddate
                FROM messageitems i
                WHERE i.messageid = ?',
                array($id)
            );
        }

        return $message;
    }
}
