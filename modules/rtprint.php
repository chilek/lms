<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2007 LMS Developers
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

$type = isset($_GET['type']) ? $_GET['type'] : '';

switch($type)
{
	case 'stats': /******************************************/

		$days = intval($_POST['days']);
		$times = intval($_POST['times']);
		$queue = intval($_POST['queue']);
		
		if($queue)
			$where[] = 'queueid = '.$queue;
		if($days)
			$where[] = 'rttickets.createtime > '.mktime(0, 0, 0, date('n'), date('j')-$days);
	
    		if($list = $DB->GetAll('SELECT COUNT(*) AS total, customerid, '
				    .$DB->Concat('UPPER(customers.lastname)',"' '",'customers.name').' AS customername
		               	    FROM rttickets
				    LEFT JOIN customers ON (customerid = customers.id)
				    WHERE customerid != 0'
				    .(isset($where) ? ' AND '.implode(' AND ', $where) : '')
				    .' GROUP BY customerid, customers.lastname, customers.name'
				    .($times ? ' HAVING COUNT(*) > '.$times : '')
				    .' ORDER BY total DESC'))
		{
    			$customer = $DB->GetAllByKey('SELECT COUNT(*) AS total, customerid
		               	    FROM rttickets 
				    WHERE cause = 1'
				    .(isset($where) ? ' AND '.implode(' AND ', $where) : '')
				    .' GROUP BY customerid', 'customerid');
    			$company = $DB->GetAllByKey('SELECT COUNT(*) AS total, customerid
		               	    FROM rttickets 
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

		$days = intval($_POST['days']);
		$customer = intval($_POST['customer']);
		$queue = intval($_POST['queue']);
		$status = $_POST['status'];
		
		if($queue)
			$where[] = 'queueid = '.$queue;
		if($customer)
			$where[] = 'customerid = '.$customer;
		if($days)
			$where[] = 'rttickets.createtime < '.mktime(0, 0, 0, date('n'), date('j')-$days);

		if($status != '')
		{
			if($status == -1)
				$where[] = 'rttickets.state != '.RT_RESOLVED;
			else
    				$where[] = 'rttickets.state = '.intval($status);
		}
		
    		$list = $DB->GetAll('SELECT rttickets.id, createtime, customerid, subject, requestor, '
				    .$DB->Concat('UPPER(customers.lastname)',"' '",'customers.name').' AS customername '
				    .(isset($_POST['extended']) ? ', address, (SELECT phone FROM customercontacts WHERE customerid = customers.id LIMIT 1) AS phone ' : '')
		               	    .'FROM rttickets
				    LEFT JOIN customers ON (customerid = customers.id)
				    WHERE state != '.RT_RESOLVED
				    .(isset($where) ? ' AND '.implode(' AND ', $where) : '')
				    .' ORDER BY createtime');

		$layout['pagetitle'] = trans('List of Requests');

		$SMARTY->assign('list', $list);
		$SMARTY->display('rtprinttickets.html');
	break;

	default:
		$layout['pagetitle'] = trans('Reports');
		
		if(!isset($CONFIG['phpui']['big_networks']) || !chkconfig($CONFIG['phpui']['big_networks']))
		{
			$SMARTY->assign('customers', $LMS->GetCustomerNames());
		}
		$SMARTY->assign('queues', $LMS->GetQueueList());
		$SMARTY->display('rtprintindex.html');
	break;
}

?>
