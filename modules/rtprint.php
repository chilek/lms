<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

if (!ConfigHelper::checkPrivilege('helpdesk_advanced_operations') && !ConfigHelper::checkPrivilege('reports')) {
    access_denied();
}

$type = $_GET['type'] ?? '';

switch ($type) {
    case 'stats':
        /******************************************/

        $days  = empty($_GET['days']) ? intval($_POST['days']) : intval($_GET['days']);
        $times = empty($_GET['times']) ? intval($_POST['times']) : intval($_GET['times']);
        $queue = empty($_GET['queue']) ? intval($_POST['queue']) : intval($_GET['queue']);
        $removed = empty($_GET['removed']) ? $_POST['removed'] : $_GET['removed'];
        $categories = empty($_GET['categories']) ? $_POST['categories'] : $_GET['categories'];
        $datefrom  = empty($_GET['datefrom']) ? $_POST['datefrom'] : $_GET['datefrom'];
        $dateto  = empty($_GET['dateto']) ? $_POST['dateto'] : $_GET['dateto'];

        if ($queue) {
            $where[] = 'queueid = '.$queue;
        }
        if ($days) {
            $where[] = 'rttickets.createtime > '.mktime(0, 0, 0, date('n'), date('j')-$days);
        }
        $catids = is_array($categories) ? Utils::filterIntegers($categories) : null;
        if (!empty($catids)) {
            if (count($catids) == 1 && $catids[0] == -1) {
                $where[] = 'tc.categoryid IS NULL';
            } else {
                $where[] = 'tc.categoryid IN (' . implode(',', $catids) . ')';
            }
        }

        if (!ConfigHelper::checkPrivilege('helpdesk_advanced_operations')) {
            $where[] = 'rttickets.deleted = 0';
        } else {
            if ($removed != '') {
                if ($removed == '-1') {
                    $where[] = 'rttickets.deleted = 0';
                } else {
                    $where[] = 'rttickets.deleted = 1';
                }
            }
        }

        if (!empty($datefrom)) {
            $datefrom=date_to_timestamp($datefrom);
            $where[] = 'rttickets.createtime >= '.$datefrom;
        } else {
            $datefrom = 0;
        }

        if (!empty($dateto)) {
            $dateto=date_to_timestamp($dateto);
            $where[] = 'rttickets.createtime <= '.$dateto;
        } else {
            $dateto = 0;
        }

        if ($list = $DB->GetAll('SELECT COUNT(*) AS total, customerid, '
                    .$DB->Concat('UPPER(customers.lastname)', "' '", 'customers.name').' AS customername
		               	    FROM rttickets
		               	    LEFT JOIN rtticketcategories tc ON tc.ticketid = rttickets.id
				    LEFT JOIN customers ON (customerid = customers.id)
				    WHERE customerid IS NOT NULL'
                    .(isset($where) ? ' AND '.implode(' AND ', $where) : '')
                    .' GROUP BY customerid, customers.lastname, customers.name'
                    .($times ? ' HAVING COUNT(*) > '.$times : '')
                    .' ORDER BY total DESC')) {
            $customer = $DB->GetAllByKey('SELECT COUNT(*) AS total, customerid
		               	    FROM rttickets 
		               	    LEFT JOIN rtticketcategories tc ON tc.ticketid = rttickets.id
				    WHERE cause = 1'
                .(isset($where) ? ' AND '.implode(' AND ', $where) : '')
                .' GROUP BY customerid', 'customerid');
            $company = $DB->GetAllByKey('SELECT COUNT(*) AS total, customerid
		               	    FROM rttickets 
		               	    LEFT JOIN rtticketcategories tc ON tc.ticketid = rttickets.id
				    WHERE cause = 2'
                .(isset($where) ? ' AND '.implode(' AND ', $where) : '')
                .' GROUP BY customerid', 'customerid');

            foreach ($list as $idx => $row) {
                $list[$idx]['customer'] = isset($customer[$row['customerid']]) ? $customer[$row['customerid']]['total'] : null;
                $list[$idx]['company'] = isset($company[$row['customerid']]) ? $company[$row['customerid']]['total'] : 0;
                $list[$idx]['other'] = $list[$idx]['total'] - $list[$idx]['customer'] - $list[$idx]['company'];
            }
        }

        $layout['pagetitle'] = trans('Requests Stats');

        $SMARTY->assign('list', $list);
        $SMARTY->display('rt/rtprintstats.html');
        break;

    case 'ticketlist':
        /******************************************/

        $days     = empty($_GET['days']) ? intval($_POST['days']) : intval($_GET['days']);
        $customer = empty($_GET['customer']) ? intval($_POST['customer']) : intval($_GET['customer']);
        $queue    = empty($_GET['queue']) ? intval($_POST['queue']) : intval($_GET['queue']);
        $status   = $_GET['status'] ?? $_POST['status'];
        $removed  = $_GET['removed'] ?? $_POST['removed'];
        $subject  = empty($_GET['subject']) ? $_POST['subject'] : $_GET['subject'];
        $extended = !empty($_GET['extended']) || ((empty($_POST['extended']) ? false : true));
        $comment_details = !empty($_GET['comment-details']) || !empty($_POST['comment-details']);
        $categories = empty($_GET['categories']) ? $_POST['categories'] : $_GET['categories'];
        $datefrom  = empty($_GET['datefrom']) ? $_POST['datefrom'] : $_GET['datefrom'];
        $dateto  = empty($_GET['dateto']) ? $_POST['dateto'] : $_GET['dateto'];
        $address  = empty($_GET['address']) ? $_POST['address'] : $_GET['address'];
        $zip  = empty($_GET['zip']) ? $_POST['zip'] : $_GET['zip'];
        $city  = empty($_GET['city']) ? $_POST['city'] : $_GET['city'];

        if ($queue) {
            $where[] = 't.queueid = '.$queue;
        }
        if ($customer) {
            $where[] = 'customerid = '.$customer;
        }
        if ($days) {
            $where[] = 't.createtime < '.mktime(0, 0, 0, date('n'), date('j')-$days);
        }
        if ($subject) {
            $where[] = 't.subject ?LIKE? '.$DB->Escape("%$subject%");
        }

        // category id's
        if (!empty($categories)) {
            if (!is_array($categories)) {
                $categories = array($categories);
            }

            if (in_array('all', $categories)) {
                $filter['catids'] = null;
            } else {
                $filter['catids'] = Utils::filterIntegers($categories);
                if (in_array(-1, $filter['catids'])) {
                    if (count($filter['catids']) > 1) {
                        $catidsfilter = '(';
                    }
                    $catidsfilter .= 'tc.categoryid IS NULL';
                    $filter['catids'] = array_diff($filter['catids'], ["-1"]);
                    if (!empty($filter['catids'])) {
                        $catidsfilter .= ' OR tc.categoryid IN (' . implode(',', $filter['catids']) . '))';
                    }
                    $where[] = $catidsfilter;
                } else {
                    $where[] = 'tc.categoryid IN (' . implode(',', $filter['catids']) . ')';
                }
            }
        }

        if ($status != '') {
            if ($status == -1) {
                $where[] = 't.state != '.RT_RESOLVED;
            } else {
                $where[] = 't.state = '.intval($status);
            }
        }

        if (!ConfigHelper::checkPrivilege('helpdesk_advanced_operations')) {
            $where[] = 't.deleted = 0';
        } else {
            if ($removed != '') {
                if ($removed == '-1') {
                    $where[] = 't.deleted = 0';
                } else {
                    $where[] = 't.deleted = 1';
                }
            }
        }

        if (!empty($datefrom)) {
            $datefrom = date_to_timestamp($datefrom);
            $where[] = 't.createtime >= ' . $datefrom;
        } else {
            $datefrom = 0;
        }

        if (!empty($dateto)) {
            $dateto = date_to_timestamp($dateto);
            $where[] = 't.createtime <= ' . $dateto;
        } else {
            $dateto = 0;
        }

        if (!empty($address) || !empty($zip) || !empty($city)) {
            $where[] = '('
                . (empty($address) ? '1=1' : 'UPPER(va.address) ?LIKE? UPPER(' . $DB->Escape('%' . $address . '%') . ')')
                . ' AND '
                . (empty($zip) ? '1=1' : 'UPPER(va.zip) ?LIKE? UPPER(' . $DB->Escape('%' . $zip . '%') . ')')
                . ' AND '
                . (empty($city) ? '1=1' : 'UPPER(va.city) ?LIKE? UPPER(' . $DB->Escape('%' . $city . '%') . ')')
                . ')';
        }

        $list = $DB->GetAllByKey('SELECT t.id, t.createtime, t.resolvetime, t.deadline, t.customerid, t.subject,
            t.requestor, t.requestor_mail, '
            .$DB->Concat('UPPER(c.lastname)', "' '", 'c.name').' AS customername '
            .(!empty($_POST['contacts']) || !empty($_GET['contacts'])
                ? ', COALESCE(va.city, c.city) AS city, COALESCE(va.address, c.address) AS address,
					(SELECT ' . $DB->GroupConcat('contact', ',', true) . '
						FROM customercontacts WHERE customerid = c.id AND (customercontacts.type & '. (CONTACT_MOBILE|CONTACT_FAX|CONTACT_LANDLINE) .' > 0 )
						GROUP BY customerid
					) AS phones,
					(SELECT ' . $DB->GroupConcat('contact', ',', true) . '
						FROM customercontacts WHERE customerid = c.id AND (customercontacts.type & ' . CONTACT_EMAIL .' > 0)
						GROUP BY customerid
					) AS emails ' : '')
            .'FROM rttickets t
			JOIN rtrights r ON r.queueid = t.queueid AND r.rights & ' . RT_RIGHT_READ . ' > 0
			LEFT JOIN rtticketcategories tc ON tc.ticketid = t.id
			LEFT JOIN customeraddressview c ON (customerid = c.id)
			LEFT JOIN vaddresses va ON va.id = t.address_id
			WHERE 1 = 1 '
            .(isset($where) ? ' AND '.implode(' AND ', $where) : '')
            .' ORDER BY t.createtime', 'id');

        if ($list && $extended) {
            $tickets = implode(',', array_keys($list));
            if ($content = $DB->GetAll('SELECT m.id, subject, body, ticketid, createtime,
                    m.type, m.type AS note,
                    contenttype, '
                    . $DB->Concat('customers.lastname', "' '", 'customers.name') . ' AS customername,
                    userid, vusers.name AS username, customerid, mailfrom, phonefrom
                FROM rtmessages m
                LEFT JOIN customers ON customers.id = customerid
                LEFT JOIN vusers ON vusers.id = userid
                WHERE ticketid in (' . $tickets . ')
                ORDER BY createtime')) {
                foreach ($content as $idx => $row) {
                    $body = $row['body'];
                    if ($comment_details) {
                        $list[$row['ticketid']]['content'][$row['id']] = array(
                            'subject' => $row['subject'],
                            'body' => $body,
                            'note' => $row['note'],
                            'createtime' => $row['createtime'],
                            'type' => $row['type'],
                            'contenttype' => $row['contenttype'],
                            'userid' => $row['userid'],
                            'username' => $row['username'],
                            'customerid' => $row['customerid'],
                            'customername' => $row['customername'],
                            'mailfrom' => $row['mailfrom'],
                            'phonefrom' => $row['phonefrom'],
                            'attachments' => array(),
                        );
                    } else {
                        $list[$row['ticketid']]['content'][$row['id']] = array(
                            'subject' => $row['subject'],
                            'body' => $body,
                            'note' => $row['note'],
                            'contenttype' => $row['contenttype'],
                            'attachments' => array(),
                        );
                    }
                    unset($content[$idx]);
                }
                $attachments = $DB->GetAll(
                    'SELECT m.ticketid, a.messageid, a.filename, a.contenttype, a.cid
                    FROM rtattachments a
                    JOIN rtmessages m ON m.id = a.messageid
                    WHERE m.ticketid IN (' . $tickets . ') AND a.cid <> ?',
                    array('')
                );
                if (!empty($attachments)) {
                    foreach ($attachments as $attachment) {
                        $message = &$list[$attachment['ticketid']]['content'][$attachment['messageid']];
                        if ($message['contenttype'] == 'text/html') {
                            if (!isset($url_prefix)) {
                                $url_prefix = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '') . '://'
                                    . $_SERVER['HTTP_HOST'] . substr(
                                        $_SERVER['REQUEST_URI'],
                                        0,
                                        strrpos($_SERVER['REQUEST_URI'], '/') + 1
                                    );
                            }
                            $message['body'] = str_ireplace(
                                '"CID:' . $attachment['cid'] . '"',
                                '"' . $url_prefix . '?m=rtmessageview&api=1&cid=' . $attachment['cid']
                                    . '&tid=' . $attachment['ticketid']. '&mid=' . $attachment['messageid']. '"',
                                $message['body']
                            );
                        }
                    }
                }
            }
        }

        $layout['pagetitle'] = trans('List of Requests');

        $SMARTY->assign('list', $list);
        $SMARTY->assign('comment_details', $comment_details);

        $SMARTY->display($extended ? 'rt/rtprinttickets-ext.html' : 'rt/rtprinttickets.html');
        break;

    default:
        $categories = $LMS->GetUserCategories(Auth::GetCurrentUser());

        $layout['pagetitle'] = trans('Reports');

        if (!ConfigHelper::checkConfig('phpui.big_networks')) {
            $SMARTY->assign('customers', $LMS->GetCustomerNames());
        }
        $SMARTY->assign('queues', $LMS->GetQueueList(array('stats' => false)));
        $SMARTY->assign('categories', $categories);
        $SMARTY->display('rt/rtprintindex.html');
        break;
}
