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
 * LMSHelpdeskManager
 *
 */
class LMSHelpdeskManager extends LMSManager implements LMSHelpdeskManagerInterface
{
    private $lastmessageid = null;

    public function GetQueue($id, $short = false)
    {
        if ($queue = $this->db->GetRow('SELECT * FROM rtqueues WHERE id=?', array($id))) {
            if (!$short) {
                $queue['verifier'] = $this->db->GetRow(
                    'SELECT id, name, rname, login FROM vusers WHERE id=(SELECT verifierid FROM rtqueues WHERE id=?)',
                    array($id)
                );
                $users = $this->db->GetAll('SELECT id, name, rname, login FROM vusers WHERE deleted=0 ORDER BY rname');
                foreach ($users as $user) {
                    $user['rights'] = $this->GetUserRightsRT($user['id'], $id);
                    $queue['rights'][] = $user;
                }
                $queue['categories'] = $this->GetQueueCategories($id);
            }
            return $queue;
        } else {
            return null;
        }
    }

    /**
     * @param array $params associative array of parameters described below:
     *      ids - queue identifiers (default: null = any), array() or single integer value
     *      state - ticket states (default: null = any), -1 = unresolved, -2 = any, -3 = new or open
     *          array() of integer values or single integer value
     *      priority - ticket priorities (default: null = any),
     *          array() of integer values or single integer value
     *      source - ticket source (default: 0 = all),
     *          -1 = unknown/other
     *           0 = all
     *      owner - ticket owner (default: null = any),
     *          array() or single integer value
     *          -1 = without owner,
     *          -2 = with,
     *          -3 = without owner or owner set to current user,
     *          all = filter off
     *      catids - ticket categories (default: null = any, -1 = without category),
     *          array() of integer values or single integer value
     *      removed - ticket removal flag (default: null = any),
     *          -1 = without removal flag,
     *          1 = with removal flag set,
     *      netdevids - ticket network devices (default: null = any),
     *          array() of integer values or single integer value,
     *      netnodeids - ticket network nodes (default: null = any),
     *          array() of integer values or single integer value,
     *          -1 - without netnode set,
     *          -2 - with netnode set,
     *      deadline - ticket deadline (default: null = any),
     *          1 - with deadline set,
     *          -1 - without deadline set,
     *          -2 - with expired deadline,
     *          -3 - less than 7 days to deadline,
     *          -4 - with not expired deadline
     *      serviceids - ticket services (default: null = any),
     *          array() of integer values or single integer value,
     *      typeids - ticket types (default: null = any),
     *          array() of integer values or single integer value,
     *      unread - ticket unread flag (default: null or < 0 = any),
     *          0 - read tickets,
     *          1 - unread tickets,
     *      parentids - ticket parentid
     *          null (default: null = any),
     *          -1 - show only parent tickets
     *          single integer value - all tickets with the same parent id
     *      verifierids - ticket verifier (default: null = any/none)
     *          array() of integer values,
     *          all - filter is off
     *          -1 - without verifier set
     *          -2 - with verifier set
     *      rights - tickets from queues with particular permissions (default:null whitch means tickets from all queues),
     *      projectids - ticket investment projects (default: null = any/none)
     *          array() of integer values,
     *      cid - ticket customerid (default: null = any/none, integer value)
     *      subject - ticket subject (default: null = any/none, text value)
     *      fromdate - ticket creation date was from  (default: null = any/none, integer value)
     *      todate - ticket creation date was to  (default: null = any/none, integer value)
     *      count - count records only or return selected record interval
     *          true - count only,
     *          false - get records,
     *      offset - first returned record (null = 0),
     *      limit - returned record count (null = unlimited),
     *      order - returned records order (default: createtime,desc)
     *          can contain field_name,order pairs,
     *          supported field names:
     *          ticketid, subject, requestor, owner, lastmodified, creator, queue, priority, deadline, service,
     *              type, createtime,
     *          supported orders:
     *          asc = ascending, desc = descending
     *      short - returned only ticket data (default: null, type: boolean),
     * @return mixed
     */
    public function GetQueueContents(array $params)
    {
        $userid = Auth::GetCurrentUser();
        extract($params);
        foreach (array('ids', 'state', 'priority', 'source', 'owner', 'catids', 'removed', 'netdevids', 'netnodeids', 'deadline',
            'serviceids', 'typeids', 'unread', 'parentids', 'verifierids', 'rights', 'projectids', 'cid', 'subject', 'fromdate', 'todate', 'short', 'watching') as $var) {
            if (!isset(${$var})) {
                ${$var} = null;
            }
        }
        if (!isset($order) || !$order) {
            $order = 'createtime,desc';
        }
        if (!isset($rights)) {
            $rights = 0;
        } else {
            $rights = intval($rights);
        }
        if (!isset($count)) {
            $count = false;
        }

        [$order, $direction] = sscanf($order, '%[^,],%s');

        ($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

        switch ($order) {
            case 'ticketid':
                $sqlord = ' ORDER BY t.id';
                break;
            case 'subject':
                $sqlord = ' ORDER BY t.subject';
                break;
            case 'requestor':
                $sqlord = ' ORDER BY t.requestor';
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
            case 'service':
                $sqlord = ' ORDER BY t.service';
                break;
            case 'type':
                $sqlord = ' ORDER BY t.type';
                break;
            default:
                $sqlord = ' ORDER BY t.createtime';
                break;
        }

        if (empty($state) || $state == -2) {
            $statefilter = '';
        } elseif (is_array($state)) {
            $statefilter = ' AND t.state IN (' . implode(',', $state) . ')';
        } elseif ($state == -3) {
            $statefilter = ' AND (t.state = ' . RT_NEW . ' OR t.state = ' . RT_OPEN .')';
        } elseif ($state == -1) {
            $statefilter = ' AND t.state <> ' . RT_RESOLVED;
        } else {
            $statefilter = ' AND t.state = '.$state;
        }

        if (empty($priority)) {
            $priorityfilter = '';
        } elseif (is_array($priority)) {
            if (in_array('-101', $priority)) {
                $priorityfilter = ' AND (t.priority IN (' . implode(',', $priority) . ') OR t.priority IS NULL)';
            } else {
                $priorityfilter = ' AND t.priority IN (' . implode(',', $priority) . ')';
            }
        } else {
            $priorityfilter = ' AND t.priority = '.$priority;
        }

        if (empty($source) || intval($source) == -1) {
            $sourcefilter = '';
        } else {
            $sourcefilter = ' AND t.source = ' . $source;
        }

        if (empty($netdevids)) {
            $netdevidsfilter = '';
        } elseif (is_array($netdevids)) {
            $netdevidsfilter = ' AND t.netdevid IN (' . implode(',', $netdevids) . ')';
        } else {
            $netdevidsfilter = ' AND t.netdevid = ' . $netdevids;
        }

        if (empty($netnodeids)) {
            $netnodeidsfilter = '';
        } elseif (is_array($netnodeids) && in_array(-1, $netnodeids)) {
            $netnodeidsfilter = ' AND t.netnodeid IS NULL';
        } elseif (is_array($netnodeids) && in_array(-2, $netnodeids)) {
            $netnodeidsfilter = ' AND t.netnodeid IS NOT NULL';
        } elseif (is_array($netnodeids)) {
            $netnodeidsfilter = ' AND t.netnodeid IN (' . implode(',', $netnodeids) . ')';
        } else {
            $netnodeidsfilter = ' AND t.netnodeid = ' . $netnodeids;
        }

        if (empty($serviceids)) {
            $serviceidsfilter = '';
        } elseif (is_array($serviceids)) {
            $serviceidsfilter = ' AND t.service IN (' . implode(',', $serviceids) . ')';
        } else {
            $serviceidsfilter = ' AND t.service = '.$serviceids;
        }

        if (empty($typeids)) {
            $typeidsfilter = '';
        } elseif (is_array($typeids)) {
            $typeidsfilter = ' AND t.type IN (' . implode(',', $typeids) . ')';
        } else {
            $typeidsfilter = ' AND t.type = '.$typeids;
        }

        switch ($verifierids) {
            case '-2':
                $verifieridsfilter = ' AND t.verifierid IS NOT NULL';
                break;
            case '-1':
                $verifieridsfilter = ' AND t.verifierid IS NULL';
                break;
            case 'all':
                $verifieridsfilter = '';
                break;
            default:
                if (!empty($verifierids)) {
                    if (is_array($verifierids)) {
                        $verifieridsfilter = ' AND t.verifierid IN (' . implode(',', $verifierids) . ') ';
                    } else {
                        $verifieridsfilter = ' AND t.verifierid = ' . $verifierids;
                    }
                } else {
                    $verifieridsfilter = '';
                }
                break;
        }

        if (empty($projectids)) {
            $projectidsfilter = '';
        } elseif (is_array($projectids)) {
            $projectidsfilter = ' AND t.invprojectid IN (' . implode(',', $projectids) . ')';
        } else {
            $projectidsfilter = ' AND t.invprojectid = '.$projectids;
        }

        if (empty($cid)) {
            $cidfilter = '';
        } else {
            $cidfilter = ' AND t.customerid = ' . $cid;
        }

        if (empty($subject)) {
            $subjectfilter = '';
        } else {
            $subjectfilter = " AND t.subject ?LIKE? '%" . $subject . "%'";
        }

        if (empty($fromdate)) {
            $fromdatefilter = '';
        } else {
            $fromdatefilter = ' AND t.createtime >= ' . $fromdate;
        }

        if (empty($todate)) {
            $todatefilter = '';
        } else {
            $todatefilter = ' AND t.createtime <= ' . $todate;
        }

        if ($watching == '1') {
                $watchingfilter = ' AND tw.userid IS NOT NULL';
        } else {
                $watchingfilter = '';
        }

        if (!ConfigHelper::checkPrivilege('helpdesk_advanced_operations')) {
            $removedfilter = ' AND t.deleted = 0';
        } else {
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

        if (!empty($owner) && !is_array($owner)) {
            $owner = array($owner);
        }

        if (!empty($owner) && !in_array('all', $owner)) {
            if (in_array('-3', $owner)) {
                $ownerfilter = ' AND (t.owner IS NULL OR t.owner = ' . $userid . ')';
            } elseif (in_array('-2', $owner)) {
                $ownerfilter = ' AND t.owner IS NOT NULL';
            } else {
                if (in_array('-1', $owner)) {
                    if (count($owner) == 1) {
                        $ownerfilter = ' AND t.owner IS NULL';
                    } else {
                        $ownerfilter = ' AND (t.owner IS NULL OR t.owner IN (' . implode(',', $owner) . ')) ';
                    }
                } else {
                    $ownerfilter = ' AND t.owner IN (' . implode(',', $owner) . ') ';
                }
            }
        } else {
            $ownerfilter = '';
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
                case '-3':
                    $deadlinefilter = ' AND t.deadline > ?NOW? AND t.deadline - 7 * 86400 < ?NOW?';
                    break;
                case '-4':
                    $deadlinefilter = ' AND t.deadline IS NOT NULL AND t.deadline > ?NOW?';
                    break;
                default:
                    $deadlinefilter = '';
                    break;
            }
        } else {
            $deadlinefilter = '';
        }

        if (isset($unread) && $unread >= 0) {
            switch ($unread) {
                case 0:
                    $unreadfilter = ' AND (lv.vdate >= t.modtime OR t.state = ' . RT_RESOLVED . ')';
                    break;
                case 1:
                    $unreadfilter = ' AND (t.state <> ' . RT_RESOLVED . ' AND (lv.ticketid IS NULL OR lv.vdate < t.modtime))';
                    break;
                default:
                    $unreadfilter = '';
            }
        } else {
            $unreadfilter = '';
        }

        if (!empty($parentids)) {
            if (!is_array($parentids)) {
                $parentids = array($parentids);
            }

            if (in_array(-1, $parentids)) {
                $parentfilter = ' AND t.parentid IS NULL';
            } else {
                $parentfilter = ' AND t.parentid IN (' . implode(',', $parentids) . ')';
            }
        } else {
            $parentfilter = '';
        }

        if (!empty($catids) && !in_array('all', $catids)) {
            if (in_array(-1, $catids)) {
                if (count($catids) > 1) {
                    $categoriesfilter = ' AND (tc.categoryid IN (' . implode(',', $catids) . ') OR tc.categoryid IS NULL)';
                } else {
                    $categoriesfilter = ' AND tc.categoryid IS NULL';
                }
            } else {
                $categoriesfilter = ' AND tc.categoryid IN (' . implode(',', $catids) . ')';
            }
        } else {
            $categoriesfilter = '';
        }

        $user_permission_checks = ConfigHelper::checkConfig('rt.additional_user_permission_checks', ConfigHelper::checkConfig('phpui.helpdesk_additional_user_permission_checks'));
        $allow_empty_categories = ConfigHelper::checkConfig('rt.allow_empty_categories', ConfigHelper::checkConfig('phpui.helpdesk_allow_empty_categories'));

        $qids = null;
        if (!empty($ids)) {
            $qids = $ids;
            if (!is_array($ids) && $ids != 0) {
                $qids = array($ids);
            }
        }

        $all_queues = false;
        if ($qids) {
            $queues = $this->db->GetCol('SELECT queueid FROM rtrights WHERE userid=?', array($userid));
            if ($queues && count($queues) == count($qids)) {
                $all_queues = true;
            }
        }

        if ($count) {
            return $this->db->GetOne('SELECT COUNT(DISTINCT t.id)
				FROM rttickets t
				LEFT JOIN rtticketcategories tc ON (t.id = tc.ticketid)
				LEFT JOIN rtticketlastview lv ON lv.ticketid = t.id AND lv.userid = ?
				LEFT JOIN rtticketwatchers tw ON (t.id = tw.ticketid) AND tw.userid = ?
				WHERE 1=1 '
                . ($rights ? ' AND (t.queueid IN (
						SELECT q.id FROM rtqueues q
						JOIN rtrights r ON r.queueid = q.id
						WHERE r.userid = ' . $userid . ' AND r.rights & ' . $rights . ' =  ' . $rights . '
					)'. ($user_permission_checks ? ' OR t.owner = ' . $userid . ' OR t.verifierid = ' . $userid : '')
                    . ') AND (tc.categoryid IN (
								SELECT categoryid
								FROM rtcategoryusers WHERE userid = ' . $userid . '
							)'
                        . ($allow_empty_categories
                            ? ' OR NOT EXISTS (
									SELECT 1 FROM rtticketcategories tc2
									WHERE tc2.ticketid = t.id
								)'
                            : '')
                        . ')'
                    : '')
                . ($qids ? ' AND (t.queueid IN (' . implode(',', $qids) . ')'
                            . ($all_queues && $user_permission_checks ? ' OR t.owner = ' . $userid . ' OR t.verifierid = ' . $userid : '') . ')'
                    : ($user_permission_checks ? ' AND (t.queueid IS NOT NULL OR t.owner = ' . $userid . ' OR t.verifierid = ' . $userid . ')' : ''))
                . $categoriesfilter
                . $unreadfilter
                . $parentfilter
                . $statefilter
                . $priorityfilter
                . $sourcefilter
                . $ownerfilter
                . $removedfilter
                . $netdevidsfilter
                . $netnodeidsfilter
                . $deadlinefilter
                . $serviceidsfilter
                . $verifieridsfilter
                . $projectidsfilter
                . $cidfilter
                . $subjectfilter
                . $fromdatefilter
                . $todatefilter
                . $watchingfilter
                . $typeidsfilter, array($userid, $userid));
        }

        if ($result = $this->db->GetAll(
            'SELECT DISTINCT t.id, t.customerid, t.address_id, va.name AS vaname, va.city AS vacity, va.street, va.house, va.flat, c.address, c.city, vusers.name AS ownername,
				t.subject, t.state, owner AS ownerid, t.requestor AS req, t.source, t.priority, rtqueues.name,'
                . $this->db->Concat('c.lastname', "' '", 'c.name') . ' AS customername,
				t.requestor_phone, t.requestor_mail, t.deadline, t.requestor_userid, rq.name AS requestor_name,
				t.createtime AS createtime, u.name AS creatorname, t.deleted, t.deltime, t.deluserid,
				t.modtime AS lastmodified, vi.name AS verifiername, vi.id AS verifierid, tw.userid,
				eventcountopened, eventcountclosed, delcount, tc2.categories, t.netnodeid, nn.name AS netnode_name, t.netdevid, 
				nd.name AS netdev_name, vb.location as netnode_location, t.service, t.type, t.resolvetime,
				(CASE WHEN t.state <> ' . RT_RESOLVED . ' AND (lv.ticketid IS NULL OR lv.vdate < t.modtime) THEN 1 ELSE 0 END) AS unread,
				(CASE WHEN t.state <> ' . RT_RESOLVED . ' THEN m3.firstunread ELSE 0 END) as firstunread,
				(CASE WHEN t.id = tw.ticketid AND tw.userid = ? THEN 1 ELSE 0 END) as watching,
				ti.imagecount
			FROM rttickets t
			LEFT JOIN rtticketcategories tc ON (t.id = tc.ticketid)
			LEFT JOIN vusers ON (owner = vusers.id)
			LEFT JOIN vusers AS vi ON (verifierid = vi.id)
			LEFT JOIN customeraddressview c ON (t.customerid = c.id)
			LEFT JOIN vusers u ON (t.creatorid = u.id)
			LEFT JOIN vusers rq ON (t.requestor_userid = rq.id)
			LEFT JOIN rtticketwatchers tw ON (t.id = tw.ticketid) AND tw.userid = ?
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
			LEFT JOIN rtticketlastview lv ON lv.ticketid = t.id AND lv.userid = ?
			LEFT JOIN (
				SELECT m4.ticketid, MIN(m4.id) AS firstunread FROM rtmessages m4
				JOIN rttickets t2 ON t2.id = m4.ticketid
				LEFT JOIN rtticketlastview lv2 ON lv2.ticketid = m4.ticketid AND lv2.userid = ?
				WHERE 1 = 1'
                    . str_replace('t.', 't2.', $qids ? ' AND (t.queueid IN (' . implode(',', $qids) . ')'
                            . ($all_queues && $user_permission_checks ? ' OR t.owner = ' . $userid . ' OR t.verifierid = ' . $userid : '') . ')'
                        : ($user_permission_checks ? ' AND (t.queueid IS NOT NULL OR t.owner = ' . $userid . ' OR t.verifierid = ' . $userid . ')' : ''))
                    . str_replace('t.', 't2.', $statefilter)
                    . str_replace('t.', 't2.', $removedfilter)
                    . ' AND lv2.vdate < t2.modtime
				GROUP BY m4.ticketid
			) m3 ON m3.ticketid = t.id
            LEFT JOIN (
                SELECT ticketid, COUNT(*) AS imagecount
                FROM rtattachments a
                JOIN rtmessages ON rtmessages.id = a.messageid
                WHERE a.contenttype ?LIKE? ?
                    AND a.cid IS NULL
                GROUP BY ticketid
            ) ti ON ti.ticketid = t.id
			WHERE 1=1 '
            . ($rights ? ' AND (t.queueid IN (
					SELECT q.id FROM rtqueues q
					JOIN rtrights r ON r.queueid = q.id
					WHERE r.userid = ' . $userid . ' AND r.rights & ' . $rights . ' = ' . $rights . '
				)' . ($user_permission_checks ? ' OR t.owner = ' . $userid . ' OR t.verifierid = ' . $userid : '')
                . ') AND (tc.categoryid IN (
							SELECT categoryid
							FROM rtcategoryusers
							WHERE userid = ' . $userid . '
						)'
                    . ($allow_empty_categories
                        ? ' OR NOT EXISTS (
								SELECT 1 FROM rtticketcategories tc3
								WHERE tc3.ticketid = t.id
							)'
                        : '')
                    . ')'
                : '')
            . ($qids ? ' AND (t.queueid IN (' . implode(',', $qids) . ')'
                    . ($all_queues && $user_permission_checks ? ' OR t.owner = ' . $userid . ' OR t.verifierid = ' . $userid : '') . ')'
                : ($user_permission_checks ? ' AND (t.queueid IS NOT NULL OR t.owner = ' . $userid . ' OR t.verifierid = ' . $userid . ')' : ''))
            . $categoriesfilter
            . $unreadfilter
            . $parentfilter
            . $statefilter
            . $priorityfilter
            . $sourcefilter
            . $ownerfilter
            . $removedfilter
            . $netdevidsfilter
            . $netnodeidsfilter
            . $deadlinefilter
            . $serviceidsfilter
            . $verifieridsfilter
            . $projectidsfilter
            . $cidfilter
            . $subjectfilter
            . $fromdatefilter
            . $todatefilter
            . $watchingfilter
            . $typeidsfilter
            . ($sqlord != '' ? $sqlord . ' ' . $direction : '')
            . (isset($limit) ? ' LIMIT ' . $limit : '')
            . (isset($offset) ? ' OFFSET ' . $offset : ''),
            array($userid, $userid, $userid, $userid, 'image/%')
        )) {
            $ticket_categories = $this->GetUserCategories($userid);
            foreach ($result as &$ticket) {
                if (!empty($ticket['categories']) && ConfigHelper::checkConfig('rt.show_ticket_categories')) {
                    $categories = explode(',', $ticket['categories']);
                    if (!empty($categories)) {
                        foreach ($categories as $idx2 => $categoryid) {
                            if (isset($ticket_categories[$categoryid])) {
                                $categories[$idx2] = $ticket_categories[$categoryid];
                            } else {
                                unset($categories[$idx2]);
                            }
                        }
                    }
                    $ticket['categories'] = $categories;
                } else {
                    unset($ticket['categories']);
                }

                if (!empty($ticket['deadline'])) {
                    $ticket['deadline_diff'] = $ticket['deadline']-time();
                    $days = floor(($ticket['deadline_diff']/86400));
                    $hours = round(($ticket['deadline_diff']-($days*86400))/3600);
                    $ticket['deadline_days'] = abs($days);
                    $ticket['deadline_hours'] = abs($hours);
                }
            }
            unset($ticket);
        }

        if (!$short) {
            $result['total'] = empty($result) ? 0 : count($result);
            $result['state'] = $state;
            $result['order'] = $order;
            $result['direction'] = $direction;
            $result['owner'] = $owner;
            $result['removed'] = $removed;
            $result['priority'] = $priority;
            $result['source'] = $source;
            $result['deadline'] = $deadline;
            $result['service'] = $serviceids;
            $result['type'] = $typeids;
            $result['unread'] = $unread;
            $result['rights'] = $rights;
            $result['fromdate'] = $fromdate;
            $result['todate'] = $todate;
            $result['watching'] = $watching;
        }

        return $result;
    }

    public function GetUserRightsRT($user, $queue, $ticket = null)
    {
        if (!$queue && $ticket) {
            if (!($queue = $this->cache->getCache('rttickets', $ticket, 'queueid'))) {
                $queue = $this->db->GetOne('SELECT queueid FROM rttickets WHERE id=?', array($ticket));
            }
        }

        if (!$queue) {
            return 0;
        }

        $rights = $this->db->GetOne('SELECT rights FROM rtrights WHERE userid=? AND queueid=?', array($user, $queue));

        return ($rights ?: 0);
    }

    /**
     * @param array $params associative array of parameters described below:
     *      stats - if true queue stats should be obtained (default: true),
     *      only_accessible - if true only queues with access permissions are listed (default: true),
     *      deleted - if true deleted queues will be obtained (default: true)
     */
    public function GetQueueList(array $params)
    {
        extract($params);

        if (!isset($stats)) {
            $stats = true;
        }
        if (!isset($only_accessible)) {
            $only_accessible = true;
        }
        if (!isset($deleted)) {
            $deleted = true;
        }

        $userid = Auth::GetCurrentUser();
        if ($result = $this->db->GetAll('SELECT q.id, name, email, description, newticketsubject, newticketbody,
				newmessagesubject, newmessagebody, resolveticketsubject, resolveticketbody,
				newticketsmsbody, newmessagesmsbody, resolveticketsmsbody,
				deleted, deltime, deluserid
				FROM rtqueues q
				' . ((ConfigHelper::checkPrivilege('full_access') && $only_accessible)
                    || !ConfigHelper::checkPrivilege('full_access') ? ' JOIN rtrights r ON r.queueid = q.id' : '')
                . ' WHERE ' . (!$deleted ? 'q.deleted = 0' : (ConfigHelper::checkPrivilege('helpdesk_advanced_operations') ? '1=1' : 'q.deleted = 0'))
                . ((ConfigHelper::checkPrivilege('full_access') && $only_accessible)
                    || !ConfigHelper::checkPrivilege('full_access') ? ' AND r.rights <> 0 AND r.userid = ' . $userid : '')
                . ' ORDER BY name')) {
            if ($stats) {
                foreach ($result as &$row) {
                    $stats = $this->GetQueueStats($row['id'], $deleted_tickets);
                    if ($stats) {
                        $row = array_merge($row, $stats);
                    }
                }
                unset($row);
            }
        }
        return $result;
    }

    public function GetQueueNames()
    {
        if (!ConfigHelper::checkPrivilege('helpdesk_advanced_operations')) {
            $join = 'JOIN rtrights r ON r.queueid = q.id WHERE r.rights <> 0 AND r.userid = ? AND q.deleted = ?';
            $args = array(Auth::GetCurrentUser(), 0);
        } else {
            $join = '';
            $args = array();
        }

        return $this->db->GetAll('SELECT q.id, name FROM rtqueues q ' . $join  . ' ORDER BY name', $args);
    }

    public function GetMyQueues()
    {
        return $this->db->GetCol('SELECT q.id FROM rtqueues q
			JOIN rtrights r ON r.queueid = q.id AND r.userid = ?
			WHERE q.deleted = 0', array(Auth::GetCurrentUser()));
    }

    public function QueueExists($id)
    {
        return (bool)$this->db->GetOne('SELECT * FROM rtqueues WHERE id=?', array($id));
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
                . 'userid, customerid, private, closed, closeddate, closeduserid, events.type, ticketid, va.location, '
                . $this->db->Concat('customers.lastname', "' '", 'customers.name').' AS customername, '
                . $this->db->Concat('users.firstname', "' '", 'users.lastname').' AS username, '
                . $this->db->Concat('u.firstname', "' '", 'u.lastname').' AS closedusername, vn.name AS node_name, '
                . $this->db->Concat('c.city', "', '", 'c.address') . ' AS customerlocation, vn.location AS node_location '
                . 'FROM events '
                . 'LEFT JOIN customers ON (customerid = customers.id) '
                . 'LEFT JOIN users ON (userid = users.id) '
                . 'LEFT JOIN users u ON (closeduserid = u.id) '
                . 'LEFT JOIN vaddresses va ON va.id = events.address_id '
                . 'LEFT JOIN vnodes as vn ON (nodeid = vn.id) '
                . 'LEFT JOIN customerview c ON (events.customerid = c.id) '
                . 'WHERE ticketid = ? ORDER BY events.id ASC', array($id));

        if (is_array($events)) {
            foreach ($events as $idx => $row) {
                $events[$idx]['userlist'] = $this->db->GetAll("SELECT vu.name,userid AS ul FROM eventassignments AS e LEFT JOIN vusers vu ON vu.id = e.userid WHERE eventid = $row[id]");
            }
        }

        return $events;
    }

    public function GetQueueName($id)
    {
        return $this->db->GetOne('SELECT name FROM rtqueues WHERE id=?', array($id));
    }

    public function GetFavoriteQueues()
    {
        $fav_queues = Utils::filterIntegers(explode(',', ConfigHelper::getConfig('rt.favorite_queues', '', true)));
        $favorite_queues = array();
        foreach ($fav_queues as $fq) {
            if ($this->QueueExists($fq) && !empty($fq)) {
                $favorite_queues[$fq] = $this->GetQueueName($fq);
            }
        }
        return $favorite_queues;
    }

    public function GetQueueEmail($id)
    {
        return $this->db->GetOne('SELECT email FROM rtqueues WHERE id=?', array($id));
    }

    public function GetQueueStats($id, $deleted_tickets = true)
    {
        $stats = null;

        $userid = Auth::GetCurrentUser();
        $user_permission_checks = ConfigHelper::checkConfig('rt.additional_user_permission_checks', ConfigHelper::checkConfig('phpui.helpdesk_additional_user_permission_checks'));

        if ($result = $this->db->GetAll(
            'SELECT t.state, COUNT(t.state) AS scount
			FROM rttickets t
			' . ($user_permission_checks ? 'LEFT JOIN rtrights r ON r.queueid = t.queueid AND r.userid = ' . $userid . ' AND r.rights <> 0' : '') . '
			WHERE t.queueid = ?' . ($user_permission_checks ? ' AND (r.queueid IS NOT NULL OR t.owner = ' . $userid . ' OR t.verifierid = ' . $userid . ')' : '')
            . (empty($deleted_tickets) ? ' AND t.deleted = 0' : '')
            . ' GROUP BY t.state
			ORDER BY t.state ASC',
            array($id)
        )) {
            foreach ($result as $row) {
                $stats[$row['state']] = $row['scount'];
            }
            foreach (array('new', 'open', 'resolved', 'dead') as $idx => $value) {
                $stats[$value] = $stats[$idx] ?? 0;
            }

            $result = $this->db->GetRow(
                'SELECT MAX(t.modtime) AS lastticket,
				SUM(CASE WHEN deleted = 1 THEN 1 ELSE 0 END) AS delcount,
				SUM(CASE WHEN state <> ? THEN 1 ELSE 0 END) AS unresolved,
				SUM(CASE WHEN priority = ? AND state <> ? THEN 1 ELSE 0 END) AS critical,
				SUM(CASE WHEN state <> ? AND (lv.ticketid IS NULL OR lv.vdate < t.modtime) THEN 1 ELSE 0 END) AS unread
				FROM rttickets t
				LEFT JOIN rtticketlastview lv ON lv.ticketid = t.id AND lv.userid = ?
				' . ($user_permission_checks ? 'LEFT JOIN rtrights r ON r.queueid = t.queueid AND r.userid = ' . $userid . ' AND r.rights <> 0' : '') . '
				WHERE t.queueid = ?' . ($user_permission_checks ? ' AND (r.queueid IS NOT NULL OR t.owner = ' . $userid . ' OR t.verifierid = ' . $userid . ')' : '')
                    . (empty($deleted_tickets) ? ' AND t.deleted = 0' : ''),
                array(RT_RESOLVED, RT_PRIORITY_CRITICAL, RT_RESOLVED, RT_RESOLVED,
                    $userid,
                $id)
            );
            if (!empty($result)) {
                $stats = array_merge($stats, $result);
            }
        }

        return $stats;
    }

    public function GetCategory($id)
    {
        if ($category = $this->db->GetRow('SELECT * FROM rtcategories WHERE id=?', array($id))) {
            $cssProperties = Utils::parseCssProperties($category['style']);
            $category['background-style'] = $cssProperties['background-color'] ?? '#ffffff';
            $category['text-style'] = $cssProperties['color'] ?? '#000000';
            $users = $this->db->GetAll('SELECT id, name, rname, login FROM vusers WHERE deleted=0 ORDER BY rname');
            foreach ($users as $user) {
                $user['owner'] = $this->db->GetOne('SELECT 1 FROM rtcategoryusers WHERE userid = ? AND categoryid = ?', array($user['id'], $id));
                $category['owners'][] = $user;
            }
            return $category;
        } else {
            return null;
        }
    }

    public function GetUserRightsToCategory($user, $category, $ticket = null)
    {
        if (!$category && $ticket) {
            if (!($category = $this->cache->getCache('rttickets', $ticket, 'categoryid'))) {
                $category = $this->db->GetCol('SELECT categoryid FROM rtticketcategories WHERE ticketid=?', array($ticket));
            }
        }

        // grant access to ticket when no categories assigned to this ticket
        if (!$category) {
            return 1;
        }

        if (!$this->CategoryExists($category)) {
            return true;
        }

        $owner = $this->db->GetOne('SELECT 1 FROM rtcategoryusers WHERE userid = ? AND categoryid ' .
                (is_array($category) ? 'IN (' . implode(',', $category) . ')' : '= ' . $category), array($user));

        return ($owner === '1');
    }

    public function GetCategoryList($stats = true)
    {
        if ($result = $this->db->GetAll('SELECT id, name, description, style
				FROM rtcategories ORDER BY name')) {
            if ($stats) {
                foreach ($result as $idx => $row) {
                    foreach ($this->GetCategoryStats($row['id']) as $sidx => $row2) {
                        $result[$idx][$sidx] = $row2;
                    }
                }
            }
            foreach ($result as $idx => $category) {
                $result[$idx]['owners'] = $this->db->GetAll('SELECT u.id, name FROM rtcategoryusers cu
				LEFT JOIN vusers u ON cu.userid = u.id
				WHERE categoryid = ?', array($category['id']));
            }
        }
        return $result;
    }

    public function GetCategoryStats($id)
    {
        if ($result = $this->db->GetAll('SELECT state, COUNT(state) AS scount
			FROM rttickets LEFT JOIN rtticketcategories ON rttickets.id = rtticketcategories.ticketid
			WHERE rtticketcategories.categoryid = ? GROUP BY state ORDER BY state ASC', array($id))) {
            foreach ($result as $row) {
                $stats[$row['state']] = $row['scount'];
            }
            foreach (array('new', 'open', 'resolved', 'dead') as $idx => $value) {
                $stats[$value] = $stats[$idx] ?? 0;
            }
        }
        $stats['lastticket'] = $this->db->GetOne('SELECT createtime FROM rttickets
			LEFT JOIN rtticketcategories ON rttickets.id = rtticketcategories.ticketid
			WHERE rtticketcategories.categoryid = ? ORDER BY createtime DESC', array($id));

        return $stats;
    }

    public function CategoryExists($id)
    {
        return (bool)$this->db->GetOne('SELECT * FROM rtcategories WHERE id=?', array($id));
    }

    public function GetCategoryIdByName($category)
    {
        return $this->db->GetOne('SELECT id FROM rtcategories WHERE name=?', array($category));
    }

    public function GetCategoryName($id)
    {
        return $this->db->GetOne('SELECT name FROM rtcategories WHERE id=?', array($id));
    }

    public function GetUserCategories($userid = null)
    {
        return $this->db->GetAllByKey(
            'SELECT c.id, name
            FROM rtcategories c
            LEFT JOIN rtcategoryusers cu
            ON c.id = cu.categoryid '
            . ($userid ? 'WHERE userid = ' . intval($userid) : '' )
            . ' ORDER BY name',
            'id'
        );
    }

    public function RTStats()
    {
        $userid = Auth::GetCurrentUser();
        $categories = $this->GetUserCategories($userid);
        if (empty($categories)) {
            return null;
        }
        foreach ($categories as $category) {
            $catids[] = $category['id'];
        }
        return $this->db->GetAll(
            'SELECT c.id AS id, c.name,
				    COUNT(CASE state WHEN ' . RT_NEW . ' THEN 1 END) AS new,
				    COUNT(CASE state WHEN ' . RT_OPEN . ' THEN 1 END) AS opened,
				    COUNT(CASE state WHEN ' . RT_RESOLVED . ' THEN 1 END) AS resolved,
				    COUNT(CASE state WHEN ' . RT_DEAD . ' THEN 1 END) AS dead,
				    COUNT(CASE WHEN state != ' . RT_RESOLVED . ' THEN 1 END) AS unresolved,
				    COUNT(CASE WHEN t.state <> ' . RT_RESOLVED . ' AND (lv.ticketid IS NULL OR lv.vdate < t.modtime) THEN 1 END) AS unread
				    FROM rtcategories c
				    LEFT JOIN rtticketcategories tc ON c.id = tc.categoryid
				    LEFT JOIN rttickets t ON t.id = tc.ticketid
				    LEFT JOIN rtrights r ON r.queueid = t.queueid AND r.userid = ?
				    LEFT JOIN rtticketlastview lv ON lv.ticketid = t.id AND lv.userid = ?
				    WHERE c.id IN (' . implode(',', $catids) . ') AND (r.rights > 0 OR t.owner = ? OR t.verifierid = ?)
				    GROUP BY c.id, c.name
				    ORDER BY c.name',
            array($userid, $userid, $userid, $userid)
        );
    }

    public function GetQueueByTicketId($id)
    {
        if ($queueid = $this->db->GetOne('SELECT queueid FROM rttickets WHERE id=?', array($id))) {
            return $this->db->GetRow('SELECT * FROM rtqueues WHERE id=?', array($queueid));
        } else {
            return null;
        }
    }

    public function TicketExists($id)
    {
        $ticket = $this->db->GetOne('SELECT * FROM rttickets WHERE id = ?', array($id));
        $this->cache->setCache('rttickets', $id, null, $ticket);
        return $ticket;
    }

    private function SaveTicketMessageAttachments($ticketid, $messageid, $files, $cleanup = false)
    {
        $rt_dir = ConfigHelper::getConfig('rt.mail_dir', STORAGE_DIR . DIRECTORY_SEPARATOR . 'rt');
        $storage_dir_permission = intval(ConfigHelper::getConfig('storage.dir_permission', ConfigHelper::getConfig('rt.mail_dir_permission', '0700')), 8);
        $storage_dir_owneruid = ConfigHelper::getConfig('storage.dir_owneruid', 'root');
        $storage_dir_ownergid = ConfigHelper::getConfig('storage.dir_ownergid', 'root');

        if (!empty($files) && $rt_dir) {
            $ticket_dir = $rt_dir . DIRECTORY_SEPARATOR . sprintf('%06d', $ticketid);
            $message_dir = $ticket_dir . DIRECTORY_SEPARATOR . sprintf('%06d', $messageid);

            @umask(0007);

            @mkdir($ticket_dir, $storage_dir_permission);
            @chown($ticket_dir, $storage_dir_owneruid);
            @chgrp($ticket_dir, $storage_dir_ownergid);
            @mkdir($message_dir, $storage_dir_permission);
            @chown($message_dir, $storage_dir_owneruid);
            @chgrp($message_dir, $storage_dir_ownergid);

            $dirs_to_be_deleted = array();
            foreach ($files as $file) {
                // handle spaces and unknown characters in filename
                // on systems having problems with that
                $filename = preg_replace('/[^\w\.-_]/', '_', basename($file['name']));
                $dstfile = $message_dir . DIRECTORY_SEPARATOR . $filename;
                if (isset($file['content'])) {
                    $fh = @fopen($dstfile, 'w');
                    if (empty($fh)) {
                        continue;
                    }
                    fwrite($fh, $file['content'], strlen($file['content']));
                    fclose($fh);
                } else {
                    if ($cleanup) {
                        $dirs_to_be_deleted[] = dirname($file['name']);
                    }
                    if (!@rename($file['tmp_name'] ?? $file['name'], $dstfile)) {
                        continue;
                    }
                }

                @chown($dstfile, $storage_dir_owneruid);
                @chgrp($dstfile, $storage_dir_ownergid);

                $this->db->Execute(
                    'INSERT INTO rtattachments (messageid, filename, contenttype, cid)
					VALUES (?, ?, ?, ?)',
                    array($messageid, $filename, $file['type'], $file['content-id'] ?? null)
                );
            }
            if (!empty($dirs_to_be_deleted)) {
                $dirs_to_be_deleted = array_unique($dirs_to_be_deleted);
                foreach ($dirs_to_be_deleted as $dir) {
                    rrmdir($dir);
                }
            }
        }
    }

    public function TicketMessageAdd($message, $files = null)
    {
        $headers = '';
        if (isset($message['headers'])) {
            if (is_array($message['headers'])) {
                foreach ($message['headers'] as $name => $value) {
                    $headers .= $name . ': ' . $value . "\n";
                }
            } else {
                $headers = $message['headers'];
            }
        }

        if (!isset($message['queue'])) {
            $queue = $this->GetQueueByTicketId($message['ticketid']);
            $message['queue'] = $queue['id'];
        }

        $this->lastmessageid = '<msg.' . $message['queue']
            . '.' . $message['ticketid']
            . '.' . time() . '@rtsystem.' . gethostname() . '>';

        $createtime = $message['createtime'] ?? time();

        $body = preg_replace("/\r/", "", $message['body']);
        if (isset($message['contenttype']) && $message['contenttype'] == 'text/html') {
            $body = Utils::removeInsecureHtml($body);
        }

        $this->db->Execute(
            'INSERT INTO rtmessages (ticketid, createtime, subject, body, userid, customerid, mailfrom,
			inreplyto, messageid, replyto, headers, type, phonefrom, contenttype, extid)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            array(
                $message['ticketid'],
                $createtime,
                $message['subject'] ?? '',
                $body,
                $message['userid'] ?? Auth::GetCurrentUser(),
                empty($message['customerid']) ? null : $message['customerid'],
                $message['mailfrom'] ?? '',
                $message['inreplyto'] ?? null,
                $message['messageid'] ?? $this->lastmessageid,
                $message['replyto'] ?? ($message['headers']['Reply-To'] ?? ''),
                $headers,
                $message['type'] ?? RTMESSAGE_REGULAR,
                isset($message['phonefrom']) && $message['phonefrom'] != -1 ? $message['phonefrom'] : '',
                $message['contenttype'] ?? 'text/plain',
                $message['extid'] ?? null,
            )
        );
        $msgid = $this->db->GetLastInsertID('rtmessages');

        $this->db->Execute(
            'UPDATE rttickets SET modtime = ? WHERE id = ?',
            array(
                $createtime,
                $message['ticketid'],
            )
        );

        $this->SaveTicketMessageAttachments($message['ticketid'], $msgid, $files);

        return $msgid;
    }

    public function TicketAdd($ticket, $files = null)
    {
        $createtime = $ticket['createtime'] ?? time();

        $this->db->Execute('INSERT INTO rttickets (queueid, customerid, requestor, requestor_mail, requestor_phone,
			requestor_userid, subject, state, owner, createtime, modtime, cause, creatorid, source, priority, address_id, nodeid,
			netnodeid, netdevid, verifierid, deadline, service, type, invprojectid, parentid)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($ticket['queue'],
            empty($ticket['customerid']) ? null : $ticket['customerid'],
            $ticket['requestor'] ?? null,
            $ticket['requestor_mail'] ?? null,
            $ticket['requestor_phone'] ?? null,
            empty($ticket['requestor_userid']) ? null : $ticket['requestor_userid'],
            $ticket['subject'],
            !empty($ticket['state']) ? $ticket['state'] : RT_NEW,
            !empty($ticket['owner']) ? $ticket['owner'] : null,
            $createtime,
            $createtime,
            $ticket['cause'] ?? 0,
            $ticket['userid'] ?? Auth::GetCurrentUser(),
            $ticket['source'] ?? 0,
            isset($ticket['priority']) && strlen($ticket['priority']) ? $ticket['priority'] : null,
            !empty($ticket['address_id']) ? $ticket['address_id'] : null,
            !empty($ticket['nodeid']) ? $ticket['nodeid'] : null,
            !empty($ticket['netnodeid']) ? $ticket['netnodeid'] : null,
            !empty($ticket['netdevid']) ? $ticket['netdevid'] : null,
            !empty($ticket['verifierid']) ? $ticket['verifierid'] : null,
            !empty($ticket['deadline']) ? $ticket['deadline'] : null,
            !empty($ticket['service']) ? $ticket['service'] : SERVICE_OTHER,
            !empty($ticket['type']) ? $ticket['type'] : RT_TYPE_OTHER,
            !empty($ticket['invprojectid']) ? $ticket['invprojectid'] : null,
            empty($ticket['parentid']) ? null : $ticket['parentid'],
        ));

        $id = $this->db->GetLastInsertID('rttickets');

        if (!empty($ticket['parentid']) && !empty($ticket['relatedtickets'])) {
            foreach (array_values($ticket['relatedtickets']) as $tid) {
                $this->updateTicketParentID($tid, $ticket['parentid']);
            }
        }

        $this->lastmessageid = '<msg.' . $ticket['queue'] . '.' . $id . '.' . time() . '@rtsystem.' . gethostname() . '>';

        if (isset($ticket['contenttype']) && $ticket['contenttype'] == 'text/plain') {
            $body = str_replace("\r", "", $ticket['body']);
        } else {
            $body = Utils::removeInsecureHtml($ticket['body']);
        }

        $this->db->Execute('INSERT INTO rtmessages (ticketid, customerid, createtime,
				subject, body, mailfrom, phonefrom, messageid, replyto, headers, contenttype, extid)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($id,
            empty($ticket['customerid']) ? null : $ticket['customerid'],
            $createtime,
            $ticket['subject'],
            $body,
            empty($ticket['mailfrom']) ? '' : $ticket['mailfrom'],
            empty($ticket['phonefrom']) || $ticket['phonefrom'] == -1 ? '' : $ticket['phonefrom'],
            $ticket['messageid'] ?? $this->lastmessageid,
            $ticket['replyto'] ?? '',
            $ticket['headers'] ?? '',
            $ticket['contenttype'] ?? 'text/plain',
            $ticket['extid'] ?? null,
        ));

        if (isset($ticket['note']) && $ticket['note']) {
            $this->db->Execute('INSERT INTO rtmessages (ticketid, customerid, createtime,
                        subject, body, mailfrom, phonefrom, messageid, replyto, headers, type)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($id,
                empty($ticket['customerid']) ? null : $ticket['customerid'],
                $createtime,
                $ticket['subject'],
                preg_replace("/\r/", "", $ticket['note']),
                empty($ticket['mailfrom']) ? '' : $ticket['mailfrom'],
                empty($ticket['phonefrom']) || $ticket['phonefrom'] == -1 ? '' : $ticket['phonefrom'],
                $ticket['messageid'] ?? $this->lastmessageid,
                $ticket['replyto'] ?? '',
                $ticket['headers'] ?? '',
                RTMESSAGE_NOTE,
            ));
        }

        $msgid = $this->db->GetLastInsertID('rtmessages');

        if (isset($ticket['contenttype']) && $ticket['contenttype'] == 'text/html') {
            $body = str_replace(
                array(
                    '%tid%',
                    '%mid%'
                ),
                array(
                    $id,
                    $msgid
                ),
                $body
            );
            $this->db->Execute('UPDATE rtmessages SET body = ? WHERE id = ?', array($body, $msgid));
        }

        if (!empty($ticket['categories'])) {
            foreach (array_keys($ticket['categories']) as $catid) {
                $this->db->Execute('INSERT INTO rtticketcategories (ticketid, categoryid)
					VALUES (?, ?)', array($id, $catid));
            }
        }

        $this->SaveTicketMessageAttachments($id, $msgid, $files);

        return $id;
    }

    public function GetLastMessageID()
    {
        return $this->lastmessageid;
    }

    public function LimitQueuesToUserpanelEnabled($queuelist, $queueid)
    {
        $userpanel_enabled_modules = ConfigHelper::getConfig('userpanel.enabled_modules');
        if ((empty($userpanel_enabled_modules) || strpos($userpanel_enabled_modules, 'helpdesk') !== false)
            && ConfigHelper::getConfig('userpanel.limit_ticket_movements_to_selected_queues')) {
            $selectedqueues = explode(';', ConfigHelper::getConfig('userpanel.queues'));
            if (in_array($queueid, $selectedqueues)) {
                foreach ($queuelist as $idx => $queue) {
                    if (!in_array($queue['id'], $selectedqueues)) {
                        unset($queuelist[$idx]);
                    }
                }
            }
        }
        return $queuelist;
    }

    public function GetTicketContents($id, $short = false)
    {
        global $RT_STATES;

        $userid = Auth::GetCurrentUser();
        $helpdesk_advanced_operations = ConfigHelper::checkPrivilege('helpdesk_advanced_operations');

        $ticket = $this->db->GetRow('SELECT t.id AS ticketid, t.queueid, rtqueues.name AS queuename, t.requestor, t.requestor_phone, t.requestor_mail,
            t.requestor_userid, d.name AS requestor_username, t.state, t.owner, t.customerid, t.cause, t.creatorid, c.name AS creator,
            t.source, t.priority, i.id AS invprojectid, i.name AS invproject_name, t.verifier_rtime, '
            . $this->db->Concat('customers.lastname', "' '", 'customers.name') . ' AS customername,
            o.name AS ownername, t.createtime, t.resolvetime, t.subject, t.deleted, t.deltime, t.deluserid,
            t.address_id, va.location, t.nodeid, n.name AS node_name, n.location AS node_location,
            t.netnodeid, nn.name AS netnode_name, t.netdevid, nd.name AS netdev_name, va.city_id, va.street_id, va.house,
            t.verifierid, e.name AS verifier_username, t.deadline, openeventcount, t.type, t.service, t.parentid' .
            (!empty($userid) ? ', (CASE WHEN t.id = w.ticketid AND w.userid = ' . $userid . ' THEN 1 ELSE 0 END) as watching ' : '') . '
            FROM rttickets t
            LEFT JOIN rtqueues ON (t.queueid = rtqueues.id)
            LEFT JOIN vusers o ON (t.owner = o.id)
            LEFT JOIN vusers c ON (t.creatorid = c.id)
            LEFT JOIN vusers d ON (t.requestor_userid = d.id)
            LEFT JOIN rtticketwatchers w ON (t.id = w.ticketid)
            LEFT JOIN customers ON (customers.id = t.customerid)
            LEFT JOIN vaddresses va ON va.id = t.address_id
            LEFT JOIN vnodes n ON n.id = t.nodeid
            LEFT JOIN netnodes nn ON nn.id = t.netnodeid
            LEFT JOIN netdevices nd ON nd.id = t.netdevid
            LEFT JOIN invprojects i ON i.id = t.invprojectid
            LEFT JOIN vusers e ON (t.verifierid = e.id)
            LEFT JOIN (
                SELECT SUM(CASE WHEN closed !=1 THEN 1 ELSE 0 END) AS openeventcount,
                ticketid FROM events WHERE ticketid IS NOT NULL GROUP BY ticketid
            ) ev ON ev.ticketid = t.id
            WHERE 1=1 '
            . ($helpdesk_advanced_operations ? '' : ' AND t.deleted = 0')
            . (' AND t.id = ?'), array($id));

        if (empty($ticket)) {
            return null;
        }

        $ticket['requestor_name'] = $ticket['requestor'];
        if (empty($ticket['requestor_userid'])
            && (!empty($ticket['requestor'])
                || !empty($ticket['requestor_mail'])
                || !empty($ticket['requestor_phone']))) {
            $ticket['requestor_userid'] = 0;
        }

        $ticket['categories'] = $this->GetTicketCategories($id);

        $ticket['categorynames'] = empty($ticket['categories']) ? array() :
            array_map(function ($elem) {
                return $elem['name'];
            }, $ticket['categories']);


        if ($ticket['city_id'] && $ticket['house']) {
            $location_manager = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);
            $ticket['coords'] = $location_manager->getCoordinatesForAddress(array(
                'city_id' => $ticket['city_id'],
                'street_id' => $ticket['street_id'],
                'building_num' => $ticket['house'],
            ));
        }

        $ticket['parent'] = $this->getTickets($ticket['parentid']);
        $ticket['relatedtickets'] = $this->GetRelatedTickets($id);

        if (!$short) {
            $ticket['messages'] = $this->GetTicketMessages($id);
        }

        $ticket['status'] = $RT_STATES[$ticket['state']];
        $ticket['uptime'] = uptimef($ticket['resolvetime'] ?
            $ticket['resolvetime'] - $ticket['createtime'] :
            time() - $ticket['createtime']);

        if (!empty($ticket['nodeid']) && empty($ticket['node_location'])) {
            $customer_manager = new LMSCustomerManager($this->db, $this->auth, $this->cache, $this->syslog);
            $ticket['node_location'] = $customer_manager->getAddressForCustomerStuff($ticket['customerid']);
        }
        return $ticket;
    }

    private function GetTicketMessages($ticketid)
    {
        if (empty(intval($ticketid))) {
            die('Ticket ID is empty or invalid');
        }

        $ticketid = intval($ticketid);
        $hide_attachments = !empty($params['hide_attachments']);
        $helpdesk_advanced_operations = ConfigHelper::checkPrivilege('helpdesk_advanced_operations');

        $messages = $this->db->GetAll(
            '(SELECT rtmessages.id AS id, phonefrom, mailfrom, subject, body, contenttype, createtime, '
            . $this->db->Concat('customers.lastname', "' '", 'customers.name') . ' AS customername,
                userid, vusers.name AS username, customerid, rtmessages.type, rtmessages.deleted, rtmessages.deltime, rtmessages.deluserid
                FROM rtmessages
                LEFT JOIN customers ON (customers.id = customerid)
                LEFT JOIN vusers ON (vusers.id = userid)
                WHERE 1=1'
            . ($helpdesk_advanced_operations ? '' : ' AND rtmessages.deleted = 0')
            . (' AND ticketid = ?)')
            . (' ORDER BY createtime ASC, rtmessages.id'),
            array($ticketid)
        );

        if (!$hide_attachments) {
            foreach ($messages as &$message) {
                $message['attachments'] = array();
                $attachments = $this->GetTicketMessageAttachments($message['id']);
                if ($attachments) {
                    if ($message['contenttype'] == 'text/html') {
                        $url_prefix = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '') . '://'
                            . $_SERVER['HTTP_HOST'] . substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1);
                    }

                    foreach ($attachments as $attachment) {
                        if (empty($attachment['cid'])) {
                            $message['attachments'][] = $attachment;
                        } elseif ($message['contenttype'] == 'text/html') {
                            $message['body'] = str_ireplace(
                                '"CID:' . $attachment['cid'] . '"',
                                '"' . $url_prefix . '?m=rtmessageview&api=1&cid=' . $attachment['cid'] . '&tid=' . $ticketid . '&mid=' . $message['id'] . '"',
                                $message['body']
                            );
                        }
                    }
                }
            }
        }

        return $messages;
    }

    public function changeTicketWatching($ticketid, $watching)
    {
        $userid = Auth::GetCurrentUser();
        $ticketprivilege = $this->db->GetOne(
            'SELECT rights FROM rtrights
	        WHERE
	              userid = ?
	              AND queueid = (SELECT queueid FROM rttickets WHERE id = ?)',
            array($userid, $ticketid)
        );

        if (!$ticketprivilege) {
            die("No permission to watch this ticket. Don't mess with the developer.");
        }

        if ($watching) {
            $this->db->Execute(
                'INSERT INTO rtticketwatchers (ticketid, userid) VALUES (?, ?)',
                array($ticketid, $userid)
            );
        } else {
            $this->db->Execute(
                'DELETE FROM rtticketwatchers WHERE ticketid = ? AND userid = ?',
                array($ticketid, $userid)
            );
        }
    }

    public function GetMessage($id)
    {
        if ($message = $this->db->GetRow('SELECT * FROM rtmessages WHERE id=?', array($id))) {
            $message['attachments'] = array();

            $attachments = $this->db->GetAll('SELECT * FROM rtattachments WHERE messageid = ?', array($id));
            if ($attachments) {
                if ($message['contenttype'] == 'text/html') {
                    $url_prefix = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '') . '://'
                        . $_SERVER['HTTP_HOST'] . substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1);
                }

                foreach ($attachments as $attachment) {
                    if (empty($attachment['cid'])) {
                        $message['attachments'][] = $attachment;
                    } elseif ($message['contenttype'] == 'text/html') {
                        $message['body'] = str_ireplace(
                            '"CID:' . $attachment['cid'] . '"',
                            '"' . $url_prefix . '/?m=rtmessageview&api=1&cid=' . $attachment['cid'] . '&tid=' . $message['ticketid'] . '&mid=' . $message['id'] . '"',
                            $message['body']
                        );
                    }
                }
            }

            $references = array();
            $reply = $message;
            while ($reply['inreplyto']) {
                if ($reply['messageid']) {
                    $references[] = $reply['messageid'];
                }
                $reply = $this->db->GetRow(
                    'SELECT messageid, inreplyto FROM rtmessages WHERE id = ?',
                    array($reply['inreplyto'])
                );
            }
            if ($reply['messageid']) {
                $references[] = $reply['messageid'];
            }
            $message['references'] = array_reverse($references);

            $message['cc']  = array();
            if (function_exists('imap_rfc822_parse_headers')) {
                $headers = imap_rfc822_parse_headers($message['headers']);
                if (!empty($headers) && isset($headers->cc)) {
                    foreach ($headers->cc as $cc) {
                        $email = $cc->mailbox . '@' . $cc->host;
                        $message['cc'][$email] = array(
                            'display' => isset($cc->personal) ? iconv_mime_decode($cc->personal) : '',
                            'address' => $email,
                        );
                    }
                }
            }
        }
        return $message;
    }

    public function GetFirstMessage($ticketid)
    {
        $messageid = $this->db->GetOne('SELECT MIN(id) FROM rtmessages
			WHERE ticketid = ? AND (type = ? OR type = ?)
			GROUP BY ticketid', array($ticketid, RTMESSAGE_REGULAR, RTMESSAGE_NOTE));
        if ($messageid) {
            return $this->GetMessage($messageid);
        } else {
            return null;
        }
    }

    public function GetLastMessage($ticketid)
    {
        $messageid = $this->db->GetOne('SELECT MAX(id) FROM rtmessages
			WHERE ticketid = ? AND (type = ? OR type = ?)
			GROUP BY ticketid', array($ticketid, RTMESSAGE_REGULAR, RTMESSAGE_NOTE));
        if ($messageid) {
            return $this->GetMessage($messageid);
        } else {
            return null;
        }
    }

    protected function updateTicketParentID($ticketid, $parentid = null)
    {
        $notes = array();
        if ($parentid) {
            $note = trans('Ticket parent ID has been set to $a.', $parentid);
        } else {
            $note = trans('Ticket parent ID has been removed.');
        }
        $this->db->Execute('INSERT INTO rtmessages (userid, ticketid, type, body, createtime)
            VALUES(?, ?, ?, ?, ?NOW?)', array(Auth::GetCurrentUser(), $ticketid, RTMESSAGE_PARENT_CHANGE, $note));
        $this->db->Execute('UPDATE rttickets SET parentid = ? WHERE id = ?', array($parentid, $ticketid));
    }

    public function TicketChange($ticketid, array $props)
    {
        global $LMS, $RT_STATES, $RT_CAUSE, $RT_SOURCES, $RT_PRIORITIES, $SERVICETYPES, $RT_TYPES;

        $allow_empty_categories = ConfigHelper::checkConfig('rt.allow_empty_categories', ConfigHelper::checkConfig('phpui.helpdesk_allow_empty_categories'));

        $userid = Auth::GetCurrentUser();
        $ticket = $this->GetTicketContents($ticketid);

        $type = 0;
        $notes = array();

        if (array_key_exists('owner', $props)) {
            if ($ticket['owner'] != $props['owner']) {
                if (isset($props['owner'])) {
                    $notes[] = trans('Ticket has been assigned to user $a.', $LMS->GetUserName($props['owner']));
                } else {
                    $notes[] = trans('Ticket has been unassigned from user $a.', $LMS->GetUserName($ticket['owner']));
                }
                $type = $type | RTMESSAGE_OWNER_CHANGE;
            } else {
                $props['owner'] = $ticket['owner'];
            }
        } else {
            $props['owner'] = $ticket['owner'];
        }

        if (isset($props['queueid']) && $ticket['queueid'] != $props['queueid']) {
            $notes[] = trans('Ticket has been moved from queue $a to queue $b.', $LMS->GetQueueName($ticket['queueid']), $LMS->GetQueueName($props['queueid']));
            $type = $type | RTMESSAGE_QUEUE_CHANGE;
            if (ConfigHelper::checkConfig('rt.ticket_queue_change_resets_ticket_state') && $ticket['state'] != RT_VERIFIED && $ticket['state'] != RT_RESOLVED) {
                $props['state'] = RT_NEW;
            }
        } else {
            $props['queueid'] = $ticket['queueid'];
        }

        if (isset($props['cause']) && $ticket['cause'] != $props['cause']) {
            $notes[] = trans('Ticket\'s cause has been changed from $a to $b.', $RT_CAUSE[$ticket['cause']], $RT_CAUSE[$props['cause']]);
            $type = $type | RTMESSAGE_CAUSE_CHANGE;
        } else {
            $props['cause'] = $ticket['cause'];
        }

        if (isset($props['source']) && $ticket['source'] != $props['source']) {
            $notes[] = trans('Ticket\'s source has been changed from $a to $b.', $RT_SOURCES[$ticket['source']], $RT_SOURCES[$props['source']]);
            $type = $type | RTMESSAGE_SOURCE_CHANGE;
        } else {
            $props['source'] = $ticket['source'];
        }

        if (array_key_exists('priority', $props) && $ticket['priority'] != $props['priority']) {
            $a = isset($ticket['priority']) && is_numeric($ticket['priority']) ? $RT_PRIORITIES[$ticket['priority']] : trans('undefined');
            $b = isset($props['priority']) && is_numeric($props['priority']) ? $RT_PRIORITIES[$props['priority']] : trans('undefined');
            $notes[] = trans('Ticket\'s priority has been changed from $a to $b.', $a, $b);
            $type = $type | RTMESSAGE_PRIORITY_CHANGE;
        } else {
            $props['priority'] = $ticket['priority'];
        }

        if (isset($props['state']) && $ticket['state'] != $props['state']) {
            $notes[] = trans('Ticket\'s state has been changed from $a to $b.', $RT_STATES[$ticket['state']]['label'], $RT_STATES[$props['state']]['label']);
            $type = $type | RTMESSAGE_STATE_CHANGE;
        } else {
            $props['state'] = $ticket['state'];
        }

        if (isset($props['subject']) && $ticket['subject'] != $props['subject']) {
            $notes[] = trans('Ticket\'s subject has been changed from $a to $b.', $ticket['subject'], $props['subject']);
            $type = $type | RTMESSAGE_SUBJECT_CHANGE;
        } else {
            $props['subject'] = $ticket['subject'];
        }

        if (array_key_exists('netnodeid', $props)) {
            if ($ticket['netnodeid'] != $props['netnodeid']) {
                $netnode_manager = new LMSNetNodeManager($this->db, $this->auth, $this->cache, $this->syslog);
                if (isset($props['netnodeid'])) {
                    $notes[] = trans(
                        'Ticket\'s network node assignments has been changed from $a to $b.',
                        $netnode_manager->GetNetNodeName($ticket['netnodeid']),
                        $netnode_manager->GetNetNodeName($props['netnodeid'])
                    );
                } else {
                    $notes[] = trans('Ticket has been unassigned from network node $a.', $netnode_manager->GetNetNodeName($ticket['netnodeid']));
                }
                $type = $type | RTMESSAGE_NETNODE_CHANGE;
            } else {
                $props['netnodeid'] = $ticket['netnodeid'];
            }
        } else {
            $props['netnodeid'] = $ticket['netnodeid'];
        }

        if (array_key_exists('invprojectid', $props)) {
            if ($ticket['invprojectid'] != $props['invprojectid']) {
                $project_manager = new LMSProjectManager($this->db, $this->auth, $this->cache, $this->syslog);
                if (isset($props['invprojectid'])) {
                    $notes[] = trans(
                        'Ticket\'s investment project has been changed from $a to $b.',
                        $project_manager->GetProjectName($ticket['invprojectid']),
                        $project_manager->GetProjectName($props['invprojectid'])
                    );
                } else {
                    $notes[] = trans(
                        'Ticket has been unassigned from investment project $a.',
                        $project_manager->GetProjectName($ticket['invprojectid'])
                    );
                }
                $type = $type | RTMESSAGE_INVPROJECT_CHANGE;
            } else {
                $props['invprojectid'] = $ticket['invprojectid'];
            }
        } else {
            $props['invprojectid'] = $ticket['invprojectid'];
        }

        if (array_key_exists('netdevid', $props)) {
            if ($ticket['netdevid'] != $props['netdevid']) {
                $netdev_manager = new LMSNetDevManager($this->db, $this->auth, $this->cache, $this->syslog);
                if (isset($props['netdevid'])) {
                    $notes[] = trans(
                        'Ticket\'s network device assignments has been changed from $a to $b.',
                        $netdev_manager->GetNetDevName($ticket['netdevid']),
                        $netdev_manager->GetNetDevName($props['netdevid'])
                    );
                } else {
                    $notes[] = trans(
                        'Ticket has been unassigned from network device $a.',
                        $netdev_manager->GetNetDevName($ticket['netdevid'])
                    );
                }
                $type = $type | RTMESSAGE_NETDEV_CHANGE;
            } else {
                $props['netdevid'] = $ticket['netdevid'];
            }
        } else {
            $props['netdevid'] = $ticket['netdevid'];
        }

        if (array_key_exists('verifierid', $props)) {
            if (isset($props['verifierid']) && $ticket['verifierid'] != $props['verifierid']) {
                $notes[] = trans('User $a has been set as verifier to ticket.', $LMS->GetUserName($props['verifierid']));
                $type = $type | RTMESSAGE_VERIFIER_CHANGE;
            } elseif (!isset($props['verifierid']) && !empty($ticket['verifierid'])) {
                $notes[] = trans('Verifier has been removed from ticket.');
                $type = $type | RTMESSAGE_VERIFIER_CHANGE;
            } else {
                $props['verifierid'] = $ticket['verifierid'];
            }
        } else {
            $props['verifierid'] = $ticket['verifierid'];
        }

        if (isset($props['verifier_rtime']) && $ticket['verifier_rtime'] != $props['verifier_rtime']) {
            $notes[] = trans('Ticket has been transferred to verifier.');
            $type = $type | RTMESSAGE_VERIFIER_RTIME;
        } else {
            $props['verifier_rtime'] = $ticket['verifier_rtime'];
        }

        if (array_key_exists('deadline', $props)) {
            if (isset($props['deadline']) && $ticket['deadline'] != $props['deadline']) {
                $notes[] = trans('Ticket deadline has been set to $a.', date('Y/m/d H:i', $props['deadline']));
                $type = $type | RTMESSAGE_DEADLINE_CHANGE;
            } elseif (!isset($props['deadline']) && !empty($ticket['deadline'])) {
                $notes[] = trans('Ticket deadline has been removed.');
                $type = $type | RTMESSAGE_DEADLINE_CHANGE;
            } else {
                $props['deadline'] = $ticket['deadline'];
            }
        } else {
            $props['deadline'] = $ticket['deadline'];
        }

        if (isset($props['service']) && $ticket['service'] != $props['service']) {
            $notes[] = trans('Ticket service has been set to $a.', $SERVICETYPES[$props['service']]);
            $type = $type | RTMESSAGE_SERVICE_CHANGE;
        } else {
            $props['service'] = $ticket['service'];
        }

        if (isset($props['type']) && $ticket['type'] != $props['type']) {
            $notes[] = trans('Ticket type has been set to $a.', trans($RT_TYPES[$props['type']]['label']));
            $type = $type | RTMESSAGE_TYPE_CHANGE;
        } else {
            $props['type'] = $ticket['type'];
        }

        if (array_key_exists('parentid', $props)) {
            if (isset($props['parentid']) && $ticket['parentid'] != $props['parentid']) {
                $notes[] = trans('Ticket parent ID has been set to $a.', $props['parentid']);
                $type = $type | RTMESSAGE_PARENT_CHANGE;
            } elseif (!isset($props['parentid']) && !empty($ticket['parentid'])) {
                $notes[] = trans('Ticket parent ID has been removed.');
                $type = $type | RTMESSAGE_PARENT_CHANGE;
            } else {
                $props['parentid'] = $ticket['parentid'];
            }
        } else {
            $props['parentid'] = $ticket['parentid'];
        }

        if (isset($props['customerid'])) {
            if ($ticket['customerid'] != $props['customerid']) {
                if ($ticket['customerid']) {
                    if (empty($props['customerid'])) {
                        $notes[] = trans(
                            'Ticket has been moved from customer $a ($b) to $c.',
                            $LMS->getCustomerName($ticket['customerid']),
                            $ticket['customerid'],
                            $props['requestor']
                        );
                    } else {
                        $notes[] = trans(
                            'Ticket has been moved from customer $a ($b) to customer $c ($d).',
                            $LMS->getCustomerName($ticket['customerid']),
                            $ticket['customerid'],
                            $LMS->getCustomerName($props['customerid']),
                            $props['customerid']
                        );
                    }
                } else {
                    $notes[] = trans(
                        'Ticket has been moved from $a to customer $b ($c).',
                        $ticket['requestor'],
                        $LMS->getCustomerName($props['customerid']),
                        $props['customerid']
                    );
                }
                $type = $type | RTMESSAGE_CUSTOMER_CHANGE;
            }
        } else {
            $props['customerid'] = $ticket['customerid'];
        }

        if (empty($props['customerid'])) {
            $props['customerid'] = null;
        }

        if (isset($props['categories'])) {
            $ticket['categories'] = empty($ticket['categories']) ? array() : array_keys($ticket['categories']);
            $categories = $this->GetCategoryList(false);

            $category_change = $props['category_change'] ?? null;
            switch ($category_change) {
                case 2:
                    $categories_added = array_diff($props['categories'], $ticket['categories']);
                    $categories_removed = array();
                    break;
                case 3:
                    $categories_added = array();
                    $categories_removed = array_intersect($ticket['categories'], $props['categories']);
                    break;
                default:
                    $categories_added = array_diff($props['categories'], $ticket['categories']);
                    $categories_removed = array_diff($ticket['categories'], $props['categories']);
                    break;
            }

            if (!empty($categories_removed)) {
                foreach ($categories_removed as $category) {
                    $this->db->Execute(
                        'DELETE FROM rtticketcategories WHERE ticketid = ? AND categoryid = ?',
                        array($ticketid, $category)
                    );
                    $notes[] = trans('Category $a has been removed from ticket.', $categories[$category]['name']);
                }
            }
            if (!empty($categories_added)) {
                foreach ($categories_added as $category) {
                    $this->db->Execute(
                        'INSERT INTO rtticketcategories (ticketid, categoryid) VALUES (?, ?)',
                        array($ticketid, $category)
                    );
                    $notes[] = trans('Category $a has been added to ticket.', $categories[$category]['name']);
                }
            }
            $type = $type | RTMESSAGE_CATEGORY_CHANGE;
        }

        if (array_key_exists('address_id', $props)) {
            if (isset($props['address_id']) && $ticket['address_id'] != $props['address_id']) {
                $type = $type | RTMESSAGE_LOCATION_CHANGE;
                $address = $LMS->GetAddress($props['address_id']);
                $props['location'] = $address['location'];
                if (empty($ticket['address_id'])) {
                    $notes[] = trans('Ticket\'s location has been changed to $a.', $props['location']);
                } else {
                    $notes[] = trans('Ticket\'s location has been changed from $a to $b.', $ticket['location'], $props['location']);
                }
            } elseif (!isset($props['address_id']) && !empty($ticket['address_id'])) {
                $type = $type | RTMESSAGE_LOCATION_CHANGE;
                $notes[] = trans('Ticket\'s location $a has been removed.', $ticket['location']);
            } else {
                $props['address_id'] = $ticket['address_id'];
            }
        } else {
            $props['address_id'] = $ticket['address_id'];
        }

        if (!empty($props['nodeid'])) {
            if ($ticket['nodeid'] != $props['nodeid']) {
                $type = $type | RTMESSAGE_NODE_CHANGE;
                $node_manager = new LMSNodeManager($this->db, $this->auth, $this->cache, $this->syslog);
                $node_locations = $node_manager->GetNodeLocations($ticket['customerid']);
                if (isset($node_locations[$props['nodeid']])) {
                    $props['node_name'] = $node_locations[$props['nodeid']]['name'];
                    $props['node_location'] = $node_locations[$props['nodeid']]['location'];
                } else {
                    $props['node_name'] = $props['node_location'] = '-';
                }
                if (empty($ticket['nodeid'])) {
                    $notes[] = trans(
                        'Ticket\'s node has been changed to $a ($b).',
                        $props['node_name'] . ': ' . $props['node_location'],
                        $props['nodeid']
                    );
                } else {
                    $notes[] = trans(
                        'Ticket\'s node has been changed from $a ($b) to $c ($d).',
                        $ticket['node_name'] . ': ' . $ticket['node_location'],
                        $ticket['nodeid'],
                        $props['node_name'] . ': ' . $props['node_location'],
                        $props['nodeid']
                    );
                }
            }
        } elseif (array_key_exists('nodeid', $props)) {
            $props['nodeid'] = null;
            if (!empty($ticket['nodeid'])) {
                $notes[] = trans(
                    'Ticket\'s node $a ($b) has been removed.',
                    $ticket['node_name'] . ': ' . $ticket['node_location'],
                    $ticket['nodeid']
                );
            }
        } else {
            $props['nodeid'] = $ticket['nodeid'];
        }

        if (array_key_exists('requestor', $props)) {
            if (empty($props['requestor'])) {
                $props['requestor'] = '';
            }
        } else {
            $props['requestor'] = $ticket['requestor'];
        }

        if (array_key_exists('requestor_userid', $props)) {
            if (empty($props['requestor_userid'])) {
                $props['requestor_userid'] = null;
            }
        } else {
            $props['requestor_userid'] = empty($ticket['requestor_userid']) ? null : $ticket['requestor_userid'];
        }

        if (array_key_exists('requestor_phone', $props)) {
            if (empty($props['requestor_phone'])) {
                $props['requestor_phone'] = null;
            }
        } else {
            $props['requestor_phone'] = $ticket['requestor_phone'];
        }

        if (array_key_exists('requestor_mail', $props)) {
            if (empty($props['requestor_mail'])) {
                $props['requestor_mail'] = null;
            }
        } else {
            $props['requestor_mail'] = $ticket['requestor_mail'];
        }

        if ($props['priority'] == '' || !isset($props['priority'])) {
            $props['priority'] = null;
        }

        if ($type) {
            $note = implode("\n", $notes);
            $modtime = time();
            $resolvetime = ($props['state'] == RT_RESOLVED) ? $modtime : 0;
            $newticketowner = ($props['state'] == RT_RESOLVED && empty($props['owner'])) ?
                $userid : $props['owner'];
            $this->db->Execute(
                'UPDATE rttickets SET queueid = ?, owner = ?, cause = ?, state = ?, modtime = ?, resolvetime=?, subject = ?,
                customerid = ?, source = ?, priority = ?, address_id = ?, nodeid = ?, netnodeid = ?, netdevid = ?,
                verifierid = ?, verifier_rtime = ?, deadline = ?, service = ?, type = ?, invprojectid = ?,
                requestor_userid = ?, requestor = ?, requestor_mail = ?, requestor_phone = ?, parentid = ?
                WHERE id = ?',
                array(
                    $props['queueid'],
                    $newticketowner,
                    $props['cause'],
                    $props['state'],
                    $modtime,
                    $resolvetime,
                    $props['subject'],
                    $props['customerid'],
                    $props['source'],
                    $props['priority'],
                    $props['address_id'],
                    $props['nodeid'],
                    $props['netnodeid'],
                    $props['netdevid'],
                    $props['verifierid'],
                    $props['verifier_rtime'],
                    $props['deadline'],
                    $props['service'],
                    $props['type'],
                    $props['invprojectid'],
                    $props['requestor_userid'],
                    $props['requestor'],
                    $props['requestor_mail'],
                    $props['requestor_phone'],
                    $props['parentid'],
                    $ticketid
                )
            );
            if (!empty($note)) {
                $this->db->Execute(
                    'INSERT INTO rtmessages (userid, ticketid, type, body, createtime)
                    VALUES(?, ?, ?, ?, ?)',
                    array(
                        $userid,
                        $ticketid,
                        $type,
                        $note,
                        $modtime
                    )
                );
            }
        }

        // update ticket relations
        $relatedtickets = $this->getRelatedTickets($ticketid);
        if (empty($relatedtickets)) {
            $relatedtickets = array();
        }
        if (empty($props['relatedtickets'])) {
            $props['relatedtickets'] = array();
        }
        // find tickets with the same as current ticket parentid set before the moment
        // and treat them as related tickets too
        if (!empty($relatedtickets) && !empty($props['parentid'])) {
            foreach ($relatedtickets as $ticket) {
                if ($ticket['parentid'] == $props['parentid']) {
                    $props['relatedtickets'][] = $ticket['id'];
                }
            }
            $props['relatedtickets'] = array_unique($props['relatedtickets']);
        }

        $relations_to_remove = array_diff(array_keys($relatedtickets), array_values($props['relatedtickets']));
        if (!empty($relations_to_remove)) {
            foreach ($relations_to_remove as $tid) {
                $this->updateTicketParentID($tid);
            }
        }
        if (!empty($props['parentid'])) {
            if ($props['parentid'] != $ticket['parentid']) {
                $relations_to_add = array_values($props['relatedtickets']);
            } else {
                $relations_to_add = array_diff(array_values($props['relatedtickets']), array_keys($relatedtickets));
            }
            if (!empty($relations_to_add)) {
                foreach ($relations_to_add as $tid) {
                    $this->updateTicketParentID($tid, $props['parentid']);
                }
            }
        }
    }

    public function GetQueueCategories($queueid)
    {
        return $this->db->GetAllByKey('SELECT c.id, c.name
			FROM rtqueuecategories qc
			JOIN rtcategories c ON c.id = qc.categoryid
			WHERE queueid = ?', 'id', array($queueid));
    }

    public function ReplaceNotificationSymbols($text, array $params)
    {
        if (isset($params['contentype']) && $params['contenttype'] == 'text/html') {
            $text = str_replace("\n", "<br>\n", $text);
        }

        $text = preg_replace_callback(
            '/%(\\d*)tid/',
            function ($m) use ($params) {
                return sprintf('%0' . $m[1] . 'd', $params['id']);
            },
            $text
        );

        $text = str_replace('%queue', $params['queue'] ?? '', $text);
        $text = str_replace('%cid', isset($params['customerid']) ? sprintf("%04d", $params['customerid']) : '', $text);
        $text = str_replace('%status', $params['status']['label'], $text);
        $text = str_replace('%cat', implode(' ; ', $params['categories']), $text);
        $text = str_replace('%subject', $params['subject'] ?? '', $text);
        $text = str_replace('%author', $params['author'] ?? '', $text);
        if (isset($params['contenttype']) && $params['contenttype'] == 'text/html') {
            $text = str_replace('%body', '<hr>' . ($params['body'] ?? '') . '<hr>', $text);
        } else {
            $text = str_replace('%body', $params['body'] ?? '', $text);
        }
        $text = str_replace('%priority', $params['priority'] ?? '', $text);
        $text = (!empty($params['deadline']))
            ? str_replace('%deadline', date('Y/m/d H:i', $params['deadline']), $text)
            : str_replace('%deadline', '-', $text);
        $text = str_replace('%service', $params['service'] ?? '', $text);
        $text = str_replace('%type', $params['type'] ?? '', $text);
        $text = str_replace('%invproject', $params['invproject_name'] ?? '', $text);
        $text = str_replace('%invprojectid', $params['invprojectid'] ?? '', $text);
        $text = str_replace('%requestor', $params['requestor'] ?? '', $text);
        $text = str_replace('%requestor_mail', $params['requestor_mail'] ?? '', $text);
        $text = str_replace('%requestor_phone', $params['requestor_phone'] ?? '', $text);
        $text = str_replace('%requestor_userid', $params['requestor_userid'] ?? '', $text);
        $text = str_replace('%parentid', $params['parentid'] ?? '', $text);
        $text = str_replace('%node', $params['node'] ?? '', $text);
        $text = str_replace('%nodeid', $params['nodeid'] ?? '', $text);
        $text = str_replace('%netnode', $params['netnode'] ?? '', $text);
        $text = str_replace('%netnodeid', $params['netnodeid'] ?? '', $text);
        $text = str_replace('%netdev', $params['netdev'] ?? '', $text);
        $text = str_replace('%netdevid', $params['netdevid'] ?? '', $text);
        $text = str_replace('%owner', $params['owner'] ?? '', $text);
        $text = str_replace('%ownerid', $params['ownerid'] ?? '', $text);
        $text = str_replace('%verifier', $params['verifier'] ?? '', $text);
        $text = str_replace('%verifierid', $params['verifierid'] ?? '', $text);
        $url_prefix = (!empty($params['url']) ? $params['url']
            : 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '') . '://'
            . $_SERVER['HTTP_HOST']
            . substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1));
        $url = $url_prefix . '?m=rtticketview&id=' . $params['id']
                . (isset($params['messageid']) ? '#rtmessage-' . $params['messageid'] : '');
        if (isset($params['contenttype']) && $params['contenttype'] == 'text/html') {
            $url = '<a href="' . $url . '">' . $url . '</a>';
        }
        $text = str_replace('%url', $url, $text);
        $text = str_replace('%customerinfo', $params['customerinfo'] ?? '', $text);
        if (empty($params['attachments'])) {
            $text = str_replace('%attachments', '', $text);
        } elseif (isset($params['messageid'])) {
            $attachment_text = trans('Attachments:');
            foreach ($params['attachments'] as $attachment) {
                $url = $url_prefix . '?m=rtmessageview&tid=' . $params['id']
                    . '&mid=' . $params['messageid'] . '&file=' . urlencode(preg_replace('/[^\w\.-_]/', '_', $attachment['filename']));
                $attachment_text .= "\n" . (isset($params['contenttype']) && $params['contenttype'] == 'text/html' ? '<br><a href="' . $url . '">' . $url . '</a>' : $url);
            }
            $text = str_replace('%attachments', $attachment_text, $text);
        } else {
            $text = str_replace('%attachments', '', $text);
        }

        return $text;
    }

    public function ReplaceNotificationCustomerSymbols($text, array $params)
    {
        $customer_manager = new LMSCustomerManager($this->db, $this->auth, $this->cache, $this->syslog);
        $locations = $customer_manager->getCustomerAddresses($params['customerid']);
        $address_id = $this->db->GetOne('SELECT address_id FROM rttickets WHERE id = ?', array($params['id']));

        $text = str_replace('%custname', $params['customer']['customername'], $text);
        $text = str_replace('%cid', sprintf("%04d", $params['customerid']), $text);
        $text = str_replace('%address', (empty($address_id) ? $params['customer']['address'] . ', ' . $params['customer']['zip'] . ' ' . $params['customer']['city']
            : $locations[$address_id]['location']), $text);
        $text = str_replace('%phone', !empty($params['phones'])
            ? implode(', ', $params['phones']) : '-', $text);
        $text = str_replace('%email', !empty($params['emails'])
        ? implode(', ', $params['emails']) : '-', $text);

        return $text;
    }

    public function NotifyUsers(array $params)
    {
        global $LMS;

        $notification_attachments = ConfigHelper::checkConfig('rt.notification_attachments', ConfigHelper::checkConfig('phpui.helpdesk_notification_attachments'));

        $notify_author = ConfigHelper::checkConfig('rt.author_notify', ConfigHelper::checkConfig('phpui.helpdesk_author_notify'));
        $userid = Auth::GetCurrentUser();
        $sms_service = ConfigHelper::getConfig('sms.service');

        $args = array(
            'queue' => $params['queue'],
            'contact' => '',
        );
        if (!$notify_author && $userid) {
            $args['user'] = $userid;
        }

        // send email
        $args['type'] = MSG_MAIL;

        $smtp_options = $this->GetRTSmtpOptions();

        if (isset($params['verifierid']) && $params['verifierid'] && (!isset($params['recipients']) || ($params['recipients'] & RT_NOTIFICATION_VERIFIER))) {
            $verifier_email = $this->db->GetOne(
                'SELECT
                    u.email
                FROM users u
                WHERE u.email <> ?
                    AND u.deleted = 0
                    AND u.access = 1
                    AND u.id = ?
                    AND (u.ntype & ?) > 0',
                array(
                    '',
                    $params['verifierid'],
                    MSG_MAIL,
                )
            );
            if (!empty($verifier_email)) {
                $params['mail_headers']['To'] = '<' . $verifier_email . '>';
                $LMS->SendMail(
                    $verifier_email,
                    $params['mail_headers'],
                    $params['mail_body'],
                    $notification_attachments && !empty($params['attachments']) ? $params['attachments'] : null,
                    null,
                    $smtp_options
                );
            }
        }

        if ($params['queue'] && (!isset($params['recipients']) || ($params['recipients'] & RT_NOTIFICATION_USER))) {
            $recipients = $this->db->GetCol(
                'SELECT
                    DISTINCT u.email
                FROM users u
                JOIN rtrights r ON r.userid = u.id
                ' . (!empty($params['ticketid']) && intval($params['ticketid'])
                    ? 'LEFT JOIN rttickets t ON t.queueid = r.queueid AND t.id = ' . intval($params['ticketid']) . '
                    LEFT JOIN rtticketwatchers w ON w.ticketid = t.id AND w.userid = u.id'
                    : '') . '
                WHERE r.queueid = ?
                    AND u.deleted = 0
                    AND u.access = 1
                    AND u.email <> ?
                    AND (
                        (r.rights & ' . RT_RIGHT_EMAIL_NOTICE . ') > 0
                        OR (r.rights & ' . RT_RIGHT_EMAIL_WATCHING_NOTICE . ') > 0 AND w.id IS NOT NULL
                    )'
                . (!isset($args['user']) || $notify_author ? '' : ' AND u.id <> ?')
                . (!empty($params['verifierid']) ? ' AND u.id <> ' . intval($params['verifierid']) : '')
                . ' AND (u.ntype & ?) > 0',
                array_values($args)
            );

            if (!empty($recipients) && !empty($verifier_email)) {
                $recipients = array_diff($recipients, array($verifier_email));
            }

            if (!empty($recipients)) {
                if (isset($params['oldqueue'])) {
                    $oldrecipients = $this->db->GetCol(
                        'SELECT
                            DISTINCT u.email
                        FROM users u
                        JOIN rtrights r ON r.userid = u.id
                        ' . (!empty($params['ticketid']) && intval($params['ticketid'])
                                    ? 'LEFT JOIN rttickets t ON t.queueid = r.queueid AND t.id = ' . intval($params['ticketid']) . '
                            LEFT JOIN rtticketwatchers w ON w.ticketid = t.id AND w.userid = u.id'
                                    : '') . '
                        WHERE r.queueid = ?
                            AND u.deleted = 0
                            AND u.access = 1
                            AND u.email <> ?
                            AND (
                                (r.rights & ' . RT_RIGHT_EMAIL_NOTICE . ') > 0
                                OR (r.rights & ' . RT_RIGHT_EMAIL_WATCHING_NOTICE . ') > 0 AND w.id IS NOT NULL
                            )
                            AND (u.ntype & ?) > 0',
                        array(
                            $params['oldqueue'],
                            '',
                            MSG_MAIL,
                        )
                    );
                    if (!empty($oldrecipients)) {
                        $recipients = array_diff($recipients, $oldrecipients);
                    }
                }

                if (!empty($params['attachments'])) {
                    if ($notification_attachments) {
                        $attachments = $params['attachments'];
                    } elseif (isset($params['contenttype']) && $params['contenttype'] == 'text/html') {
                        $attachments = array_filter($params['attachments'], function ($attachment) {
                            return isset($attachment['content-id']);
                        });
                    }
                }

                foreach ($recipients as $email) {
                    $params['mail_headers']['To'] = '<' . $email . '>';
                    $LMS->SendMail(
                        $email,
                        $params['mail_headers'],
                        $params['mail_body'],
                        !empty($attachments) ? $attachments : null,
                        null,
                        $smtp_options
                    );
                }
            }
        }

        // send sms
        $args['type'] = MSG_SMS;

        if (isset($params['verifierid']) && $params['verifierid'] && (!isset($params['recipients']) || ($params['recipients'] & RT_NOTIFICATION_VERIFIER))) {
            $verifier_phone = $this->db->GetOne(
                'SELECT
                    u.phone
                FROM users u
                WHERE u.phone <> ?
                    AND u.deleted = 0
                    AND u.access = 1
                    AND u.id = ?
                    AND (u.ntype & ?) > 0',
                array(
                    '',
                    $params['verifierid'],
                    MSG_SMS,
                )
            );
            if (!empty($verifier_phone)) {
                $LMS->SendSMS($verifier_phone, $params['sms_body']);
            }
        }

        if ($params['queue'] && (!isset($params['recipients']) || ($params['recipients'] & RT_NOTIFICATION_USER))) {
            if (!empty($sms_service) && ($recipients = $this->db->GetCol(
                'SELECT
                    DISTINCT u.phone
                FROM users u
                JOIN rtrights r ON r.userid = u.id
                ' . (!empty($params['ticketid']) && intval($params['ticketid'])
                    ? 'LEFT JOIN rttickets t ON t.queueid = r.queueid AND t.id = ' . intval($params['ticketid']) . '
                    LEFT JOIN rtticketwatchers w ON w.ticketid = t.id AND w.userid = u.id'
                    : '') . '
                WHERE r.queueid = ?
                    AND u.deleted = 0
                    AND u.access = 1
                    AND u.phone <> ?
                    AND (
                        (r.rights & ' . RT_RIGHT_SMS_NOTICE . ') > 0
                        OR (r.rights & ' . RT_RIGHT_SMS_WATCHING_NOTICE . ') > 0 AND w.id IS NOT NULL
                    )'
                . (!isset($args['user']) || $notify_author ? '' : ' AND u.id <> ?')
                . (!empty($params['verifierid']) ? ' AND u.id <> ' . intval($params['verifierid']) : '')
                . ' AND (u.ntype & ?) > 0',
                array_values($args)
            ))) {
                if (isset($params['oldqueue'])) {
                    $oldrecipients = $this->db->GetCol(
                        'SELECT
                            DISTINCT u.phone
                        FROM users u
                        JOIN rtrights r ON r.userid = u.id
                        ' . (!empty($params['ticketid']) && intval($params['ticketid'])
                            ? 'LEFT JOIN rttickets t ON t.queueid = r.queueid AND t.id = ' . intval($params['ticketid']) . '
                            LEFT JOIN rtticketwatchers w ON w.ticketid = t.id AND w.userid = u.id'
                            : '') . '
                        WHERE r.queueid = ?
                            AND u.deleted = 0
                            AND u.access = 1
                            AND u.phone <> ?
                        AND (
                            (r.rights & ' . RT_RIGHT_SMS_NOTICE . ') > 0
                            OR (r.rights & ' . RT_RIGHT_SMS_WATCHING_NOTICE . ') > 0 AND w.id IS NOT NULL
                        )
                        AND (u.ntype & ?) > 0',
                        array(
                            $params['oldqueue'],
                            '',
                            MSG_SMS,
                        )
                    );
                    if (!empty($oldrecipients)) {
                        $recipients = array_diff($recipients, $oldrecipients);
                    }
                }

                foreach ($recipients as $phone) {
                    $LMS->SendSMS($phone, $params['sms_body']);
                }
            }
        }
    }

    public function CleanupTicketLastView()
    {
        if (random_int(0, 100) <= 1) {
            $this->db->Execute('DELETE FROM rtticketlastview
				WHERE ticketid IN (
					SELECT t.id FROM rttickets t
					WHERE state = ?
				)', array(RT_RESOLVED));
        }
    }

    public function MarkQueueAsRead($queueid)
    {
        $userid = Auth::GetCurrentUser();

        if (!$this->db->GetOne('SELECT q.id FROM rtqueues q
			JOIN rtrights r ON r.queueid = q.id
			WHERE r.userid = ?', array($userid))) {
            return;
        }

        $this->db->BeginTrans();
        $this->db->LockTables('rtticketlastview');

        $this->db->Execute(
            'DELETE FROM rtticketlastview
			WHERE userid = ? AND ticketid IN (SELECT id FROM rttickets WHERE queueid = ?)',
            array($userid, $queueid)
        );
        $this->db->Execute(
            'INSERT INTO rtticketlastview (ticketid, userid, vdate)
			(SELECT id, ?, ?NOW? FROM rttickets WHERE queueid = ? AND state <> ?)',
            array($userid, $queueid, RT_RESOLVED)
        );

        $this->db->UnLockTables();
        $this->db->CommitTrans();
    }

    public function MarkTicketAsRead($ticketid)
    {
        $userid = Auth::GetCurrentUser();

        if (!$this->db->GetOne(
            'SELECT t.id FROM rttickets t
			JOIN rtqueues q ON q.id = t.queueid
			JOIN rtrights r ON r.queueid = q.id
			WHERE t.id = ? AND t.state <> ? AND r.userid = ?',
            array($ticketid, RT_RESOLVED, $userid)
        )) {
            return;
        }

        $this->db->BeginTrans();
        $this->db->LockTables('rtticketlastview');

        if ($this->db->GetOne(
            'SELECT 1 FROM rtticketlastview WHERE ticketid = ? AND userid = ?',
            array($ticketid, $userid)
        )) {
            $result = $this->db->Execute(
                'UPDATE rtticketlastview SET vdate = ?NOW? WHERE ticketid = ? AND userid = ?',
                array($ticketid, $userid)
            );
        } else {
            $result = $this->db->Execute(
                'INSERT INTO rtticketlastview (ticketid, userid, vdate) VALUES (?, ?, ?NOW?)',
                array($ticketid, $userid)
            );
        }

        $this->db->UnLockTables();
        $this->db->CommitTrans();

        return $result;
    }

    public function MarkTicketAsUnread($ticketid)
    {
        $userid = Auth::GetCurrentUser();

        if (!$this->db->GetOne('SELECT t.id FROM rttickets t
			JOIN rtqueues q ON q.id = t.queueid
			JOIN rtrights r ON r.queueid = q.id
			WHERE t.id = ? AND t.state <> ? AND r.userid = ?', array($ticketid, RT_RESOLVED, $userid))) {
            return;
        }

        return $this->db->Execute(
            'DELETE FROM rtticketlastview WHERE ticketid = ? AND userid = ?',
            array($ticketid, $userid)
        );
    }

    public function GetIndicatorStats()
    {
        $userid = Auth::GetCurrentUser();

        $result = array(
            'critical' => 0,
            'urgent' => 0,
            'unread' => 0,
            'expired' => 0,
            'outdated' => 0,
            'verify' => 0,
            'left' => 0,
            'events' => 0,
            'overdue' => 0,
            'watching' => 0,
        );

        if (ConfigHelper::checkPrivilege('helpdesk_operation') || ConfigHelper::checkPrivilege('helpdesk_administration')) {
            $result = array(
                    'critical' => $this->GetQueueContents(array('count' => true, 'priority' => RT_PRIORITY_CRITICAL, 'state' => -3, 'rights' => RT_RIGHT_INDICATOR)),
                    'urgent' => $this->GetQueueContents(array('count' => true, 'priority' => RT_PRIORITY_URGENT, 'state' => -3, 'rights' => RT_RIGHT_INDICATOR)),
                    'unread' => $this->GetQueueContents(array('count' => true, 'state' => -1, 'unread' => 1, 'rights' => RT_RIGHT_INDICATOR)),
                    'expired' => $this->GetQueueContents(array('count' => true, 'state' => -1, 'deadline' => -2, 'owner' => $userid, 'rights' => RT_RIGHT_INDICATOR)),
                    'outdated' => $this->GetQueueContents(array('count' => true, 'state' => RT_EXPIRED, 'owner' => $userid, 'rights' => RT_RIGHT_INDICATOR)),
                    'verify' => $this->GetQueueContents(array('count' => true, 'state' => RT_VERIFIED, 'verifierids' => $userid, 'rights' => RT_RIGHT_INDICATOR)),
                    'left' => $this->GetQueueContents(array('count' => true, 'state' => -1, 'owner' => $userid, 'rights' => RT_RIGHT_INDICATOR)),
                    'watching' => $this->GetQueueContents(array('count' => true, 'watching' => 1, 'rights' => RT_RIGHT_INDICATOR)),
            );
        }

        if (ConfigHelper::CheckPrivilege('timetable_management')) {
            $event_manager = new LMSEventManager($this->db, $this->auth, $this->cache, $this->syslog);
            $result['events'] = $event_manager->GetEventList(array('userid' => $userid,
                'forward' => 1, 'closed' => 0, 'count' => true));
            $result['overdue'] = $event_manager->GetEventList(array('userid' => $userid,
                'forward' => -1, 'closed' => 0, 'count' => true));
        }

        return $result;
    }

    public function DetermineSenderEmail($user_email, $queue_email, $ticket_email, $forced_order = null)
    {
        $helpdesk_sender_email = empty($forced_order)
            ? ConfigHelper::getConfig('rt.sender_email', ConfigHelper::getConfig('phpui.helpdesk_sender_email', 'user,queue,ticket'))
            : $forced_order;
        $attributes = explode(',', $helpdesk_sender_email);
        $attribute = reset($attributes);
        $mailfrom = '';
        while ($attribute !== false) {
            $attribute = trim($attribute);
            if ($attribute == 'user') {
                if ($user_email) {
                    $mailfrom = $user_email;
                    break;
                }
            } elseif ($attribute == 'queue') {
                if ($queue_email) {
                    $mailfrom = $queue_email;
                    break;
                }
            } elseif ($attribute == 'ticket') {
                $mailfrom = $ticket_email;
                break;
            } else {
                $mailfrom = $attribute;
                break;
            }
            $attribute = next($attributes);
        }
        return $mailfrom;
    }

    public function GetTicketRequestorMail($ticketid)
    {
        $mail = $this->db->GetOne(
            'SELECT requestor_mail FROM rttickets
			WHERE id = ? AND requestor_mail <> ?',
            array($ticketid, '')
        );
        if (empty($phone)) {
            return $this->db->GetOne(
                'SELECT mailfrom FROM rtmessages
                    WHERE ticketid = ? AND mailfrom <> ?
                    LIMIT 1',
                array($ticketid, '')
            );
        } else {
            return $mail;
        }
    }

    public function GetTicketRequestorPhone($ticketid)
    {
        $phone = $this->db->GetOne(
            'SELECT requestor_phone FROM rttickets
			WHERE id = ? AND requestor_phone <> ?',
            array($ticketid, '')
        );
        if (empty($phone)) {
            return $this->db->GetOne(
                'SELECT phonefrom FROM rtmessages
                    WHERE ticketid = ? AND phonefrom <> ?
                    LIMIT 1',
                array($ticketid, '')
            );
        } else {
            return $phone;
        }
    }

    public function CheckTicketAccess($ticketid)
    {
        $userid = Auth::GetCurrentUser();

        $user_permission_checks = ConfigHelper::checkConfig('rt.additional_user_permission_checks', ConfigHelper::checkConfig('phpui.helpdesk_additional_user_permission_checks'));
        $allow_empty_categories = ConfigHelper::checkConfig('rt.allow_empty_categories', ConfigHelper::checkConfig('phpui.helpdesk_allow_empty_categories'));

        if ($user_permission_checks) {
            return $this->db->GetOne(
                'SELECT (CASE WHEN r.rights IS NULL THEN '
                    . (RT_RIGHT_READ | RT_RIGHT_WRITE | RT_RIGHT_DELETE) . ' ELSE r.rights END) FROM rttickets t
				LEFT JOIN rtrights r ON r.queueid = t.queueid AND r.userid = ?
				WHERE t.id = ? AND (r.rights IS NOT NULL OR t.owner = ? OR t.verifierid = ?)
					AND (EXISTS (
						SELECT tc.categoryid FROM rtticketcategories tc
						JOIN rtcategoryusers u ON u.userid = ? AND u.categoryid = tc.categoryid
						WHERE tc.ticketid = ?
					)' . ($allow_empty_categories
                        ? ' OR NOT EXISTS (
								SELECT tc2.categoryid FROM rtticketcategories tc2
								WHERE tc2.ticketid = ' . intval($ticketid) . '
							)'
                        : '')
                    . ')',
                array($userid, $ticketid, $userid, $userid, $userid, $ticketid)
            );
        } else {
            return $this->db->GetOne(
                'SELECT rights FROM rtrights r
				JOIN rttickets t ON t.queueid = r.queueid
				WHERE r.userid = ? AND t.id = ?
					AND (EXISTS (
						SELECT tc.categoryid FROM rtticketcategories tc
						JOIN rtcategoryusers u ON u.userid = ? AND u.categoryid = tc.categoryid
						WHERE tc.ticketid = ?
					)' . ($allow_empty_categories
                        ? ' OR NOT EXISTS (
								SELECT tc2.categoryid FROM rtticketcategories tc2
								WHERE tc2.ticketid = ' . intval($ticketid) . '
							)'
                        : '')
                    . ')',
                array($userid, $ticketid, $userid, $ticketid)
            );
        }
    }

    public function GetRelatedTickets($ticketid)
    {
        return $this->db->GetAllByKey(
            'SELECT id, subject AS name, parentid FROM rttickets WHERE id <> ? AND parentid = (SELECT parentid FROM rttickets WHERE id = ?) ORDER BY id',
            'id',
            array($ticketid, $ticketid)
        );
    }

    public function GetTicketCategories($ticketid)
    {
        return $this->db->GetAllByKey(
            'SELECT categoryid AS id, c.name, c.style
                FROM rtticketcategories tc
                JOIN rtcategories c ON c.id = tc.categoryid
                WHERE ticketid = ?',
            'id',
            array($ticketid)
        );
    }

    private function GetTicketMessageAttachments($messageid)
    {
        return $this->db->GetAll(
            'SELECT filename, contenttype, cid FROM rtattachments WHERE messageid = ?',
            array($messageid)
        );
    }

    public function GetChildTickets($ticketid)
    {
        return $this->db->GetAll(
            'SELECT id, subject FROM rttickets WHERE parentid = ? ORDER BY id',
            array($ticketid)
        );
    }

    public function getTickets($ticketids)
    {
        if (is_array($ticketids)) {
            return $this->db->GetAllByKey(
                'SELECT id, state, subject AS name, customerid FROM rttickets WHERE id IN ? ORDER BY id',
                'id',
                array($ticketids)
            );
        } else {
            return $this->db->GetRow(
                'SELECT id, state, subject AS name, customerid FROM rttickets WHERE id = ?',
                array($ticketids)
            );
        }
    }

    public function GetTicketParentID($ticketid)
    {
        if (!empty($ticketid)) {
            return $this->db->GetOne('SELECT parentid FROM rttickets WHERE id = ?', array($ticketid));
        } else {
            return null;
        }
    }

    public function IsTicketLoop($ticketid, $parentid)
    {
        if ($ticketid == $parentid) {
            return true;
        }
        if (empty($parentid)) {
            return false;
        }
        $parentid = $this->GetTicketParentID($parentid);
        return $this->IsTicketLoop($ticketid, $parentid);
    }

    public function GetRTSmtpOptions($config_section = 'rt')
    {
        $options = array();

        $variable_mapping = array(
            'host' => $config_section . '.smtp_host',
            'port' => $config_section . '.smtp_port',
            'user' => array($config_section . '.smtp_username', $config_section . '.smtp_user'),
            'pass' => array($config_section . '.smtp_password', $config_section . '.smtp_pass'),
            'auth' => array($config_section . '.smtp_auth_type', $config_section . '.smtp_auth'),
            'secure' => $config_section . '.smtp_secure',
            'ssl_verify_peer' => $config_section . '.smtp_ssl_verify_peer',
            'ssl_verify_peer_name' => $config_section . '.smtp_ssl_verify_peer_name',
            'ssl_allow_self_signed' => $config_section . '.smtp_ssl_allow_self_signed',
            'smime_certificate' => $config_section . '.smime_certificate',
            'smime_key' => $config_section . '.smime_key',
            'smime_ca_chain' => $config_section . '.smime_ca_chain',
            'smime_sender_email' => $config_section . '.smime_sender_email',
        );

        foreach ($variable_mapping as $option_name => $variable_name) {
            if (is_array($variable_name)) {
                $exists = false;
                foreach ($variable_name as $vname) {
                    if (ConfigHelper::variableExists($vname)) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    continue;
                }
                $variable_name = $vname;
            } else if (!ConfigHelper::variableExists($variable_name)) {
                    continue;
            }

            $variable = ConfigHelper::getConfig($variable_name);
            if (empty($variable)) {
                continue;
            }

            $options[$option_name] = strpos($option_name, 'ssl_') === false ? $variable
                : ConfigHelper::checkValue($variable);
        }

        return $options;
    }

    public function CopyQueuePermissions($src_userid, $dst_userid)
    {
        $this->db->Execute('DELETE FROM rtrights WHERE userid = ?', array($dst_userid));
        return $this->db->Execute(
            'INSERT INTO rtrights (userid, queueid, rights)
            (SELECT ?, queueid, rights FROM rtrights WHERE userid = ?)',
            array($dst_userid, $src_userid)
        );
    }

    public function CopyCategoryPermissions($src_userid, $dst_userid)
    {
        $this->db->Execute('DELETE FROM rtcategoryusers WHERE userid = ?', array($dst_userid));
        return $this->db->Execute(
            'INSERT INTO rtcategoryusers (userid, categoryid)
            (SELECT ?, categoryid FROM rtcategoryusers WHERE userid = ?)',
            array($dst_userid, $src_userid)
        );
    }

    public function TicketIsAssigned($ticketid)
    {
        return $this->db->Execute(
            'SELECT 1 FROM rttickets
            WHERE id = ? AND owner <> ?',
            array($ticketid, Auth::GetCurrentUser())
        ) > 0;
    }

    public function getTicketImageGalleries(&$ticket)
    {
        $ticket['images'] = array();
        foreach ($ticket['messages'] as &$message) {
            $images = array();
            $message['images'] = array();
            if ($message['type'] == RTMESSAGE_REGULAR || $message['type'] == RTMESSAGE_NOTE) {
                if (!empty($message['attachments'])) {
                    foreach ($message['attachments'] as $attachment) {
                        if (strpos($attachment['contenttype'], 'image') === 0) {
                            $url = '?m=rtmessageview&tid=' . $ticket['ticketid'] . '&mid=' . $message['id']
                                . '&file=' . urlencode($attachment['filename']) . '&api=1';
                            $images[] = array(
                                'image' => $url,
                                'thumb' => $url . '&thumbnail=200',
                                'title' => $attachment['filename'],
                            );
                        }
                    }
                }
            }
            if (count($images)) {
                $message['images'] = $images;
                $ticket['images'] = array_merge($ticket['images'], $images);
            }
        }
        unset($message);
    }

    public function cleanupTicketSubject($subject)
    {
        static $ticket_subject_remove_reply_prefix = null;
        static $subject_ticket_regexp_match = null;

        if (!isset($ticket_subject_remove_reply_prefix)) {
            $ticket_subject_remove_reply_prefix = ConfigHelper::checkConfig('rt.ticket_subject_remove_reply_prefix');
            $subject_ticket_regexp_match = ConfigHelper::getConfig('rt.subject_ticket_regexp_match', '\[RT#(?<ticketid>[0-9]{6,})\]');
        }
        if ($ticket_subject_remove_reply_prefix) {
            $subject = preg_replace('/(Re|Odp):\s*/', '', $subject);
        }
        return preg_replace('/' . $subject_ticket_regexp_match . '/', '', $subject);
    }

    public function isCategoryAssignedToTicket($categoryid, $ticketid)
    {
        return $this->db->GetOne(
            'SELECT id
            FROM rtticketcategories
            WHERE ticketid = ?
                AND categoryid = ?',
            array(
                $ticketid,
                $categoryid,
            )
        );
    }

    public function assignCategoryToTicket($categoryid, $ticketid)
    {
        $categories = is_array($categoryid) ? $categoryid : array($categoryid);
        $inserted = 0;
        foreach ($categories as $category) {
            if (!$this->isCategoryAssignedToTicket($category, $ticketid)) {
                $res = $this->db->Execute(
                    'INSERT INTO rtticketcategories (ticketid, categoryid) VALUES (?, ?)',
                    array(
                        $ticketid,
                        $category,
                    )
                );
                if (!empty($res)) {
                    $inserted++;
                }
            }
        }
        return $inserted;
    }

    public function deleteTicket($ticketid, $persistent = true)
    {
        if ($this->syslog) {
            $ticket = $this->db->GetRow(
                'SELECT
                        t.customerid,
                        t.queueid,
                        t.nodeid,
                        t.netnodeid,
                        t.netdevid
                    FROM rttickets t
                    WHERE t.id = ?',
                array(
                    $ticketid,
                )
            );

            $userid = Auth::GetCurrentUser();

            $args = array(
                SYSLOG::RES_USER => $userid,
                'del_' . SYSLOG::getResourceKey(SYSLOG::RES_USER) => $userid,
                SYSLOG::RES_TICKET => $ticketid,
                SYSLOG::RES_QUEUE => $ticket['queueid'],
                SYSLOG::RES_CUST => $ticket['customerid'],
                SYSLOG::RES_NODE => $ticket['nodeid'],
                SYSLOG::RES_NETNODE => $ticket['netnodeid'],
                SYSLOG::RES_NETDEV => $ticket['netdevid'],
            );

            $messageids = $this->db->GetCol('SELECT id FROM rtmessages WHERE ticketid = ?', array($ticketid));
            if (empty($messageids)) {
                $messageids = array();
            }

            $message_args = array(
                SYSLOG::RES_TICKET_MESSAGE => null,
                SYSLOG::RES_TICKET => $ticketid,
            );
        }

        if ($persistent) {
            $rt_dir = ConfigHelper::getConfig('rt.mail_dir', STORAGE_DIR . DIRECTORY_SEPARATOR . 'rt');

            $ticket_dir = $rt_dir . DIRECTORY_SEPARATOR . sprintf('%06d', $ticketid);

            rrmdir($ticket_dir);

            if ($this->syslog) {
                $this->syslog->AddMessage(SYSLOG::RES_TICKET, SYSLOG::OPER_DELETE, $args);

                foreach ($messageids as $messageid) {
                    $args[SYSLOG::RES_TICKET_MESSAGE] = $messageid;
                    $args['del_' . SYSLOG::getResourceKey(SYSLOG::RES_USER)] = $userid;
                    $this->syslog->AddMessage(SYSLOG::RES_TICKET_MESSAGE, SYSLOG::OPER_DELETE, $args);
                }
            }

            return $this->db->Execute('DELETE FROM rttickets WHERE id = ?', array($ticketid));
        } else {
            $result = $this->db->Execute(
                'UPDATE rttickets SET deleted = ?, deltime = ?NOW?, deluserid = ? WHERE deleted = ? AND id = ?',
                array(1, $userid, 0, $ticketid)
            );

            $this->db->Execute(
                'UPDATE rtmessages SET deleted = ?, deluserid = ? WHERE deleted = ? and ticketid = ?',
                array(1, $userid, 0, $ticketid)
            );

            if ($this->syslog) {
                $args['deleted'] = 1;

                $this->syslog->AddMessage(SYSLOG::RES_TICKET, SYSLOG::OPER_UPDATE, $args);

                foreach ($messageids as $messageid) {
                    $args[SYSLOG::RES_TICKET_MESSAGE] = $messageid;
                    $args['del_' . SYSLOG::getResourceKey(SYSLOG::RES_USER)] = $userid;
                    $args['deleted'] = 1;
                    $this->syslog->AddMessage(SYSLOG::RES_TICKET_MESSAGE, SYSLOG::OPER_UPDATE, $args);
                }
            }

            return $result;
        }
    }
}
