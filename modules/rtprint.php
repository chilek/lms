<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

if (!check_conf('privileges.reports'))
	access_denied();

$type = isset($_GET['type']) ? $_GET['type'] : '';

switch($type)
{
	case 'stats': /******************************************/

		$days  = !empty($_GET['days']) ? intval($_GET['days']) : intval($_POST['days']);
		$times = !empty($_GET['times']) ? intval($_GET['times']) : intval($_POST['times']);
		$queue = !empty($_GET['queue']) ? intval($_GET['queue']) : intval($_POST['queue']);
		$categories = !empty($_GET['categories']) ? $_GET['categories'] : $_POST['categories'];
		
		if($queue)
			$where[] = 'queueid = '.$queue;
		if($days)
			$where[] = 'rttickets.createtime > '.mktime(0, 0, 0, date('n'), date('j')-$days);
		$catids = (is_array($categories) ? array_keys($categories) : NULL);
		if (!empty($catids))
			$where[] = 'tc.categoryid IN ('.implode(',', $catids).')';
		else
			$where[] = 'tc.categoryid IS NULL';
	
    		if($list = $DB->GetAll('SELECT COUNT(*) AS total, customerid, '
				    .$DB->Concat('UPPER(customers.lastname)',"' '",'customers.name').' AS customername
		               	    FROM rttickets
		               	    LEFT JOIN rtticketcategories tc ON tc.ticketid = rttickets.id
				    LEFT JOIN customers ON (customerid = customers.id)
				    WHERE customerid != 0'
				    .(isset($where) ? ' AND '.implode(' AND ', $where) : '')
				    .' GROUP BY customerid, customers.lastname, customers.name'
				    .($times ? ' HAVING COUNT(*) > '.$times : '')
				    .' ORDER BY total DESC'))
		{
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
			
			foreach($list as $idx => $row)
			{
				$list[$idx]['customer'] = isset($customer[$row['customerid']]) ? $customer[$row['customerid']]['total'] : 0;
				$list[$idx]['company'] = isset($company[$row['customerid']]) ? $company[$row['customerid']]['total'] : 0;
				$list[$idx]['other'] = $list[$idx]['total'] - $list[$idx]['customer'] - $list[$idx]['company'];
			}
		}

		$layout['pagetitle'] = trans('Requests Stats');

		$SMARTY->assign('list', $list);
		$SMARTY->display('rtprintstats.html');
	break;

	case 'ticketslist': /******************************************/

		$days 	  = !empty($_GET['days']) ? intval($_GET['days']) : intval($_POST['days']);
		$customer = !empty($_GET['customer']) ? intval($_GET['customer']) : intval($_POST['customer']);
		$queue 	  = !empty($_GET['queue']) ? intval($_GET['queue']) : intval($_POST['queue']);
		$status   = isset($_GET['status']) ? $_GET['status'] : $_POST['status'];
		$subject  = !empty($_GET['subject']) ? $_GET['subject'] : $_POST['subject'];
		$extended = !empty($_GET['extended']) ? true : !empty($_POST['extended']) ? true : false;
		$categories = !empty($_GET['categories']) ? $_GET['categories'] : $_POST['categories'];

		if($queue)
			$where[] = 'queueid = '.$queue;
		if($customer)
			$where[] = 'customerid = '.$customer;
		if($days)
			$where[] = 'rttickets.createtime < '.mktime(0, 0, 0, date('n'), date('j')-$days);
		if($subject)
			$where[] = 'rttickets.subject ?LIKE? '.$DB->Escape("%$subject%");
		$catids = (is_array($categories) ? array_keys($categories) : NULL);
		if (!empty($catids))
			$where[] = 'tc.categoryid IN ('.implode(',', $catids).')';
		else
			$where[] = 'tc.categoryid IS NULL';

		if($status != '')
		{
			if($status == -1)
				$where[] = 'rttickets.state != '.RT_RESOLVED;
			else
    				$where[] = 'rttickets.state = '.intval($status);
		}

    		$list = $DB->GetAllByKey('SELECT rttickets.id, createtime, customerid, subject, requestor, '
			.$DB->Concat('UPPER(customers.lastname)',"' '",'customers.name').' AS customername '
			.(!empty($_POST['contacts']) || !empty($_GET['contacts'])
				? ', address, (SELECT phone
				FROM customercontacts
				WHERE customerid = customers.id LIMIT 1) AS phone ' : '')
		        .'FROM rttickets
			LEFT JOIN rtticketcategories tc ON tc.ticketid = rttickets.id
			LEFT JOIN customers ON (customerid = customers.id)
			WHERE state != '.RT_RESOLVED
			.(isset($where) ? ' AND '.implode(' AND ', $where) : '')
			.' ORDER BY createtime', 'id');

		if ($list && $extended)
		{
			$tickets = implode(',', array_keys($list));
			if ($content = $DB->GetAll('(SELECT body, ticketid, createtime, 0 AS note
				FROM rtmessages
				WHERE ticketid in ('.$tickets.'))
				UNION
				(SELECT body, ticketid, createtime, 1 AS note
				FROM rtnotes
				WHERE ticketid in ('.$tickets.'))
			        ORDER BY createtime'))
			{
				foreach ($content as $idx => $row)
				{
					$list[$row['ticketid']]['content'][] = array(
						'body' => trim($row['body']),
						'note' => $row['note'],
					);
					unset($content[$idx]);
				}
			}
		}

		$layout['pagetitle'] = trans('List of Requests');

		$SMARTY->assign('list', $list);
		$SMARTY->display($extended ? 'rtprinttickets-ext.html' : 'rtprinttickets.html');
	break;

	default:
		$categories = $LMS->GetCategoryListByUser($AUTH->id);

		$layout['pagetitle'] = trans('Reports');
		
		if(!isset($CONFIG['phpui']['big_networks']) || !chkconfig($CONFIG['phpui']['big_networks']))
		{
			$SMARTY->assign('customers', $LMS->GetCustomerNames());
		}
		$SMARTY->assign('queues', $LMS->GetQueueList());
		$SMARTY->assign('categories', $categories);
		$SMARTY->display('rtprintindex.html');
	break;
}

?>
