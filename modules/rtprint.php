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

if (!ConfigHelper::checkPrivilege('helpdesk_advanced_operations') && !ConfigHelper::checkPrivilege('reports'))
	access_denied();

$type = isset($_GET['type']) ? $_GET['type'] : '';

switch($type)
{
	case 'stats': /******************************************/

		$days  = !empty($_GET['days']) ? intval($_GET['days']) : intval($_POST['days']);
		$times = !empty($_GET['times']) ? intval($_GET['times']) : intval($_POST['times']);
		$queue = !empty($_GET['queue']) ? intval($_GET['queue']) : intval($_POST['queue']);
		$removed = !empty($_GET['removed']) ? $_GET['removed'] : $_POST['removed'];
		$categories = !empty($_GET['categories']) ? $_GET['categories'] : $_POST['categories'];
		$datefrom  = !empty($_GET['datefrom']) ? $_GET['datefrom'] : $_POST['datefrom'];
		$dateto  = !empty($_GET['dateto']) ? $_GET['dateto'] : $_POST['dateto'];
		
		if($queue)
			$where[] = 'queueid = '.$queue;
		if($days)
			$where[] = 'rttickets.createtime > '.mktime(0, 0, 0, date('n'), date('j')-$days);
		$catids = (is_array($categories) ? array_keys($categories) : NULL);
		if (!empty($catids))
			$where[] = 'tc.categoryid IN ('.implode(',', $catids).')';
		else
			$where[] = 'tc.categoryid IS NULL';

			if(!ConfigHelper::checkPrivilege('helpdesk_advanced_operations'))
			$where[] = 'rttickets.deleted = 0';
			else
			{
				if($removed != '')
				{
					if($removed == '-1')
						$where[] = 'rttickets.deleted = 0';
						else
							$where[] = 'rttickets.deleted = 1';
				}
			}
	
		if(!empty($datefrom))
		{
			$datefrom=date_to_timestamp($datefrom);
			$where[] = 'rttickets.createtime >= '.$datefrom;
		}
		else
			$datefrom = 0;

		if(!empty($dateto))
		{
			$dateto=date_to_timestamp($dateto);
			$where[] = 'rttickets.createtime <= '.$dateto;
		}
		else
			$dateto = 0;

    		if($list = $DB->GetAll('SELECT COUNT(*) AS total, customerid, '
				    .$DB->Concat('UPPER(customers.lastname)',"' '",'customers.name').' AS customername
		               	    FROM rttickets
		               	    LEFT JOIN rtticketcategories tc ON tc.ticketid = rttickets.id
				    LEFT JOIN customers ON (customerid = customers.id)
				    WHERE customerid IS NOT NULL'
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
				$list[$idx]['customer'] = isset($customer[$row['customerid']]) ? $customer[$row['customerid']]['total'] : null;
				$list[$idx]['company'] = isset($company[$row['customerid']]) ? $company[$row['customerid']]['total'] : 0;
				$list[$idx]['other'] = $list[$idx]['total'] - $list[$idx]['customer'] - $list[$idx]['company'];
			}
		}

		$layout['pagetitle'] = trans('Requests Stats');

		$SMARTY->assign('list', $list);
		$SMARTY->display('rt/rtprintstats.html');
	break;

	case 'ticketslist': /******************************************/

		$days 	  = !empty($_GET['days']) ? intval($_GET['days']) : intval($_POST['days']);
		$customer = !empty($_GET['customer']) ? intval($_GET['customer']) : intval($_POST['customer']);
		$queue 	  = !empty($_GET['queue']) ? intval($_GET['queue']) : intval($_POST['queue']);
		$status   = isset($_GET['status']) ? $_GET['status'] : $_POST['status'];
		$removed  = isset($_GET['removed']) ? $_GET['removed'] : $_POST['removed'];
		$subject  = !empty($_GET['subject']) ? $_GET['subject'] : $_POST['subject'];
		$extended = !empty($_GET['extended']) ? true : !empty($_POST['extended']) ? true : false;
		$categories = !empty($_GET['categories']) ? $_GET['categories'] : $_POST['categories'];
		$datefrom  = !empty($_GET['datefrom']) ? $_GET['datefrom'] : $_POST['datefrom'];
		$dateto  = !empty($_GET['dateto']) ? $_GET['dateto'] : $_POST['dateto'];

		if($queue)
			$where[] = 'rttickets.queueid = '.$queue;
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

		if(!ConfigHelper::checkPrivilege('helpdesk_advanced_operations'))
			$where[] = 'rttickets.deleted = 0';
			else
			{
				if($removed != '')
				{
					if($removed == '-1')
						$where[] = 'rttickets.deleted = 0';
						else
							$where[] = 'rttickets.deleted = 1';
				}
			}
		if(!empty($datefrom))
                {
                        $datefrom=date_to_timestamp($datefrom);
                        $where[] = 'rttickets.createtime >= '.$datefrom;
                }
		else
			$datefrom = 0;

                if(!empty($dateto))
                {
                        $dateto=date_to_timestamp($dateto);
                        $where[] = 'rttickets.createtime <= '.$dateto;
                }
		else
			$dateto = 0;

		$list = $DB->GetAllByKey('SELECT rttickets.id, createtime, customerid, subject, requestor, '
			.$DB->Concat('UPPER(c.lastname)',"' '",'c.name').' AS customername '
			.(!empty($_POST['contacts']) || !empty($_GET['contacts'])
				? ', city, address, (SELECT ' . $DB->GroupConcat('contact', ',', true) . '
					FROM customercontacts WHERE customerid = c.id AND (customercontacts.type & '. (CONTACT_MOBILE|CONTACT_FAX|CONTACT_LANDLINE) .' > 0 ) GROUP BY customerid) AS phones,
					(SELECT ' . $DB->GroupConcat('contact', ',', true) . '
					FROM customercontacts WHERE customerid = c.id AND (customercontacts.type & ' . CONTACT_EMAIL .' > 0)
					GROUP BY customerid) AS emails ' : '')
			.'FROM rttickets
			JOIN rtrights r ON r.queueid = rttickets.queueid AND r.rights & ' . RT_RIGHT_READ . ' > 0
			LEFT JOIN rtticketcategories tc ON tc.ticketid = rttickets.id
			LEFT JOIN customeraddressview c ON (customerid = c.id)
			WHERE 1 = 1 '
			.(isset($where) ? ' AND '.implode(' AND ', $where) : '')
			.' ORDER BY createtime', 'id');

		if ($list && $extended)
		{
			$tickets = implode(',', array_keys($list));
			if ($content = $DB->GetAll('(SELECT body, ticketid, createtime, rtmessages.type AS note
				FROM rtmessages
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
		$SMARTY->display($extended ? 'rt/rtprinttickets-ext.html' : 'rt/rtprinttickets.html');
	break;

	default:
		$categories = $LMS->GetUserCategories(Auth::GetCurrentUser());

		$layout['pagetitle'] = trans('Reports');

		if (!ConfigHelper::checkConfig('phpui.big_networks'))
			$SMARTY->assign('customers', $LMS->GetCustomerNames());
		$SMARTY->assign('queues', $LMS->GetQueueList(array('stats' => false)));
		$SMARTY->assign('categories', $categories);
		$SMARTY->display('rt/rtprintindex.html');
	break;
}

?>
