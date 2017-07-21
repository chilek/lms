<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2017 LMS Developers
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
 * LMSHelpdeskManager
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class LMSHelpdeskManager extends LMSManager implements LMSHelpdeskManagerInterface
{
	private $lastmessageid = null;

    public function GetQueue($id)
    {
        if ($queue = $this->db->GetRow('SELECT * FROM rtqueues WHERE id=?', array($id))) {
            $users = $this->db->GetAll('SELECT id, name FROM vusers WHERE deleted=0');
            foreach ($users as $user) {
                $user['rights'] = $this->GetUserRightsRT($user['id'], $id);
                $queue['rights'][] = $user;
            }
            $queue['categories'] = $this->db->GetAll('SELECT categoryid, name
                FROM rtqueuecategories
                JOIN rtcategories c ON c.id = categoryid
                WHERE queueid = ?', array($id));
            return $queue;
        } else
            return NULL;
    }

    public function GetQueueContents($ids, $order = 'createtime,desc', $state = NULL, $owner = 0, $catids = NULL, $removed = NULL)
    {
        if (!$order)
            $order = 'createtime,desc';

        list($order, $direction) = sscanf($order, '%[^,],%s');

        ($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

        switch ($order) {
            case 'ticketid':
                $sqlord = ' ORDER BY t.id';
                break;
            case 'subject':
                $sqlord = ' ORDER BY t.subject';
                break;
            case 'requestor':
                $sqlord = ' ORDER BY requestor';
                break;
            case 'owner':
                $sqlord = ' ORDER BY ownername';
                break;
            case 'lastmodified':
                $sqlord = ' ORDER BY lastmodified';
                break;
            case 'creator':
                $sqlord = ' ORDER BY creatorname';
                break;
            default:
                $sqlord = ' ORDER BY t.createtime';
                break;
        }

        switch ($state) {
            case '0':
            case '1':
            case '2':
            case '3':
                $statefilter = ' AND state = ' . $state;
                break;
            case '-1':
                $statefilter = ' AND state != ' . RT_RESOLVED;
                break;
            default:
                $statefilter = '';
                break;
        }

        if(!ConfigHelper::checkPrivilege('helpdesk_advanced_operations'))
        $removedfilter = 'AND t.deleted = 0';
        else {
	        switch ($removed) {
		        case '-1':
			        $removedfilter = ' AND t.deleted = 0';
			        break;
		        case '1':
			        $removedfilter = ' AND t.deleted = 1';
			        break;
		        default:
			        $removedfilter = '';
			        break;
	        }
        }

        if ($result = $this->db->GetAll(
                'SELECT DISTINCT t.id, t.customerid, c.address, vusers.name AS ownername,
			    t.subject, state, owner AS ownerid, t.requestor AS req, t.source,
			    CASE WHEN customerid = 0 THEN t.requestor ELSE '
                . $this->db->Concat('c.lastname', "' '", 'c.name') . ' END AS requestor,
			    t.createtime AS createtime, u.name AS creatorname, t.deleted, t.deltime, t.deluserid,
				(CASE WHEN m.lastmodified IS NULL THEN 0 ELSE m.lastmodified END) AS lastmodified
		    FROM rttickets t
		    LEFT JOIN (SELECT MAX(createtime) AS lastmodified, ticketid FROM rtmessages GROUP BY ticketid) m ON m.ticketid = t.id
		    LEFT JOIN rtticketcategories tc ON (t.id = tc.ticketid)
		    LEFT JOIN vusers ON (owner = vusers.id)
		    LEFT JOIN customeraddressview c ON (t.customerid = c.id)
		    LEFT JOIN vusers u ON (t.creatorid = u.id)
		    WHERE 1=1 '
                . (is_array($ids) ? ' AND t.queueid IN (' . implode(',', $ids) . ')' : ($ids != 0 ? ' AND t.queueid = ' . $ids : ''))
                . (is_array($catids) ? ' AND tc.categoryid IN (' . implode(',', $catids) . ')' : ($catids != 0 ? ' AND tc.categoryid = ' . $catids : ''))
                . $statefilter
                . ($owner ? ' AND t.owner = ' . intval($owner) : '')
                . $removedfilter
                . ($sqlord != '' ? $sqlord . ' ' . $direction : ''))) {
            foreach ($result as $idx => $ticket) {
		$ticket['eventcount'] = $this->db->GetOne('SELECT COUNT(id) FROM events WHERE ticketid = ?', array($ticket['id']));
		$ticket['delcount'] = $this->db->GetOne('SELECT COUNT(id) FROM rtmessages WHERE ticketid = ? AND deleted = 1 AND deltime != 0', array($ticket['id']));
                //$ticket['requestoremail'] = preg_replace('/^.*<(.*@.*)>$/', '\1',$ticket['requestor']);
                //$ticket['requestor'] = str_replace(' <'.$ticket['requestoremail'].'>','',$ticket['requestor']);
                if (!$ticket['customerid'])
                    list($ticket['requestor'], $ticket['requestoremail']) = sscanf($ticket['req'], "%[^<]<%[^>]");
                else
                    list($ticket['requestoremail']) = sscanf($ticket['req'], "<%[^>]");
                $result[$idx] = $ticket;
            }
        }

        $result['total'] = sizeof($result);
        $result['state'] = $state;
        $result['order'] = $order;
        $result['direction'] = $direction;
        $result['owner'] = $owner;
        $result['removed'] = $removed;

        return $result;
    }

    public function GetUserRightsRT($user, $queue, $ticket = NULL)
    {
        if (!$queue && $ticket) {
            if (!($queue = $this->cache->getCache('rttickets', $ticket, 'queueid')))
                $queue = $this->db->GetOne('SELECT queueid FROM rttickets WHERE id=?', array($ticket));
        }

        if (!$queue)
            return 0;

        $rights = $this->db->GetOne('SELECT rights FROM rtrights WHERE userid=? AND queueid=?', array($user, $queue));

        return ($rights ? $rights : 0);
    }

    public function GetQueueList($stats = true)
    {
	$del = 0;
        if ($result = $this->db->GetAll('SELECT q.id, name, email, description, newticketsubject, newticketbody,
				newmessagesubject, newmessagebody, resolveticketsubject, resolveticketbody, deleted, deltime, deluserid
				FROM rtqueues q'
				. (!ConfigHelper::checkPrivilege('helpdesk_advanced_operations') ? ' JOIN rtrights r ON r.queueid = q.id
				WHERE r.rights <> 0 AND r.userid = ? AND q.deleted = ?' : '') . ' ORDER BY name', array($this->auth->id, $del))) {
            if ($stats)
                foreach ($result as $idx => $row)
                    foreach ($this->GetQueueStats($row['id']) as $sidx => $row2)
                        $result[$idx][$sidx] = $row2;
        }
        return $result;
    }

    public function GetQueueNames()
    {
	$del = 0;
	return $this->db->GetAll('SELECT q.id, name FROM rtqueues q'
			. (!ConfigHelper::checkPrivilege('helpdesk_advanced_operations') ? ' JOIN rtrights r ON r.queueid = q.id
			WHERE r.rights <> 0 AND r.userid = ? AND q.deleted = ?' : '') . ' ORDER BY name', array($this->auth->id, $del));
    }

    public function QueueExists($id)
    {
        return ($this->db->GetOne('SELECT * FROM rtqueues WHERE id=?', array($id)) ? TRUE : FALSE);
    }

    public function GetQueueIdByName($queue)
    {
        return $this->db->GetOne('SELECT id FROM rtqueues WHERE name=?', array($queue));
    }

    public function GetQueueNameByTicketId($id)
    {
        return $this->db->GetOne('SELECT name FROM rtqueues '
                . 'WHERE id=(SELECT queueid FROM rttickets WHERE id=?)', array($id));
    }

    public function GetEventsByTicketId($id)
    {
        return $this->db->GetAll('SELECT events.id as id, title, description, note, date, begintime, endtime, '
                . 'userid, userid AS uad, customerid, private, closed, closeduserid, events.type, '
                . ''.$this->db->Concat('customers.name',"' '",'customers.lastname').' AS customername, '
                . ''.$this->db->Concat('users.firstname',"' '",'users.lastname').' AS username, '
                . ''.$this->db->Concat('u.firstname',"' '",'u.lastname').' AS closedusername '
                . 'FROM events '
                . 'LEFT JOIN customers ON (customerid = customers.id) '
                . 'LEFT JOIN users ON (userid = users.id) '
                . 'LEFT JOIN users u ON (closeduserid = u.id) '
                . 'WHERE ticketid = ? ORDER BY events.id ASC', array($id));
    }

    public function GetQueueName($id)
    {
        return $this->db->GetOne('SELECT name FROM rtqueues WHERE id=?', array($id));
    }

    public function GetQueueEmail($id)
    {
        return $this->db->GetOne('SELECT email FROM rtqueues WHERE id=?', array($id));
    }

    public function GetQueueStats($id)
    {
        if ($result = $this->db->GetAll('SELECT state, COUNT(state) AS scount
			FROM rttickets WHERE queueid = ? GROUP BY state ORDER BY state ASC', array($id))) {
            foreach ($result as $row)
                $stats[$row['state']] = $row['scount'];
            foreach (array('new', 'open', 'resolved', 'dead') as $idx => $value)
                $stats[$value] = isset($stats[$idx]) ? $stats[$idx] : 0;
        }
        $stats['lastticket'] = $this->db->GetOne('SELECT createtime FROM rttickets
			WHERE queueid = ? ORDER BY createtime DESC', array($id));
        $stats['delcount'] = $this->db->GetOne('SELECT COUNT(id) FROM rttickets
			WHERE queueid = ? AND deleted = 1', array($id));

        return $stats;
    }

    public function GetCategory($id)
    {
        if ($category = $this->db->GetRow('SELECT * FROM rtcategories WHERE id=?', array($id))) {
            $users = $this->db->GetAll('SELECT id, name FROM vusers WHERE deleted=0 ORDER BY login asc');
            foreach ($users as $user) {
                $user['owner'] = $this->db->GetOne('SELECT 1 FROM rtcategoryusers WHERE userid = ? AND categoryid = ?', array($user['id'], $id));
                $category['owners'][] = $user;
            }
            return $category;
        } else
            return NULL;
    }

    public function GetUserRightsToCategory($user, $category, $ticket = NULL)
    {
        if (!$category && $ticket) {
            if (!($category = $this->cache->getCache('rttickets', $ticket, 'categoryid')))
                $category = $this->db->GetCol('SELECT categoryid FROM rtticketcategories WHERE ticketid=?', array($ticket));
        }

        // grant access to ticket when no categories assigned to this ticket
        if (!$category)
            return 1;

        $owner = $this->db->GetOne('SELECT 1 FROM rtcategoryusers WHERE userid=? AND categoryid ' .
                (is_array($category) ? 'IN (' . implode(',', $category) . ')' : '= ' . $category), array($user));

        return ($owner === '1');
    }

    public function GetCategoryList($stats = true)
    {
        if ($result = $this->db->GetAll('SELECT id, name, description
				FROM rtcategories ORDER BY name')) {
            if ($stats)
                foreach ($result as $idx => $row)
                    foreach ($this->GetCategoryStats($row['id']) as $sidx => $row2)
                        $result[$idx][$sidx] = $row2;
            foreach ($result as $idx => $category)
                $result[$idx]['owners'] = $this->db->GetAll('SELECT u.id, name FROM rtcategoryusers cu
				LEFT JOIN vusers u ON cu.userid = u.id
				WHERE categoryid = ?', array($category['id']));
        }
        return $result;
    }

    public function GetCategoryStats($id)
    {
        if ($result = $this->db->GetAll('SELECT state, COUNT(state) AS scount
			FROM rttickets LEFT JOIN rtticketcategories ON rttickets.id = rtticketcategories.ticketid
			WHERE rtticketcategories.categoryid = ? GROUP BY state ORDER BY state ASC', array($id))) {
            foreach ($result as $row)
                $stats[$row['state']] = $row['scount'];
            foreach (array('new', 'open', 'resolved', 'dead') as $idx => $value)
                $stats[$value] = isset($stats[$idx]) ? $stats[$idx] : 0;
        }
        $stats['lastticket'] = $this->db->GetOne('SELECT createtime FROM rttickets
			LEFT JOIN rtticketcategories ON rttickets.id = rtticketcategories.ticketid
			WHERE rtticketcategories.categoryid = ? ORDER BY createtime DESC', array($id));

        return $stats;
    }

    public function CategoryExists($id)
    {
        return ($this->db->GetOne('SELECT * FROM rtcategories WHERE id=?', array($id)) ? TRUE : FALSE);
    }

    public function GetCategoryIdByName($category)
    {
        return $this->db->GetOne('SELECT id FROM rtcategories WHERE name=?', array($category));
    }

    public function GetCategoryListByUser($userid = NULL)
    {
        return $this->db->GetAll('SELECT c.id, name
		    FROM rtcategories c
		    LEFT JOIN rtcategoryusers cu
			ON c.id = cu.categoryid '
                        . ($userid ? 'WHERE userid = ' . intval($userid) : '' )
                        . ' ORDER BY name');
    }

    public function RTStats()
    {
        $categories = $this->GetCategoryListByUser($this->auth->id);
        if (empty($categories))
            return NULL;
        foreach ($categories as $category)
            $catids[] = $category['id'];
        return $this->db->GetAll('SELECT c.id AS id, c.name,
				    COUNT(CASE state WHEN ' . RT_NEW . ' THEN 1 END) AS new,
				    COUNT(CASE state WHEN ' . RT_OPEN . ' THEN 1 END) AS opened,
				    COUNT(CASE state WHEN ' . RT_RESOLVED . ' THEN 1 END) AS resolved,
				    COUNT(CASE state WHEN ' . RT_DEAD . ' THEN 1 END) AS dead,
				    COUNT(CASE WHEN state != ' . RT_RESOLVED . ' THEN 1 END) AS unresolved
				    FROM rtcategories c
				    LEFT JOIN rtticketcategories tc ON c.id = tc.categoryid
				    LEFT JOIN rttickets t ON t.id = tc.ticketid
				    WHERE c.id IN (' . implode(',', $catids) . ')
				    GROUP BY c.id, c.name
				    ORDER BY c.name');
    }

    public function GetQueueByTicketId($id)
    {
        if ($queueid = $this->db->GetOne('SELECT queueid FROM rttickets WHERE id=?', array($id)))
            return $this->db->GetRow('SELECT * FROM rtqueues WHERE id=?', array($queueid));
        else
            return NULL;
    }

    public function TicketExists($id)
    {
        $ticket = $this->db->GetOne('SELECT * FROM rttickets WHERE id = ?', array($id));
        $this->cache->setCache('rttickets', $id, null, $ticket);
        return $ticket;
    }

	private function SaveTicketMessageAttachments($ticketid, $messageid, $files, $cleanup = false) {
		if (!empty($files) && ($dir = ConfigHelper::getConfig('rt.mail_dir'))) {
			@umask(0007);
			$dir_permission = intval(ConfigHelper::getConfig('rt.mail_dir_permission', '0700'), 8);
			$dir = $dir . DIRECTORY_SEPARATOR . sprintf('%06d', $ticketid);
			@mkdir($dir, $dir_permission);
			$dir .= DIRECTORY_SEPARATOR . sprintf('%06d', $messageid);
			@mkdir($dir, $dir_permission);

			$dirs_to_be_deleted = array();
			foreach ($files as $file) {
				// handle spaces and unknown characters in filename
				// on systems having problems with that
				$filename = preg_replace('/[^\w\.-_]/', '_', basename($file['name']));
				$dstfile = $dir . DIRECTORY_SEPARATOR . $filename;
				if (isset($file['content'])) {
					$fh = @fopen($dstfile, 'w');
					if (empty($fh))
						continue;
					fwrite($fh, $file['content'], strlen($file['content']));
					fclose($fh);
				} else {
					if ($cleanup)
						$dirs_to_be_deleted = dirname($file['name']);
					if (!@rename(isset($file['tmp_name']) ? $file['tmp_name'] : $file['name'], $dstfile))
						continue;
				}
				$this->db->Execute('INSERT INTO rtattachments (messageid, filename, contenttype)
					VALUES (?,?,?)', array($messageid, $filename, $file['type']));
			}
			if (!empty($dirs_to_be_deleted)) {
				$dirs_to_be_deleted = array_unique($dirs_to_be_deleted);
				foreach ($dirs_to_be_deleted as $dir)
					rrmdir($dir);
			}
		}
	}

	public function TicketMessageAdd($message, $files = null) {
		$headers = '';
		if ($message['headers'])
			if (is_array($message['headers']))
				foreach ($message['headers'] as $name => $value)
					$headers .= $name . ': ' . $value . "\n";
			else
				$headers = $message['headers'];

		$this->lastmessageid = '<msg.' . $message['queue'] . '.' . $message['ticketid']
			. '.' . time() . '@rtsystem.' . gethostname() . '>';

		$this->db->Execute('INSERT INTO rtmessages (ticketid, createtime, subject, body, userid, customerid, mailfrom,
			inreplyto, messageid, replyto, headers, type)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$message['ticketid'],
				isset($message['createtime']) ? $message['createtime'] : time(),
				isset($message['subject']) ? $message['subject'] : '',
				preg_replace("/\r/", "", $message['body']),
				isset($message['userid']) ? $message['userid'] : (isset($this->auth->id) ? $this->auth->id : 0),
				isset($message['customerid']) ? $message['customerid'] : 0,
				isset($message['mailfrom']) ? $message['mailfrom'] : '',
				isset($message['inreplyto']) ? $message['inreplyto'] : 0,
				isset($message['messageid']) ? $message['messageid'] : $this->lastmessageid,
				isset($message['replyto']) ? $message['replyto'] :
					(isset($message['headers']['Reply-To']) ? $message['headers']['Reply-To'] : ''),
				$headers,
				isset($message['type']) ? $message['type'] : RTMESSAGE_REGULAR,
		));
		$msgid = $this->db->GetLastInsertID('rtmessages');

		$this->SaveTicketMessageAttachments($message['ticketid'], $msgid, $files, true);

		return $msgid;
	}

	public function TicketAdd($ticket, $files = NULL) {
		$this->db->Execute('INSERT INTO rttickets (queueid, customerid, requestor, subject,
				state, owner, createtime, cause, creatorid, source, address_id, nodeid)
				VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?)', array($ticket['queue'],
			$ticket['customerid'],
			$ticket['requestor'],
			$ticket['subject'],
			isset($ticket['owner']) ? $ticket['owner'] : 0,
			isset($ticket['createtime']) ? $ticket['createtime'] : time(),
			isset($ticket['cause']) ? $ticket['cause'] : 0,
			isset($ticket['userid']) ? $ticket['userid'] : (isset($this->auth->id) ? $this->auth->id : 0),
			isset($ticket['source']) ? $ticket['source'] : 0,
			isset($ticket['address_id']) && !empty($ticket['address_id']) ? $ticket['address_id'] : null,
			isset($ticket['nodeid']) && !empty($ticket['nodeid']) ? $ticket['nodeid'] : null,
		));

		$id = $this->db->GetLastInsertID('rttickets');

		$this->lastmessageid = '<msg.' . $ticket['queue'] . '.' . $id . '.' . time() . '@rtsystem.' . gethostname() . '>';

		$this->db->Execute('INSERT INTO rtmessages (ticketid, customerid, createtime,
				subject, body, mailfrom, phonefrom, messageid, replyto)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', array($id,
			$ticket['customerid'],
			isset($ticket['createtime']) ? $ticket['createtime'] : time(),
			$ticket['subject'],
			preg_replace("/\r/", "", $ticket['body']),
			empty($ticket['mailfrom']) ? '' : $ticket['mailfrom'],
			empty($ticket['phonefrom']) ? '' : $ticket['phonefrom'],
			isset($ticket['messageid']) ? $ticket['messageid'] : $this->lastmessageid,
			isset($ticket['replyto']) ? $ticket['replyto'] : '',
			isset($ticket['headers']) ? $ticket['headers'] : '',
		));

		$msgid = $this->db->GetLastInsertID('rtmessages');

		foreach (array_keys($ticket['categories']) as $catid)
			$this->db->Execute('INSERT INTO rtticketcategories (ticketid, categoryid)
				VALUES (?, ?)', array($id, $catid));

		$this->SaveTicketMessageAttachments($id, $msgid, $files);

		return $id;
	}

	public function GetLastMessageID() {
		return $this->lastmessageid;
	}

    public function GetTicketContents($id)
    {
        global $RT_STATES;

        $ticket = $this->db->GetRow('SELECT t.id AS ticketid, t.queueid, rtqueues.name AS queuename,
				t.requestor, t.state, t.owner, t.customerid, t.cause, t.creatorid, c.name AS creator, t.source, '
				. $this->db->Concat('customers.lastname', "' '", 'customers.name') . ' AS customername,
				o.name AS ownername, t.createtime, t.resolvetime, t.subject, t.deleted, t.deltime, t.deluserid,
				t.address_id, va.location, t.nodeid, n.name AS node_name, n.location AS node_location
				FROM rttickets t
				LEFT JOIN rtqueues ON (t.queueid = rtqueues.id)
				LEFT JOIN vusers o ON (t.owner = o.id)
				LEFT JOIN vusers c ON (t.creatorid = c.id)
				LEFT JOIN customers ON (customers.id = t.customerid)
				LEFT JOIN vaddresses va ON va.id = t.address_id
				LEFT JOIN vnodes n ON n.id = t.nodeid
				WHERE 1=1 '
				. (!ConfigHelper::checkPrivilege('helpdesk_advanced_operations') ? ' AND t.deleted = 0' : '')
				. ('AND t.id = ?'), array($id));

        $ticket['categories'] = $this->db->GetAllByKey('SELECT categoryid AS id FROM rtticketcategories WHERE ticketid = ?', 'id', array($id));

        $ticket['messages'] = $this->db->GetAll(
                '(SELECT rtmessages.id AS id, phonefrom, mailfrom, subject, body, createtime, '
                . $this->db->Concat('customers.lastname', "' '", 'customers.name') . ' AS customername,
				userid, vusers.name AS username, customerid, rtmessages.type, rtmessages.deleted, rtmessages.deltime, rtmessages.deluserid
				FROM rtmessages
				LEFT JOIN customers ON (customers.id = customerid)
				LEFT JOIN vusers ON (vusers.id = userid)
				WHERE 1=1 '
				. (!ConfigHelper::checkPrivilege('helpdesk_advanced_operations') ? 'AND rtmessages.deleted = 0' : '')
				. ('AND ticketid = ?)')
				.('ORDER BY createtime ASC'), array($id));

        foreach ($ticket['messages'] as $idx => $message)
            $ticket['messages'][$idx]['attachments'] = $this->db->GetAll('SELECT filename, contenttype FROM rtattachments WHERE messageid = ?', array($message['id']));

        if (!$ticket['customerid'])
            list($ticket['requestor'], $ticket['requestoremail']) = sscanf($ticket['requestor'], "%[^<]<%[^>]");
        else
            list($ticket['requestoremail']) = sscanf($ticket['requestor'], "<%[^>]");
//	$ticket['requestoremail'] = preg_replace('/^.* <(.+@.+)>$/', '\1',$ticket['requestor']);
//      $ticket['requestor'] = str_replace(' <'.$ticket['requestoremail'].'>','',$ticket['requestor']);
        $ticket['status'] = $RT_STATES[$ticket['state']];
        $ticket['uptime'] = uptimef($ticket['resolvetime'] ? $ticket['resolvetime'] - $ticket['createtime'] : time() - $ticket['createtime']);

		if (!empty($ticket['nodeid']) && empty($ticket['node_location'])) {
			$customer_manager = new LMSCustomerManager($this->db, $this->auth, $this->cache, $this->syslog);
			$ticket['node_location'] = $customer_manager->getAddressForCustomerStuff($ticket['customerid']);
		}
        return $ticket;
    }

	public function GetMessage($id) {
		if ($message = $this->db->GetRow('SELECT * FROM rtmessages WHERE id=?', array($id))) {
			$message['attachments'] = $this->db->GetAll('SELECT * FROM rtattachments WHERE messageid = ?', array($id));

			$references = array();
			$reply = $message;
			while ($reply['inreplyto']) {
				if ($reply['messageid'])
					$references[] = $reply['messageid'];
				$reply = $this->db->GetRow('SELECT messageid, inreplyto FROM rtmessages WHERE id = ?',
					array($reply['inreplyto']));
			}
			if ($reply['messageid'])
				$references[] = $reply['messageid'];
			$message['references'] = array_reverse($references);
		}
		return $message;
	}

	public function GetFirstMessage($ticketid) {
		$messageid = $this->db->GetOne('SELECT MIN(id) FROM rtmessages
			WHERE ticketid = ? AND (type = ? OR type = ?)
			GROUP BY ticketid', array($ticketid, RTMESSAGE_REGULAR, RTMESSAGE_NOTE));
		if ($messageid)
			return $this->GetMessage($messageid);
		else
			return null;
	}

	public function GetLastMessage($ticketid) {
		$messageid = $this->db->GetOne('SELECT MAX(id) FROM rtmessages
			WHERE ticketid = ? AND (type = ? OR type = ?)
			GROUP BY ticketid', array($ticketid, RTMESSAGE_REGULAR, RTMESSAGE_NOTE));
		if ($messageid)
			return $this->GetMessage($messageid);
		else
			return null;
	}

    public function TicketChange($ticketid, array $props)
    {
        global $LMS, $RT_STATES, $RT_CAUSE, $RT_SOURCES;

		$ticket = $this->db->GetRow('SELECT owner, queueid, cause, t.state, subject, customerid, requestor, source,
				' . $this->db->GroupConcat('c.categoryid') . ' AS categories, t.address_id, va.location, t.nodeid,
				n.name AS node_name, n.location AS node_location
			FROM rttickets t
			LEFT JOIN rtticketcategories c ON c.ticketid = t.id
			LEFT JOIN customer_addresses ca ON ca.id = t.address_id
			LEFT JOIN vaddresses va ON va.id = ca.address_id
			LEFT JOIN vnodes n ON n.id = t.nodeid
			WHERE t.id=?
			GROUP BY owner, queueid, cause, t.state, subject, customerid, requestor, source, t.address_id, t.nodeid, va.location,
				t.nodeid, n.name, n.location',
			array($ticketid));

        $note = "";
        $type = 0;

        if($ticket['owner'] != $props['owner'] && isset($props['owner'])) {
            $note .= trans('Ticket has been assigned to user $a.', $LMS->GetUserName($props['owner'])) .'<br>';
            $type = $type | RTMESSAGE_OWNER_CHANGE;
        } else
			   $props['owner'] = $ticket['owner'];

        if($ticket['queueid'] != $props['queueid'] && isset($props['queueid'])) {
            $note .= trans('Ticket has been moved from queue $a to queue $b.', $LMS->GetQueueName($ticket['queueid']), $LMS->GetQueueName($props['queueid'])) .'<br>';
            $type = $type | RTMESSAGE_QUEUE_CHANGE;
        } else
			   $props['queueid'] = $ticket['queueid'];

        if($ticket['cause'] != $props['cause'] && isset($props['cause'])) {
            $note .= trans('Ticket\'s cause has been changed from $a to $b.', $RT_CAUSE[$ticket['cause']], $RT_CAUSE[$props['cause']]) .'<br>';
            $type = $type | RTMESSAGE_CAUSE_CHANGE;
        } else
			   $props['cause'] = $ticket['cause'];
        
	if($ticket['source'] != $props['source'] && isset($props['source'])) {
            $note .= trans('Ticket\'s source has been changed from $a to $b.', $RT_SOURCES[$ticket['source']], $RT_SOURCES[$props['source']]) .'<br>';
            $type = $type | RTMESSAGE_SOURCE_CHANGE;
        } else
			   $props['source'] = $ticket['source'];

        if($ticket['state'] != $props['state'] && isset($props['state'])) {
            $note .= trans('Ticket\'s state has been changed from $a to $b.', $RT_STATES[$ticket['state']], $RT_STATES[$props['state']]) .'<br>';
            $type = $type | RTMESSAGE_STATE_CHANGE;
        }else
            $props['state'] = $ticket['state'];

        if($ticket['subject'] != $props['subject'] && isset($props['subject'])) {
            $note .= trans('Ticket\'s subject has been changed from $a to $b.', $ticket['subject'], $props['subject']) .'<br>';
            $type = $type | RTMESSAGE_SUBJECT_CHANGE;
        }else
            $props['subject'] = $ticket['subject'];

        if($ticket['customerid'] != $props['customerid'] && isset($props['customerid'])) {
				if($ticket['customerid'])
            	$note .= trans('Ticket has been moved from customer $a ($b) to customer $c ($d).',
            		$LMS->getCustomerName($ticket['customerid']), $ticket['customerid'], $LMS->getCustomerName($props['customerid']), $props['customerid']) .'<br>';
            else
            	$note .= trans('Ticket has been moved from $a to customer $b ($c).',
            		$ticket['requestor'], $LMS->getCustomerName($props['customerid']), $props['customerid']) .'<br>';
            $type = $type | RTMESSAGE_CUSTOMER_CHANGE;
        }else
            $props['customerid'] = $ticket['customerid'];

		if (isset($props['categories'])) {
			$ticket['categories'] = empty($ticket['categories']) ? array() : explode(',', $ticket['categories']);
			$categories = $this->db->GetAllByKey('SELECT id, name, description
				FROM rtcategories', 'id');

			$categories_added = array_diff($props['categories'], $ticket['categories']);
			$categories_removed = array_diff($ticket['categories'], $props['categories']);
			if (!empty($categories_removed))
				foreach ($categories_removed as $category) {
					$this->db->Execute('DELETE FROM rtticketcategories WHERE ticketid = ? AND categoryid = ?',
						array($id, $category['id']));
					$note .= trans('Category $a has been removed from ticket.', $categories[$category]['name']) . '<br>';
				}
			if (!empty($categories_added))
				foreach ($categories_added as $category) {
					$this->db->Execute('INSERT INTO rtticketcategories (ticketid, categoryid) VALUES (?, ?)',
						array($id, $category));
					$note .= trans('Category $a has been added to ticket.', $categories[$category]['name']) . '<br>';
				}
			$type = $type | RTMESSAGE_CATEGORY_CHANGE;
		}

		if (isset($props['address_id'])) {
			if ($ticket['address_id'] != $props['address_id']) {
				$type = $type | RTMESSAGE_LOCATION_CHANGE;
				$customer_manager = new LMSCustomerManager($this->db, $this->auth, $this->cache, $this->syslog);
				$locations = $customer_manager->getCustomerAddresses($ticket['customerid']);
				$props['location'] = $locations[$props['address_id']]['location'];
				$note .= trans('Ticket\'s location has been changed from $a to $b.',
					$ticket['location'], $props['location']);
			}
		} else
			$props['address_id'] = null;

		if (isset($props['nodeid'])) {
			if ($ticket['nodeid'] != $props['nodeid']) {
				$type = $type | RTMESSAGE_NODE_CHANGE;
				$node_manager = new LMSNodeManager($this->db, $this->auth, $this->cache, $this->syslog);
				$node_locations = $node_manager->GetNodeLocations($ticket['customerid']);
				$props['node_name'] = $node_locations[$props['nodeid']]['name'];
				$props['node_location'] = $node_locations[$props['nodeid']]['location'];
				$note .= trans('Ticket\'s node has been changed from $a ($b) to $c ($d).',
					$ticket['node_name'] . ': ' . $ticket['node_location'], $ticket['nodeid'],
					$props['node_name'] . ': ' . $props['node_location'], $props['nodeid']);
			}
		} else
			$props['nodeid'] = null;

		if ($type) {
			if ($props['state'] == RT_RESOLVED) {
				$resolvetime = time();
				if ($this->db->GetOne('SELECT owner FROM rttickets WHERE id=?', array($ticketid))) {
					$this->db->Execute('UPDATE rttickets SET queueid = ?, owner = ?, cause = ?, state = ?, resolvetime=?, subject = ?,
						customerid = ?, source = ?, address_id = ?, nodeid = ? WHERE id = ?', array(
						$props['queueid'], $props['owner'], $props['cause'], $props['state'], $resolvetime, $props['subject'],
						$props['customerid'], $props['source'], $props['address_id'], $props['nodeid'], $ticketid));
					if (!empty($note))
						$this->db->Execute('INSERT INTO rtmessages (userid, ticketid, type, body, createtime)
							VALUES(?, ?, ?, ?, ?NOW?)', array($this->auth->id, $ticketid, $type, $note));
				} else {
					$this->db->Execute('UPDATE rttickets SET queueid = ?, owner = ?, cause = ?, state = ?, resolvetime = ?, subject = ?,
						customerid = ?, source = ?, address_id = ?, nodeid = ? WHERE id = ?', array(
						$props['queueid'], $this->auth->id, $props['cause'], $props['state'], $resolvetime, $props['subject'],
						$props['customerid'], $props['source'], $props['address_id'], $props['nodeid'], $ticketid));
					if (!empty($note))
						$this->db->Execute('INSERT INTO rtmessages (userid, ticketid, type, body, createtime)
							VALUES(?, ?, ?, ?, ?NOW?)', array($this->auth->id, $ticketid, $type, $note));
				}
			} else {
				$this->db->Execute('UPDATE rttickets SET queueid = ?, owner = ?, cause = ?, state = ?, subject = ?,
					customerid = ?, source = ?, address_id = ?, nodeid = ? WHERE id = ?', array(
					$props['queueid'], $props['owner'], $props['cause'], $props['state'], $props['subject'],
					$props['customerid'], $props['source'], $props['address_id'], $props['nodeid'], $ticketid));
				if (!empty($note))
					$this->db->Execute('INSERT INTO rtmessages (userid, ticketid, type, body, createtime)
						VALUES(?, ?, ?, ?, ?NOW?)', array($this->auth->id, $ticketid, $type, $note));
			}
		}
    }

	public function GetQueueCategories($queueid) {
		return $this->db->GetAllByKey('SELECT c.id, c.name
			FROM rtqueuecategories qc
			JOIN rtcategories c ON c.id = qc.categoryid
			WHERE queueid = ?', 'id', array($queueid));
	}

	public function GetTicketCategories($ticketid) {
		return $this->db->GetAllByKey('SELECT c.id, c.name
			FROM rtticketcategories tc
			JOIN rtcategories c ON c.id = tc.categoryid
			WHERE ticketid = ?', 'id', array($ticketid));
	}
}
