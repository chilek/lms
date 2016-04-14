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
 * LMSHelpdeskManager
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class LMSHelpdeskManager extends LMSManager implements LMSHelpdeskManagerInterface
{

    public function GetQueue($id)
    {
        if ($queue = $this->db->GetRow('SELECT * FROM rtqueues WHERE id=?', array($id))) {
            $users = $this->db->GetAll('SELECT id, name FROM users WHERE deleted=0');
            foreach ($users as $user) {
                $user['rights'] = $this->GetUserRightsRT($user['id'], $id);
                $queue['rights'][] = $user;
            }
            return $queue;
        } else
            return NULL;
    }

    public function GetQueueContents($ids, $order = 'createtime,desc', $state = NULL, $owner = 0, $catids = NULL)
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

        if ($result = $this->db->GetAll(
                'SELECT DISTINCT t.id, t.customerid, c.address, users.name AS ownername,
			    t.subject, state, owner AS ownerid, t.requestor AS req,
			    CASE WHEN customerid = 0 THEN t.requestor ELSE '
                . $this->db->Concat('c.lastname', "' '", 'c.name') . ' END AS requestor, 
			    t.createtime AS createtime, u.name AS creatorname,
				(CASE WHEN m.lastmodified IS NULL THEN (CASE WHEN n.lastmodified IS NULL THEN 0 ELSE n.lastmodified END) ELSE
					(CASE WHEN n.lastmodified IS NULL THEN m.lastmodified ELSE 
						(CASE WHEN m.lastmodified > n.lastmodified THEN m.lastmodified ELSE n.lastmodified END)
					END)
				END) AS lastmodified
		    FROM rttickets t 
		    LEFT JOIN (SELECT MAX(createtime) AS lastmodified, ticketid FROM rtmessages GROUP BY ticketid) m ON m.ticketid = t.id
		    LEFT JOIN (SELECT MAX(createtime) AS lastmodified, ticketid FROM rtnotes GROUP BY ticketid) n ON n.ticketid = t.id
		    LEFT JOIN rtticketcategories tc ON (t.id = tc.ticketid)
		    LEFT JOIN users ON (owner = users.id)
		    LEFT JOIN customeraddressview c ON (t.customerid = c.id)
		    LEFT JOIN users u ON (t.creatorid = u.id)
		    WHERE 1=1 '
                . (is_array($ids) ? ' AND t.queueid IN (' . implode(',', $ids) . ')' : ($ids != 0 ? ' AND t.queueid = ' . $ids : ''))
                . (is_array($catids) ? ' AND tc.categoryid IN (' . implode(',', $catids) . ')' : ($catids != 0 ? ' AND tc.categoryid = ' . $catids : ''))
                . $statefilter
                . ($owner ? ' AND t.owner = ' . intval($owner) : '')
                . ($sqlord != '' ? $sqlord . ' ' . $direction : ''))) {
            foreach ($result as $idx => $ticket) {
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
        if ($result = $this->db->GetAll('SELECT q.id, name, email, description 
				FROM rtqueues q'
                . (!ConfigHelper::checkConfig('privileges.superuser') ? ' JOIN rtrights r ON r.queueid = q.id
					WHERE r.rights <> 0 AND r.userid = ?' : '') . ' ORDER BY name', array($this->auth->id))) {
            if ($stats)
                foreach ($result as $idx => $row)
                    foreach ($this->GetQueueStats($row['id']) as $sidx => $row)
                        $result[$idx][$sidx] = $row;
        }
        return $result;
    }

    public function GetQueueNames()
    {
        return $this->db->GetAll('SELECT q.id, name FROM rtqueues q'
                        . (!ConfigHelper::checkConfig('privileges.superuser') ? ' JOIN rtrights r ON r.queueid = q.id 
				WHERE r.rights <> 0 AND r.userid = ?' : '') . ' ORDER BY name', array($this->auth->id));
    }

    public function QueueExists($id)
    {
        return ($this->db->GetOne('SELECT * FROM rtqueues WHERE id=?', array($id)) ? TRUE : FALSE);
    }

    public function GetQueueIdByName($queue)
    {
        return $this->db->GetOne('SELECT id FROM rtqueues WHERE name=?', array($queue));
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

        return $stats;
    }

    public function GetCategory($id)
    {
        if ($category = $this->db->GetRow('SELECT * FROM rtcategories WHERE id=?', array($id))) {
            $users = $this->db->GetAll('SELECT id, name FROM users WHERE deleted=0 ORDER BY login asc');
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
                    foreach ($this->GetCategoryStats($row['id']) as $sidx => $row)
                        $result[$idx][$sidx] = $row;
            foreach ($result as $idx => $category)
                $result[$idx]['owners'] = $this->db->GetAll('SELECT u.id, name FROM rtcategoryusers cu 
				LEFT JOIN users u ON cu.userid = u.id 
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

    public function TicketAdd($ticket, $files = NULL)
    {
        $this->db->Execute('INSERT INTO rttickets (queueid, customerid, requestor, subject, 
				state, owner, createtime, cause, creatorid)
				VALUES (?, ?, ?, ?, 0, ?, ?NOW?, ?, ?)', array($ticket['queue'],
            $ticket['customerid'],
            $ticket['requestor'],
            $ticket['subject'],
            $ticket['owner'],
            isset($ticket['cause']) ? $ticket['cause'] : 0,
            isset($this->auth->id) ? $this->auth->id : 0
        ));

        $id = $this->db->GetLastInsertID('rttickets');

        $this->db->Execute('INSERT INTO rtmessages (ticketid, customerid, createtime, 
				subject, body, mailfrom)
				VALUES (?, ?, ?NOW?, ?, ?, ?)', array($id,
            $ticket['customerid'],
            $ticket['subject'],
            preg_replace("/\r/", "", $ticket['body']),
            $ticket['mailfrom']));

		$msgid = $this->db->GetLastInsertID('rtmessages');

        foreach (array_keys($ticket['categories']) as $catid)
            $this->db->Execute('INSERT INTO rtticketcategories (ticketid, categoryid) 
				VALUES (?, ?)', array($id, $catid));

        if (!empty($files) && ConfigHelper::getConfig('rt.mail_dir')) {
            $dir = ConfigHelper::getConfig('rt.mail_dir') . sprintf('/%06d/%06d', $id, $msgid);
            @mkdir(ConfigHelper::getConfig('rt.mail_dir') . sprintf('/%06d', $id), 0700);
            @mkdir($dir, 0700);
            foreach ($files as $file) {
                $newfile = $dir . '/' . $file['name'];
                if (@rename($file['tmp_name'], $newfile))
                    $this->db->Execute('INSERT INTO rtattachments (messageid, filename, contenttype) 
							VALUES (?,?,?)', array($msgid, $file['name'], $file['type']));
            }
        }

        return $id;
    }

    public function GetTicketContents($id)
    {
        global $RT_STATES;

        $ticket = $this->db->GetRow('SELECT t.id AS ticketid, t.queueid, rtqueues.name AS queuename, 
				    t.requestor, t.state, t.owner, t.customerid, t.cause, t.creatorid, c.name AS creator, '
                . $this->db->Concat('customers.lastname', "' '", 'customers.name') . ' AS customername, 
				    o.name AS ownername, t.createtime, t.resolvetime, t.subject
				FROM rttickets t
				LEFT JOIN rtqueues ON (t.queueid = rtqueues.id)
				LEFT JOIN users o ON (t.owner = o.id)
				LEFT JOIN users c ON (t.creatorid = c.id)
				LEFT JOIN customers ON (customers.id = t.customerid)
				WHERE t.id = ?', array($id));

        $ticket['categories'] = $this->db->GetAllByKey('SELECT categoryid AS id FROM rtticketcategories WHERE ticketid = ?', 'id', array($id));

        $ticket['messages'] = $this->db->GetAll(
                '(SELECT rtmessages.id AS id, mailfrom, subject, body, createtime, '
                . $this->db->Concat('customers.lastname', "' '", 'customers.name') . ' AS customername, 
				    userid, users.name AS username, customerid, NULL AS type
				FROM rtmessages
				LEFT JOIN customers ON (customers.id = customerid)
				LEFT JOIN users ON (users.id = userid)
				WHERE ticketid = ?)
				UNION
				(SELECT rtnotes.id AS id, NULL, NULL, body, createtime, NULL,
				    userid, users.name AS username, NULL, rtnotes.type
				FROM rtnotes
				LEFT JOIN users ON (users.id = userid)
				WHERE ticketid = ?)
				ORDER BY createtime ASC', array($id, $id));

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

        return $ticket;
    }

    public function GetMessage($id)
    {
        if ($message = $this->db->GetRow('SELECT * FROM rtmessages WHERE id=?', array($id)))
            $message['attachments'] = $this->db->GetAll('SELECT * FROM rtattachments WHERE messageid = ?', array($id));
        return $message;
    }

    public function TicketChange($ticketid, array $props)
    {
        global $LMS, $RT_STATES, $RT_CAUSE;

        $ticket = $this->db->GetRow('SELECT owner, queueid, cause, state, subject, customerid, requestor FROM rttickets WHERE id=?', array($ticketid));
        $note = "";
        $type = 0;

        if($ticket['owner'] != $props['owner'] && isset($props['owner'])) {
            $note .= trans('Ticket has been assigned to user $a.', $LMS->GetUserName($props['owner'])) .'<br>';
            $type = $type | RTNOTE_OWNER_CHANGE;
        } else 
			   $props['owner'] = $ticket['owner'];
			   
        if($ticket['queueid'] != $props['queueid'] && isset($props['queueid'])) {
            $note .= trans('Ticket has been moved from queue $a to queue $b.', $LMS->GetQueueName($ticket['queueid']), $LMS->GetQueueName($props['queueid'])) .'<br>';
            $type = $type | RTNOTE_QUEUE_CHANGE;
        } else 
			   $props['queueid'] = $ticket['queueid'];
        
        if($ticket['cause'] != $props['cause'] && isset($props['cause'])) {
            $note .= trans('Ticket\'s cause has been changed from $a to $b.', $RT_CAUSE[$ticket['cause']], $RT_CAUSE[$props['cause']]) .'<br>';
            $type = $type | RTNOTE_CAUSE_CHANGE;
        } else
			   $props['cause'] = $ticket['cause'];         
        
        if($ticket['state'] != $props['state'] && isset($props['state'])) {
            $note .= trans('Ticket\'s state has been changed from $a to $b.', $RT_STATES[$ticket['state']], $RT_STATES[$props['state']]) .'<br>';
            $type = $type | RTNOTE_STATE_CHANGE;
        }else
            $props['state'] = $ticket['state'];

        if($ticket['subject'] != $props['subject'] && isset($props['subject'])) {
            $note .= trans('Ticket\'s subject has been changed from $a to $b.', $ticket['subject'], $props['subject']) .'<br>';
            $type = $type | RTNOTE_SUBJECT_CHANGE;
        }else
            $props['subject'] = $ticket['subject'];

        if($ticket['customerid'] != $props['customerid'] && isset($props['customerid'])) {
				if($ticket['customerid'])
            	$note .= trans('Ticket has been moved from customer $a ($b) to customer $c ($d).', 
            		$LMS->getCustomerName($ticket['customerid']), $ticket['customerid'], $LMS->getCustomerName($props['customerid']), $props['customerid']) .'<br>';
            else 
            	$note .= trans('Ticket has been moved from $a to customer $b ($c).', 
            		$ticket['requestor'], $LMS->getCustomerName($props['customerid']), $props['customerid']) .'<br>';            
            $type = $type | RTNOTE_CUSTOMER_CHANGE;
        }else
            $props['customerid'] = $ticket['customerid'];

        if($type){
        		($state == 2 ? $resolvetime = time() : $resolvetime = 0);
        		
        		if($props['state'] == RT_RESOLVED) {
        		    if ($this->db->GetOne('SELECT owner FROM rttickets WHERE id=?', array($ticketid))){
                    $this->db->Execute('UPDATE rttickets SET queueid = ?, owner = ?, cause = ?, state = ?, resolvetime=?, subject = ?, customerid = ? WHERE id = ?', array(
            	         $props['queueid'], $props['owner'], $props['cause'], $props['state'], $resolvetime, $props['subject'], $props['customerid'], $ticketid));
                    $this->db->Execute('INSERT INTO rtnotes (userid, ticketid, type, body, createtime)
                        VALUES(?, ?, ?, ?, ?NOW?)', array($this->auth->id, $ticketid, $type, $note));      		        	
        		    } else {
                    $this->db->Execute('UPDATE rttickets SET queueid = ?, owner = ?, cause = ?, state = ?, resolvetime = ?, subject = ?, customerid = ?  WHERE id = ?', array(
            	         $props['queueid'], $this->auth->id, $props['cause'], $props['state'], $resolvetime, $props['subject'], $props['customerid'], $ticketid));
                    $this->db->Execute('INSERT INTO rtnotes (userid, ticketid, type, body, createtime)
                        VALUES(?, ?, ?, ?, ?NOW?)', array($this->auth->id, $ticketid, $type, $note));        		    	
					 }        		    	    
        		} else {
                $this->db->Execute('UPDATE rttickets SET queueid = ?, owner = ?, cause = ?, state = ?, subject = ?, customerid = ?  WHERE id = ?', array(
            	     $props['queueid'], $props['owner'], $props['cause'], $props['state'], $props['subject'], $props['customerid'], $ticketid));
                $this->db->Execute('INSERT INTO rtnotes (userid, ticketid, type, body, createtime)
                    VALUES(?, ?, ?, ?, ?NOW?)', array($this->auth->id, $ticketid, $type, $note));
            }
        }
    }
}
