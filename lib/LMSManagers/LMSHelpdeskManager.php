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
 */
class LMSHelpdeskManager extends LMSManager implements LMSHelpdeskManagerInterface
{
	private $lastmessageid = null;

    public function GetQueue($id)
    {
        if ($queue = $this->db->GetRow('SELECT * FROM rtqueues WHERE id=?', array($id))) {
            $queue['verifier'] = $this->db->GetRow('SELECT id,name FROM vusers WHERE id=(SELECT verifierid FROM rtqueues WHERE id=?)', array($id));
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

    public function GetQueueContents($ids, $order = 'createtime,desc', $state = NULL, $priority = NULL, $owner = NULL, $catids = NULL, $removed = NULL, $netdevids = NULL, $netnodeids = NULL, $deadline = NULL) {
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
			case 'queue':
				$sqlord = ' ORDER BY rtqueues.name';
				break;
			case 'priority':
				$sqlord = ' ORDER BY t.priority';
				break;
			case 'deadline':
				$sqlord = ' ORDER BY t.deadline';
				break;
			default:
				$sqlord = ' ORDER BY t.createtime';
				break;
		}

		if (empty($state)) {
			$statefilter = '';
		} elseif (is_array($state)) {
			$statefilter = ' AND t.state IN (' . implode(',', $state) . ')';
		} elseif ($state == -1)
			$statefilter = ' AND t.state <> ' . RT_RESOLVED;
		else
			$statefilter = ' AND t.state = '.$state;

		if (empty($priority)) {
			$priorityfilter = '';
		} elseif (is_array($priority)) {
			$priorityfilter = ' AND t.priority IN (' . implode(',', $priority) . ')';
		} else
			$priorityfilter = ' AND t.priority = '.$priority;

		if (empty($netdevids)) {
                        $netdevidsfilter = '';
		} elseif (is_array($netdevids)) {
                        $netdevidsfilter = ' AND t.netdevid IN (' . implode(',', $netdevids) . ')';
		} else
			$netdevidsfilter = ' AND t.netdevid = '.$netdevids;

		if (empty($netnodeids)) {
                        $netnodeidsfilter = '';
		} elseif (is_array($netnodeids)) {
                        $netnodeidsfilter = ' AND t.netnodeid IN (' . implode(',', $netnodeids) . ')';
		} else
			$netnodeidsfilter = ' AND t.netnodeid = '.$netnodeids;

		if (!ConfigHelper::checkPrivilege('helpdesk_advanced_operations'))
			$removedfilter = ' AND t.deleted = 0';
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

		switch ($owner) {
			case '-1':
				$ownerfilter = '';
				break;
			case '0':
				$ownerfilter = ' AND t.owner IS NULL';
				break;
			case '-2':
				$ownerfilter = ' AND t.owner IS NOT NULL';
				break;
			default:
				$ownerfilter = ' AND t.owner = ' . intval($owner) . ' ';
				break;
		}

	if (!empty($deadline)) {
		switch ($deadline) {
			case '1':
				$deadlinefilter = ' AND t.deadline IS NOT NULL';
				break;
			case '-1':
				$deadlinefilter = ' AND t.deadline IS NULL';
				break;
			case '-2':
				$deadlinefilter = ' AND t.deadline < ?NOW?';
				break;
			default:
				$deadlinefilter = '';
				break;
	}
	} else
		$deadlinefilter = '';

		if ($result = $this->db->GetAll(
			'SELECT DISTINCT t.id, t.customerid, t.address_id, va.name AS vaname, va.city AS vacity, va.street, va.house, va.flat, c.address, c.city, vusers.name AS ownername,
				t.subject, t.state, owner AS ownerid, t.requestor AS req, t.source, t.priority, rtqueues.name, t.requestor_phone, t.requestor_mail, t.deadline, t.requestor_userid,
				CASE WHEN customerid IS NULL THEN t.requestor ELSE '
			. $this->db->Concat('c.lastname', "' '", 'c.name') . ' END AS requestor,
				t.createtime AS createtime, u.name AS creatorname, t.deleted, t.deltime, t.deluserid,
				(CASE WHEN m.lastmodified IS NULL THEN 0 ELSE m.lastmodified END) AS lastmodified,
				eventcountopened, eventcountclosed, delcount, tc2.categories, t.netnodeid, nn.name AS netnode_name, t.netdevid, nd.name AS netdev_name, vb.location as netnode_location
			FROM rttickets t
			LEFT JOIN (SELECT MAX(createtime) AS lastmodified, ticketid FROM rtmessages GROUP BY ticketid) m ON m.ticketid = t.id
			LEFT JOIN rtticketcategories tc ON (t.id = tc.ticketid)
			LEFT JOIN vusers ON (owner = vusers.id)
			LEFT JOIN customeraddressview c ON (t.customerid = c.id)
			LEFT JOIN vusers u ON (t.creatorid = u.id)
			LEFT JOIN rtqueues ON (rtqueues.id = t.queueid)
			LEFT JOIN netnodes nn ON nn.id = t.netnodeid
			LEFT JOIN netdevices nd ON nd.id = t.netdevid
			LEFT JOIN vaddresses as va ON (t.address_id = va.id)
			LEFT JOIN vaddresses as vb ON (nn.address_id = vb.id)
			LEFT JOIN (
				SELECT SUM(CASE WHEN closed = 0 THEN 1 ELSE 0 END) AS eventcountopened,
					SUM(CASE WHEN closed = 1 THEN 1 ELSE 0 END) AS eventcountclosed,
					ticketid FROM events
				WHERE ticketid IS NOT NULL
				GROUP BY ticketid
			) ev ON ev.ticketid = t.id
			LEFT JOIN (
				SELECT COUNT(id) AS delcount, ticketid FROM rtmessages
				WHERE deleted = 1 AND deltime <> 0
				GROUP BY ticketid
			) dm ON dm.ticketid = t.id
			LEFT JOIN (
				SELECT ' . $this->db->GroupConcat('categoryid') . ' AS categories, ticketid
				FROM rtticketcategories
				GROUP BY ticketid
			) tc2 ON tc2.ticketid = t.id
			WHERE 1=1 '
			. (is_array($ids) ? ' AND t.queueid IN (' . implode(',', $ids) . ')' : ($ids != 0 ? ' AND t.queueid = ' . $ids : ''))
			. (is_array($catids) ? ' AND tc.categoryid IN (' . implode(',', $catids) . ')' : ($catids != 0 ? ' AND tc.categoryid = ' . $catids : ''))
			. $statefilter
			. $priorityfilter
			. $ownerfilter
			. $removedfilter
			. $netdevidsfilter
			. $netnodeidsfilter
			. $deadlinefilter
			. ($sqlord != '' ? $sqlord . ' ' . $direction : ''))) {
			$ticket_categories = $this->db->GetAllByKey('SELECT c.id AS categoryid, c.name, c.description, c.style
				FROM rtcategories c
				JOIN rtcategoryusers cu ON cu.categoryid = c.id
				WHERE cu.userid = ?', 'categoryid', array(Auth::GetCurrentUser()));
			foreach ($result as $idx => $ticket) {
				if (ConfigHelper::checkConfig('rt.show_ticket_categories')) {
					$categories = explode(',', $ticket['categories']);
					if (!empty($categories))
						foreach ($categories as $idx2 => $categoryid)
							if (isset($ticket_categories[$categoryid]))
								$categories[$idx2] = $ticket_categories[$categoryid];
							else
								unset($categories[$idx2]);
					$ticket['categories'] = $categories;
				} else
					unset($ticket['categories']);

				if(!empty($ticket['deadline'])) {
					$ticket['deadline_diff'] = $ticket['deadline']-time();
					$days = floor(($ticket['deadline_diff']/86400));
					$hours = round(($ticket['deadline_diff']-($days*86400))/3600);
					$ticket['deadline_days'] = abs($days);
					$ticket['deadline_hours'] = abs($hours);
				}
	
				$result[$idx] = $ticket;
			}
		}

		$result['total'] = empty($result) ? 0 : count($result);
		$result['state'] = $state;
		$result['order'] = $order;
		$result['direction'] = $direction;
		$result['owner'] = $owner;
		$result['removed'] = $removed;
		$result['priority'] = $priority;
		$result['deadline'] = $deadline;

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
				WHERE r.rights <> 0 AND r.userid = ? AND q.deleted = ?' : '') . ' ORDER BY name', array(Auth::GetCurrentUser(), $del))) {
            if ($stats)
                foreach ($result as $idx => $row)
                    foreach ($this->GetQueueStats($row['id']) as $sidx => $row2)
                        $result[$idx][$sidx] = $row2;
        }
        return $result;
    }

	public function GetQueueListByUser($userid, $stats = true) {
		if ($result = $this->db->GetAll('SELECT q.id, name, email, description, newticketsubject, newticketbody,
				newmessagesubject, newmessagebody, resolveticketsubject, resolveticketbody, deleted, deltime, deluserid
				FROM rtqueues q
				JOIN rtrights r ON r.queueid = q.id
				WHERE r.rights <> 0 AND r.userid = ?'
			. (!ConfigHelper::checkPrivilege('helpdesk_advanced_operations') ? ' AND q.deleted = 0' : '') . '
				ORDER BY name', array($userid))) {
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
			WHERE r.rights <> 0 AND r.userid = ? AND q.deleted = ?' : '') . ' ORDER BY name', array(Auth::GetCurrentUser(), $del));
    }

    public function QueueExists($id)
    {
        return ($this->db->GetOne('SELECT * FROM rtqueues WHERE id=?', array($id)) ? TRUE : FALSE);
    }

    public function GetQueueIdByName($queue)
    {
        return $this->db->GetOne('SELECT id FROM rtqueues WHERE name=?', array($queue));
    }

    public function GetQueueVerifier($id)
    {
        return $this->db->GetOne('SELECT verifierid FROM rtqueues WHERE id=?', array($id));
    }

    public function GetQueueNameByTicketId($id)
    {
        return $this->db->GetOne('SELECT name FROM rtqueues '
                . 'WHERE id=(SELECT queueid FROM rttickets WHERE id=?)', array($id));
    }

    public function GetEventsByTicketId($id)
    {
        $events = $this->db->GetAll('SELECT events.id as id, title, description, note, date, begintime, endtime, '
                . 'userid, customerid, private, closed, closeduserid, events.type, ticketid, '
                . ''.$this->db->Concat('customers.name',"' '",'customers.lastname').' AS customername, '
                . ''.$this->db->Concat('users.firstname',"' '",'users.lastname').' AS username, '
                . ''.$this->db->Concat('u.firstname',"' '",'u.lastname').' AS closedusername '
                . 'FROM events '
                . 'LEFT JOIN customers ON (customerid = customers.id) '
                . 'LEFT JOIN users ON (userid = users.id) '
                . 'LEFT JOIN users u ON (closeduserid = u.id) '
                . 'WHERE ticketid = ? ORDER BY events.id ASC', array($id));

	if(is_array($events))
		foreach($events as $idx=>$row)
			$events[$idx][ul] = $this->db->GetAll("SELECT vu.name,userid AS ul FROM eventassignments AS e LEFT JOIN vusers vu ON vu.id = e.userid WHERE eventid = $row[id]");

	return $events;
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
        if ($result = $this->db->GetAll('SELECT id, name, description, style
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
        $categories = $this->GetCategoryListByUser(Auth::GetCurrentUser());
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
						$dirs_to_be_deleted[] = dirname($file['name']);
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
				isset($message['userid']) ? $message['userid'] : Auth::GetCurrentUser(),
				empty($message['customerid']) ? null : $message['customerid'],
				isset($message['mailfrom']) ? $message['mailfrom'] : '',
				isset($message['inreplyto']) ? $message['inreplyto'] : null,
				isset($message['messageid']) ? $message['messageid'] : $this->lastmessageid,
				isset($message['replyto']) ? $message['replyto'] :
					(isset($message['headers']['Reply-To']) ? $message['headers']['Reply-To'] : ''),
				$headers,
				isset($message['type']) ? $message['type'] : RTMESSAGE_REGULAR,
		));
		$msgid = $this->db->GetLastInsertID('rtmessages');

		$this->SaveTicketMessageAttachments($message['ticketid'], $msgid, $files);

		return $msgid;
	}

	public function TicketAdd($ticket, $files = NULL) {
		$this->db->Execute('INSERT INTO rttickets (queueid, customerid, requestor, requestor_mail, requestor_phone,
			requestor_userid, subject, state, owner, createtime, cause, creatorid, source, priority, address_id, nodeid, netnodeid, netdevid, verifierid, deadline)
				VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($ticket['queue'],
			empty($ticket['customerid']) ? null : $ticket['customerid'],
			$ticket['requestor'],
			$ticket['requestor_mail'],
			$ticket['requestor_phone'],
			isset($ticket['requestor_userid']) ? $ticket['requestor_userid'] : null,
			$ticket['subject'],
			isset($ticket['owner']) && !empty($ticket['owner']) ? $ticket['owner'] : null,
			isset($ticket['createtime']) ? $ticket['createtime'] : time(),
			isset($ticket['cause']) ? $ticket['cause'] : 0,
			isset($ticket['userid']) ? $ticket['userid'] : Auth::GetCurrentUser(),
			isset($ticket['source']) ? $ticket['source'] : 0,
			isset($ticket['priority']) ? $ticket['priority'] : 0,
			isset($ticket['address_id']) && !empty($ticket['address_id']) ? $ticket['address_id'] : null,
			isset($ticket['nodeid']) && !empty($ticket['nodeid']) ? $ticket['nodeid'] : null,
			isset($ticket['netnodeid']) && !empty($ticket['netnodeid']) ? $ticket['netnodeid'] : null,
			isset($ticket['netdevid']) && !empty($ticket['netdevid']) ? $ticket['netdevid'] : null,
			isset($ticket['verifierid']) && !empty($ticket['verifierid']) ? $ticket['verifierid'] : null,
			isset($ticket['deadline']) && !empty($ticket['deadline']) ? $ticket['deadline'] : null,
		));

		$id = $this->db->GetLastInsertID('rttickets');

		$this->lastmessageid = '<msg.' . $ticket['queue'] . '.' . $id . '.' . time() . '@rtsystem.' . gethostname() . '>';

		$this->db->Execute('INSERT INTO rtmessages (ticketid, customerid, createtime,
				subject, body, mailfrom, phonefrom, messageid, replyto)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', array($id,
			empty($ticket['customerid']) ? null : $ticket['customerid'],
			isset($ticket['createtime']) ? $ticket['createtime'] : time(),
			$ticket['subject'],
			preg_replace("/\r/", "", $ticket['body']),
			empty($ticket['mailfrom']) ? '' : $ticket['mailfrom'],
			empty($ticket['phonefrom']) ? '' : $ticket['phonefrom'],
			isset($ticket['messageid']) ? $ticket['messageid'] : $this->lastmessageid,
			isset($ticket['replyto']) ? $ticket['replyto'] : '',
			isset($ticket['headers']) ? $ticket['headers'] : '',
		));
		
		if($ticket['note']) {
	                $this->db->Execute('INSERT INTO rtmessages (ticketid, customerid, createtime,
                        subject, body, mailfrom, phonefrom, messageid, replyto, type)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)', array($id,
                        empty($ticket['customerid']) ? null : $ticket['customerid'],
                        isset($ticket['createtime']) ? $ticket['createtime'] : time(),
                        $ticket['subject'],
                        preg_replace("/\r/", "", $ticket['note']),
                        empty($ticket['mailfrom']) ? '' : $ticket['mailfrom'],
                        empty($ticket['phonefrom']) ? '' : $ticket['phonefrom'],
                        isset($ticket['messageid']) ? $ticket['messageid'] : $this->lastmessageid,
                        isset($ticket['replyto']) ? $ticket['replyto'] : '',
                        isset($ticket['headers']) ? $ticket['headers'] : '',
                ));
		}

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

        $ticket = $this->db->GetRow('SELECT t.id AS ticketid, t.queueid, rtqueues.name AS queuename, t.requestor, t.requestor_phone, t.requestor_mail,
				t.requestor_userid, d.name AS requestor_username, t.state, t.owner, t.customerid, t.cause, t.creatorid, c.name AS creator,
				t.source, t.priority, '
				. $this->db->Concat('customers.lastname', "' '", 'customers.name') . ' AS customername,
				o.name AS ownername, t.createtime, t.resolvetime, t.subject, t.deleted, t.deltime, t.deluserid,
				t.address_id, va.location, t.nodeid, n.name AS node_name, n.location AS node_location,
				t.netnodeid, nn.name AS netnode_name, t.netdevid, nd.name AS netdev_name,
				t.verifierid, e.name AS verifier_username, t.deadline, openeventcount
				FROM rttickets t
				LEFT JOIN rtqueues ON (t.queueid = rtqueues.id)
				LEFT JOIN vusers o ON (t.owner = o.id)
				LEFT JOIN vusers c ON (t.creatorid = c.id)
				LEFT JOIN vusers d ON (t.requestor_userid = d.id)
				LEFT JOIN customers ON (customers.id = t.customerid)
				LEFT JOIN vaddresses va ON va.id = t.address_id
				LEFT JOIN vnodes n ON n.id = t.nodeid
				LEFT JOIN netnodes nn ON nn.id = t.netnodeid
				LEFT JOIN netdevices nd ON nd.id = t.netdevid
				LEFT JOIN vusers e ON (t.verifierid = e.id)
				LEFT JOIN (
					SELECT SUM(CASE WHEN closed !=1 THEN 1 ELSE 0 END) AS openeventcount,
					ticketid FROM events WHERE ticketid IS NOT NULL GROUP BY ticketid
				) ev ON ev.ticketid = t.id
				WHERE 1=1 '
				. (!ConfigHelper::checkPrivilege('helpdesk_advanced_operations') ? ' AND t.deleted = 0' : '')
				. (' AND t.id = ?'), array($id));

        $ticket['categories'] = $this->db->GetAllByKey('SELECT categoryid AS id, c.name
								FROM rtticketcategories tc
								JOIN rtcategories c ON c.id = tc.categoryid
								WHERE ticketid = ?', 'id', array($id));
		$ticket['categorynames'] = empty($ticket['categories']) ? array() : array_map(function($elem) {
				return $elem['name'];
			}, $ticket['categories']);

        $ticket['messages'] = $this->db->GetAll(
                '(SELECT rtmessages.id AS id, phonefrom, mailfrom, subject, body, createtime, '
                . $this->db->Concat('customers.lastname', "' '", 'customers.name') . ' AS customername,
				userid, vusers.name AS username, customerid, rtmessages.type, rtmessages.deleted, rtmessages.deltime, rtmessages.deluserid
				FROM rtmessages
				LEFT JOIN customers ON (customers.id = customerid)
				LEFT JOIN vusers ON (vusers.id = userid)
				WHERE 1=1'
				. (!ConfigHelper::checkPrivilege('helpdesk_advanced_operations') ? ' AND rtmessages.deleted = 0' : '')
				. (' AND ticketid = ?)')
				.(' ORDER BY createtime ASC, rtmessages.id'), array($id));

        foreach ($ticket['messages'] as $idx => $message)
            $ticket['messages'][$idx]['attachments'] = $this->db->GetAll('SELECT filename, contenttype FROM rtattachments WHERE messageid = ?', array($message['id']));

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
        global $LMS, $RT_STATES, $RT_CAUSE, $RT_SOURCES, $RT_PRIORITIES;

		$ticket = $this->db->GetRow('SELECT owner, queueid, cause, t.state, subject, customerid, requestor, source, priority,
				' . $this->db->GroupConcat('c.categoryid') . ' AS categories, t.address_id, va.location, t.nodeid,
				n.name AS node_name, n.location AS node_location, t.netnodeid, t.netdevid, t.verifierid, t.deadline
			FROM rttickets t
			LEFT JOIN rtticketcategories c ON c.ticketid = t.id
			LEFT JOIN vaddresses va ON va.id = t.address_id
			LEFT JOIN vnodes n ON n.id = t.nodeid
			WHERE t.id=?
			AND c.categoryid IN (
				SELECT categoryid FROM rtcategoryusers
				WHERE userid = ?
			)
			GROUP BY owner, queueid, cause, t.state, subject, customerid, requestor, source, priority, t.address_id, t.nodeid, va.location,
				t.nodeid, n.name, n.location, t.netnodeid, t.netdevid, t.verifierid, t.deadline',
			array($ticketid, Auth::GetCurrentUser()));

        $type = 0;
		$notes = array();

        if($ticket['owner'] != $props['owner'] && isset($props['owner'])) {
            $notes[] = trans('Ticket has been assigned to user $a.', $LMS->GetUserName($props['owner']));
            $type = $type | RTMESSAGE_OWNER_CHANGE;
        } else
			   $props['owner'] = $ticket['owner'];

        if($ticket['queueid'] != $props['queueid'] && isset($props['queueid'])) {
            $notes[] = trans('Ticket has been moved from queue $a to queue $b.', $LMS->GetQueueName($ticket['queueid']), $LMS->GetQueueName($props['queueid']));
            $type = $type | RTMESSAGE_QUEUE_CHANGE;
        } else
			   $props['queueid'] = $ticket['queueid'];

        if($ticket['cause'] != $props['cause'] && isset($props['cause'])) {
            $notes[] = trans('Ticket\'s cause has been changed from $a to $b.', $RT_CAUSE[$ticket['cause']], $RT_CAUSE[$props['cause']]);
            $type = $type | RTMESSAGE_CAUSE_CHANGE;
        } else
			   $props['cause'] = $ticket['cause'];
        
		if($ticket['source'] != $props['source'] && isset($props['source'])) {
            $notes[] = trans('Ticket\'s source has been changed from $a to $b.', $RT_SOURCES[$ticket['source']], $RT_SOURCES[$props['source']]);
            $type = $type | RTMESSAGE_SOURCE_CHANGE;
        } else
			   $props['source'] = $ticket['source'];

        if($ticket['priority'] != $props['priority'] && isset($props['priority'])) {
            $notes[] = trans('Ticket\'s priority has been changed from $a to $b.', $RT_PRIORITIES[$ticket['priority']], $RT_PRIORITIES[$props['priority']]);
            $type = $type | RTMESSAGE_PRIORITY_CHANGE;
        } else
            $props['priority'] = $ticket['priority'];

        if($ticket['state'] != $props['state'] && isset($props['state'])) {
            $notes[] = trans('Ticket\'s state has been changed from $a to $b.', $RT_STATES[$ticket['state']]['label'], $RT_STATES[$props['state']]['label']);
            $type = $type | RTMESSAGE_STATE_CHANGE;
        }else
            $props['state'] = $ticket['state'];

        if($ticket['subject'] != $props['subject'] && isset($props['subject'])) {
            $notes[] = trans('Ticket\'s subject has been changed from $a to $b.', $ticket['subject'], $props['subject']);
            $type = $type | RTMESSAGE_SUBJECT_CHANGE;
        }else
            $props['subject'] = $ticket['subject'];

        if($ticket['netnodeid'] != $props['netnodeid'] && isset($props['netnodeid'])) {
            $notes[] = trans('Ticket\'s netnode assignments has been changed from $a to $b.', $ticket['netnodeid'], $props['netnodeid']);
            $type = $type | RTMESSAGE_NETNODE_CHANGE;
        }else
            $props['netnodeid'] = $ticket['netnodeid'];

		if($ticket['netdevid'] != $props['netdevid'] && isset($props['netdevid'])) {
            $notes[] = trans('Ticket\'s netdev assignments has been changed from $a to $b.', $ticket['netdevid'], $props['netdevid']);
            $type = $type | RTMESSAGE_NETDEV_CHANGE;
        }else
            $props['netdevid'] = $ticket['netdevid'];

        if($ticket['verifierid'] != $props['verifierid'] && isset($props['verifierid'])) {
            $notes[] = trans('User $a has been set as verifier to ticket.', $LMS->GetUserName($props['verifierid']));
            $type = $type | RTMESSAGE_VERIFIER_CHANGE;
        } else
            $props['verifierid'] = $ticket['verifierid'];

        if($ticket['deadline'] != $props['deadline'] && isset($props['deadline'])) {
            $notes[] = trans('Ticket deadline has been set to $a:', $props['deadline']);
            $type = $type | RTMESSAGE_DEADLINE_CHANGE;
            $props['deadline'] = datetime_to_timestamp($props['deadline']);
        } else
        	$props['deadline'] = $ticket['deadline'];

        if($ticket['customerid'] != $props['customerid'] && isset($props['customerid'])) {
				if($ticket['customerid'])
            	$notes[] = trans('Ticket has been moved from customer $a ($b) to customer $c ($d).',
            		$LMS->getCustomerName($ticket['customerid']), $ticket['customerid'], $LMS->getCustomerName($props['customerid']), $props['customerid']);
            else
            	$notes[] = trans('Ticket has been moved from $a to customer $b ($c).',
            		$ticket['requestor'], $LMS->getCustomerName($props['customerid']), $props['customerid']);
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
						array($ticketid, $category));
					$notes[] = trans('Category $a has been removed from ticket.', $categories[$category]['name']);
				}
			if (!empty($categories_added))
				foreach ($categories_added as $category) {
					$this->db->Execute('INSERT INTO rtticketcategories (ticketid, categoryid) VALUES (?, ?)',
						array($ticketid, $category));
					$notes[] = trans('Category $a has been added to ticket.', $categories[$category]['name']);
				}
			$type = $type | RTMESSAGE_CATEGORY_CHANGE;
		}

		if (isset($props['address_id'])) {
			if ($ticket['address_id'] != $props['address_id']) {
				$type = $type | RTMESSAGE_LOCATION_CHANGE;
				$customer_manager = new LMSCustomerManager($this->db, $this->auth, $this->cache, $this->syslog);
				$locations = $customer_manager->getCustomerAddresses($ticket['customerid']);
				$props['location'] = $locations[$props['address_id']]['location'];
				$notes[] = trans('Ticket\'s location has been changed from $a to $b.',
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
				$notes[] = trans('Ticket\'s node has been changed from $a ($b) to $c ($d).',
					$ticket['node_name'] . ': ' . $ticket['node_location'], $ticket['nodeid'],
					$props['node_name'] . ': ' . $props['node_location'], $props['nodeid']);
			}
		} else
			$props['nodeid'] = null;

		if ($type) {
			$note = implode('<br>', $notes);
			if ($props['state'] == RT_RESOLVED) {
				$resolvetime = time();
				if ($this->db->GetOne('SELECT owner FROM rttickets WHERE id=?', array($ticketid))) {
					$this->db->Execute('UPDATE rttickets SET queueid = ?, owner = ?, cause = ?, state = ?, resolvetime=?, subject = ?,
						customerid = ?, source = ?, priority = ?, address_id = ?, nodeid = ?, netnodeid = ?, netdevid = ?, verifierid = ?, deadline = ? WHERE id = ?', array(
						$props['queueid'], $props['owner'], $props['cause'], $props['state'], $resolvetime, $props['subject'],
						$props['customerid'], $props['source'], $props['priority'], $props['address_id'], $props['nodeid'], $props['netnodeid'], $props['netdevid'],
						$props['verifierid'], $props['deadline'], $ticketid));
					if (!empty($note))
						$this->db->Execute('INSERT INTO rtmessages (userid, ticketid, type, body, createtime)
							VALUES(?, ?, ?, ?, ?NOW?)', array(Auth::GetCurrentUser(), $ticketid, $type, $note));
				} else {
					$this->db->Execute('UPDATE rttickets SET queueid = ?, owner = ?, cause = ?, state = ?, resolvetime = ?, subject = ?,
						customerid = ?, source = ?, priority = ?, address_id = ?, nodeid = ?, netnodeid = ?, netdevid = ?, verifierid = ?, deadline = ? WHERE id = ?', array(
						$props['queueid'], Auth::GetCurrentUser(), $props['cause'], $props['state'], $resolvetime, $props['subject'],
						$props['customerid'], $props['source'], $props['priority'], $props['address_id'], $props['nodeid'], $props['netnodeid'], $props['netdevid'],
						$props['verifierid'], $props['deadline'], $ticketid));
					if (!empty($note))
						$this->db->Execute('INSERT INTO rtmessages (userid, ticketid, type, body, createtime)
							VALUES(?, ?, ?, ?, ?NOW?)', array(Auth::GetCurrentUser(), $ticketid, $type, $note));
				}
			} else {
				$this->db->Execute('UPDATE rttickets SET queueid = ?, owner = ?, cause = ?, state = ?, subject = ?,
					customerid = ?, source = ?, priority = ?, address_id = ?, nodeid = ?, netnodeid = ?, netdevid = ?, verifierid = ?, deadline = ? WHERE id = ?', array(
					$props['queueid'], $props['owner'], $props['cause'], $props['state'], $props['subject'],
					$props['customerid'], $props['source'], $props['priority'], $props['address_id'], $props['nodeid'], $props['netnodeid'], $props['netdevid'],
					$props['verifierid'], $props['deadline'], $ticketid));
				if (!empty($note))
					$this->db->Execute('INSERT INTO rtmessages (userid, ticketid, type, body, createtime)
						VALUES(?, ?, ?, ?, ?NOW?)', array(Auth::GetCurrentUser(), $ticketid, $type, $note));
			}
		}
    }

	public function GetQueueCategories($queueid) {
		return $this->db->GetAllByKey('SELECT c.id, c.name
			FROM rtqueuecategories qc
			JOIN rtcategories c ON c.id = qc.categoryid
			WHERE queueid = ?', 'id', array($queueid));
	}

	public function ReplaceNotificationSymbols($text, array $params) {
		$text = str_replace('%tid', sprintf("%06d", $params['id']), $text);
		$text = str_replace('%queue', $params['queue'], $text);
		$text = str_replace('%cid', isset($params['customerid']) ? sprintf("%04d", $params['customerid']) : '', $text);
		$text = str_replace('%status', $params['status']['label'], $text);
		$text = str_replace('%cat', implode(' ; ', $params['categories']), $text);
		$text = str_replace('%subject', $params['subject'], $text);
		$text = str_replace('%body', $params['body'], $text);
		$text = str_replace('%priority', $params['priority'], $text);
		$text = (isset($params['deadline']) && !empty($params['deadline'])) ? str_replace('%deadline', $params['deadline'], $text) : str_replace('%deadline', '-', $text);
		$url = (isset($params['url']) && !empty($params['url']) ? $params['url']
			: 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '') . '://'
				. $_SERVER['HTTP_HOST']
				. substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1))
				. '?m=rtticketview&id=' . $params['id']
				. (isset($params['messageid']) ? '#rtmessage-' . $params['messageid'] : '');
		$text = str_replace('%url', $url, $text);
		$text = str_replace('%customerinfo', isset($params['customerinfo']) ? $params['customerinfo'] : '', $text);

		return $text;
	}

	public function ReplaceNotificationCustomerSymbols($text, array $params) {
		$customer_manager = new LMSCustomerManager($this->db, $this->auth, $this->cache, $this->syslog);
		$locations = $customer_manager->getCustomerAddresses($params['customerid']);
		$address_id = $this->db->GetOne('SELECT address_id FROM rttickets WHERE id = ?', array($params['id']));

		$text = str_replace('%custname', $params['customer']['customername'], $text);
		$text = str_replace('%cid', sprintf("%04d", $params['customerid']), $text);
		$text = str_replace('%address', (empty($address_id) ? $params['customer']['address'] . ', ' . $params['customer']['zip'] . ' ' . $params['customer']['city']
			: $locations[$address_id]['location']), $text);
		$text = str_replace('%phone', isset($params['phones']) && !empty($params['phones'])
			? implode(', ', $params['phones']) : '-', $text);
		$text = str_replace('%email', isset($params['emails']) && !empty($params['emails'])
		? implode(', ', $params['emails']) : '-', $text);

		return $text;
	}

	public function NotifyUsers(array $params) {
		global $LMS;

		$notify_author = ConfigHelper::checkConfig('phpui.helpdesk_author_notify');
		$userid = Auth::GetCurrentUser();
		$sms_service = ConfigHelper::getConfig('sms.service');

		$args = array(
			'queue' => $params['queue'],
		);
		if (!$notify_author && $userid)
			$args['user'] = $userid;

		// send email
		$args['type'] = MSG_MAIL;
		if ($recipients = $this->db->GetCol('SELECT DISTINCT email
			FROM users, rtrights
			WHERE users.id=userid AND queueid = ? AND email != \'\'
				AND (rtrights.rights & 8) > 0 AND deleted = 0'
				. (!isset($args['user']) || $notify_author ? '' : ' AND users.id <> ?')
				. ' AND (ntype & ?) > 0',
			array_values($args))) {

			if (isset($params['oldqueue'])) {
				$oldrecipients = $this->db->GetCol('SELECT DISTINCT email
					FROM users, rtrights
					WHERE users.id=userid AND queueid = ? AND email != \'\'
						AND (rtrights.rights & 8) > 0 AND deleted = 0
						AND (ntype & ?) > 0',
					array($params['oldqueue'], MSG_MAIL));
				if (!empty($oldrecipients))
					$recipients = array_diff($recipients, $oldrecipients);
			}

			foreach ($recipients as $email) {
				$params['mail_headers']['To'] = '<' . $email . '>';
				$LMS->SendMail($email, $params['mail_headers'], $params['mail_body']);
			}
		}

		// send sms
		$args['type'] = MSG_SMS;
		if (!empty($sms_service) && ($recipients = $this->db->GetCol('SELECT DISTINCT phone
			FROM users, rtrights
				WHERE users.id=userid AND queueid = ? AND phone != \'\'
					AND (rtrights.rights & 8) > 0 AND deleted = 0'
					. (!isset($args['user']) || $notify_author ? '' : ' AND users.id <> ?')
					. ' AND (ntype & ?) > 0',
				array_values($args)))) {

			if (isset($params['oldqueue'])) {
				$oldrecipients = $this->db->GetCol('SELECT DISTINCT phone
					FROM users, rtrights
					WHERE users.id=userid AND queueid = ? AND phone != \'\'
						AND (rtrights.rights & 8) > 0 AND deleted = 0
						AND (ntype & ?) > 0',
					array($params['oldqueue'], MSG_SMS));
				if (!empty($oldrecipients))
					$recipients = array_diff($recipients, $oldrecipients);
			}

			foreach ($recipients as $phone)
				$LMS->SendSMS($phone, $params['sms_body']);
		}
	}
}
