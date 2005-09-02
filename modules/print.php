<?php

/*
 * LMS version 1.7-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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
	case 'customerlist':

		$date = 0;
		
		if($_POST['day'])
		{
			list($year, $month, $day) = split('/',$_POST['day']);
			$date = mktime(0,0,0,$month,$day+1,$year);
		}
		
		switch($_POST['filter'])
		{
			case 0:
				$layout['pagetitle'] = trans('Customers List $0$1',($_POST['network'] ? trans(' (Net: $0)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['customergroup'] ? trans('(Group: $0)',$LMS->CustomergroupGetName($_POST['customergroup'])) : ''));
				$SMARTY->assign('customerlist', $LMS->GetCustomerList($_POST['order'].','.$_POST['direction'], $_POST['filter'], $_POST['network'], $_POST['customergroup'], NULL, $date));
			break;
			case 1:
				$layout['pagetitle'] = trans('Interested Customers List');
				$SMARTY->assign('customerlist', $LMS->GetCustomerList($_POST['order'].','.$_POST['direction'], $_POST['filter'], NULL, NULL, NULL, $date));
			break;
			case 2:
				$layout['pagetitle'] = trans('List of awaiting customers');
				$SMARTY->assign('customerlist', $LMS->GetCustomerList($_POST['order'].','.$_POST['direction'], $_POST['filter'], NULL, NULL, NULL, $date));
			break;
			case 3:
				$layout['pagetitle'] = trans('List of Connected Customers $0$1',($_POST['network'] ? trans(' (Net: $0)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['customergroup'] ? trans('(Group: $0)',$LMS->CustomergroupGetName($_POST['customergroup'])) : '')); 
				$SMARTY->assign('customerlist', $LMS->GetCustomerList($_POST['order'].','.$_POST['direction'], $_POST['filter'], $_POST['network'], $_POST['customergroup'], NULL, $date));
			break;
			case 5:
				$layout['pagetitle'] = trans('List of Disconnected Customers $0$1',($_POST['network'] ? trans(' (Net: $0)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['customergroup'] ? trans('(Group: $0)',$LMS->CustomergroupGetName($_POST['customergroup'])) : ''));
				$SMARTY->assign('customerlist', $LMS->GetCustomerList($_POST['order'].','.$_POST['direction'], $_POST['filter'], $_POST['network'], $_POST['customergroup'], NULL, $date));
			break;
			case 6:
				$layout['pagetitle'] = trans('Indebted Customers List $0$1',($_POST['network'] ? trans(' (Net: $0)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['customergroup'] ? trans('(Group: $0)',$LMS->CustomergroupGetName($_POST['customergroup'])) : ''));
				$SMARTY->assign('customerlist', $LMS->GetCustomerList($_POST['order'].','.$_POST['direction'], $_POST['filter'], $_POST['network'], $_POST['customergroup'], NULL, $date));
			break;
			case -1:
				$layout['pagetitle'] = trans('Customers Without Nodes List $0$1',($_POST['network'] ? trans(' (Net: $0)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['customergroup'] ? trans('(Group: $0)',$LMS->CustomergroupGetName($_POST['customergroup'])) : ''));
				if($customerlist = $LMS->GetCustomerList($_POST['order'].','.$_POST['direction'], NULL, NULL, $_POST['customergroup'], NULL, $date))
				{
				unset($customerlist['total']);
				unset($customerlist['state']);
				unset($customerlist['order']);
				unset($customerlist['below']);
				unset($customerlist['over']);
				unset($customerlist['direction']);

				foreach($customerlist as $idx => $row)
					if(! $row['account'])
						$ncustomerlist[] = $customerlist[$idx];
				}
				$SMARTY->assign('customerlist', $ncustomerlist);
			break;	
		}		
		$SMARTY->display('printcustomerlist.html');
	break;

	case 'customerbalance': /********************************************/
	
		$from = $_POST['from'];
		$to = $_POST['to'];

		// date format 'yyyy/mm/dd'	
		list($year, $month, $day) = split('/',$from);
		$date['from'] = mktime(0,0,0,$month,$day,$year);

		if($to) {
			list($year, $month, $day) = split('/',$to);
			$date['to'] = mktime(23,59,59,$month,$day,$year);
		} else { 
			$to = date('Y/m/d',time());
			$date['to'] = mktime(23,59,59); //koniec dnia dzisiejszego
		}

		$layout['pagetitle'] = trans('Customer $0 Balance Sheet ($1 to $2)',$LMS->GetCustomerName($_POST['customer']), ($from ? $from : ''), $to);
		
		$id = $_POST['customer'];

		if($tslist = $DB->GetAll('SELECT cash.id AS id, time, type, cash.value AS value, taxes.label AS taxlabel, customerid, comment, name AS username 
				    FROM cash 
				    LEFT JOIN taxes ON (taxid = taxes.id)
				    LEFT JOIN users ON users.id=userid 
				    WHERE customerid=? ORDER BY time', array($id))
		)
			foreach($tslist as $row)
				foreach($row as $column => $value)
					$saldolist[$column][] = $value;

		if(sizeof($saldolist['id']) > 0)
		{
			foreach($saldolist['id'] as $i => $v)
			{
				($i>0) ? $saldolist['before'][$i] = $saldolist['after'][$i-1] : $saldolist['before'][$i] = 0;

				$saldolist['value'][$i] = round($saldolist['value'][$i],3);

				switch ($saldolist['type'][$i]){

					case '3':
						$saldolist['after'][$i] = round(($saldolist['before'][$i] + $saldolist['value'][$i]),4);
						$saldolist['name'][$i] = trans('payment');
					break;

					case '4':
						$saldolist['after'][$i] = round(($saldolist['before'][$i] - $saldolist['value'][$i]),4);
						$saldolist['name'][$i] = trans('covenant');
					break;
				}

				if($saldolist['time'][$i]>=$date['from'] && $saldolist['time'][$i]<=$date['to'])
				{
					$list['id'][] = $saldolist['id'][$i];
					$list['after'][] = $saldolist['after'][$i];
					$list['before'][] = $saldolist['before'][$i];
					$list['value'][] = $saldolist['value'][$i];
					$list['taxlabel'][] = $saldolist['taxlabel'][$i];
					$list['name'][] = $saldolist['name'][$i];
					switch($saldolist['type'][$i])
					{ 
						case '3': $list['summary'] += $saldolist['value'][$i]; break;
						case '4': $list['summary'] -= $saldolist['value'][$i]; break;
					}	
					$list['date'][] = date('Y/m/d H:i',$saldolist['time'][$i]);
					$list['username'][] = $saldolist['username'][$i];
					(strlen($saldolist['comment'][$i])<3) ? $list['comment'][] = $saldolist['name'][$i] : $list['comment'][] = $saldolist['comment'][$i];
				}
			}

			$list['balance'] = $saldolist['after'][sizeof($saldolist['id'])-1];
			$list['total'] = sizeof($list['id']);

		} else
			$list['balance'] = 0;

		if($list['total'])
		{
			foreach($list['value'] as $key => $value)
				$list['value'][$key] = $value;
			foreach($list['after'] as $key => $value)
				$list['after'][$key] = $value;
			foreach($list['before'] as $key => $value)
				$list['before'][$key] = $value;
		}

		$list['customerid'] = $id;
		
		$SMARTY->assign('balancelist', $list);
		$SMARTY->display('printcustomerbalance.html');
	break;	
	
	case 'nodelist': /***********************************************/
		switch($_POST['filter'])
		{
			case 0:
				$layout['pagetitle'] = trans('Nodes List');
				$nodelist = $LMS->GetNodeList($_POST['order'].','.$_POST['direction'], NULL, NULL, $_POST['network']);
			break;
			case 1:
				$layout['pagetitle'] = trans('List of Disconnected Nodes');
				$nodelist = $LMS->GetNodeList($_POST['order'].','.$_POST['direction'], NULL, NULL, $_POST['network'], 1);
			break;
			case 2:
				$layout['pagetitle'] = trans('List of Connected Nodes');
				$nodelist = $LMS->GetNodeList($_POST['order'].','.$_POST['direction'], NULL, NULL,  $_POST['network'], 2);
			break;
			case 3:
				$layout['pagetitle'] = trans('Nodes List for Customers In Debt');

				$order=$_POST['order'].','.$_POST['direction'];
				if($order=='')
					$order='name,asc';

				list($order,$direction) = explode(',',$order);

				($direction=='desc') ? $direction = 'desc' : $direction = 'asc';

				switch($order)
				{
					case 'name':
						$sqlord = ' ORDER BY nodes.name';
					break;
					case 'id':
						$sqlord = ' ORDER BY id';
					break;
					case 'mac':
						$sqlord = ' ORDER BY mac';
					break;
				    	case 'ip':
						$sqlord = ' ORDER BY ipaddr';
					break;
					case 'ownerid':
						$sqlord = ' ORDER BY ownerid';
					break;
				    	case 'owner':
						$sqlord = ' ORDER BY owner';
					break;
				}

				if($_POST['network'])
					$net = $LMS->GetNetworkParams($_POST['network']);

				$nodelist = $DB->GetAll('SELECT nodes.id AS id, inet_ntoa(ipaddr) AS ip, mac, 
					    nodes.name AS name, nodes.info AS info, 
					    COALESCE(SUM((type * -2 + 7) * value), 0.00)/(CASE COUNT(DISTINCT nodes.id) WHEN 0 THEN 1 ELSE COUNT(DISTINCT nodes.id) END) AS balance, '
					    .$DB->Concat('UPPER(lastname)',"' '",'customers.name').' AS owner
					    FROM nodes LEFT JOIN customers ON (ownerid = customers.id)
					    LEFT JOIN cash ON (cash.customerid = customers.id)'
					    .($net ? ' WHERE ((ipaddr > '.$net['address'].' AND ipaddr < '.$net['broadcast'].') OR (ipaddr_pub > '.$net['address'].' AND ipaddr_pub < '.$net['broadcast'].'))' : '')
					    .'GROUP BY nodes.id, ipaddr, mac, nodes.name, nodes.info, customers.lastname, customers.name
					    HAVING SUM((type * -2 + 7) * value) < 0'
					    .($sqlord != '' ? $sqlord.' '.$direction : ''));
				
				$SMARTY->assign('nodelist', $nodelist);
				$SMARTY->display('printindebtnodelist.html');
				$SESSION->close();
				die;
			break;
		}	

		unset($nodelist['total']);
		unset($nodelist['order']);
		unset($nodelist['direction']);
		unset($nodelist['totalon']);
		unset($nodelist['totaloff']);
		
		$SMARTY->assign('nodelist', $nodelist);
		$SMARTY->display('printnodelist.html');
	break;

	case 'balancelist': /********************************************/
	
		$from = $_POST['balancefrom'];
		$to = $_POST['balanceto'];

		// date format 'yyyy/mm/dd'	
		list($year, $month, $day) = split('/',$from);
		$date['from'] = mktime(0,0,0,$month,$day,$year);
		
		if($to) {
			list($year, $month, $day) = split('/',$to);
			$date['to'] = mktime(23,59,59,$month,$day,$year);
		} else {
			$to = date('Y/m/d',time());
			$date['to'] = mktime(23,59,59); //koniec dnia dzisiejszego
		}

		if($user = $_POST['user'])
			$layout['pagetitle'] = trans('Balance Sheet of User: $0 ($1 to $2)', $LMS->GetUserName($user), ($from ? $from : ''), $to);
		else
			$layout['pagetitle'] = trans('Balance Sheet ($0 to $1)', ($from ? $from : ''), $to);
			
		$customerslist = $DB->GetAllByKey('SELECT id, '.$DB->Concat('UPPER(lastname)',"' '",'name').' AS customername FROM customers','id');
		
		if($date['from'])
			$lastafter = $DB->GetOne('SELECT SUM(CASE type WHEN 2 THEN value*-1 WHEN 4 THEN 0 ELSE value END) FROM cash WHERE time<?', array($date['from']));
		
		if($balancelist = $DB->GetAll('SELECT cash.id AS id, time, userid, type, cash.value AS value, taxes.label AS taxlabel, customerid, comment 
			    FROM cash LEFT JOIN taxes ON (taxid = taxes.id)
			    WHERE time>=? AND time<=? ORDER BY time ASC', array($date['from'], $date['to'])))
		{
			$x = 0;
			foreach($balancelist as $idx => $row)
			{
				if($user)
					if($row['userid']!=$user)
					{
						if($row['type']==1 || $row['type']==3)
							$lastafter += $row['value'];
						elseif($row['type']==2)
							$lastafter -= $row['value'];

						unset($balancelist[$idx]);
						continue;
					}

				$list[$x]['value'] = $row['value'];
				$list[$x]['taxlabel'] = $row['taxlabel'];
				$list[$x]['time'] = $row['time'];
				$list[$x]['comment'] = $row['comment'];
				$list[$x]['customername'] = $customerslist[$row['customerid']]['customername'];

				switch($row['type'])
				{
					case 1:
						$list[$x]['type'] = trans('income');
						$list[$x]['after'] = $lastafter + $list[$x]['value'];
						$listdata['income'] += $list[$x]['value'];
					break;
					case 2:
						$list[$x]['type'] = trans('expense');
						$list[$x]['after'] = $lastafter - $list[$x]['value'];
						$listdata['expense'] += $list[$x]['value'];
					break;
					case 3:
						$list[$x]['type'] = trans('cust. payment');
						$list[$x]['after'] = $lastafter + $list[$x]['value'];
						$listdata['incomeu'] += $list[$x]['value'];
					break;
					case 4:
						$list[$x]['type'] = trans('cust. covenant');
						$list[$x]['after'] = $lastafter;
					break;
					default:
						$list[$x]['type'] = '???';
						$list[$x]['after'] = $lastafter;
					break;
				}
				$lastafter = $list[$x]['after'];
				$x++;
				unset($balancelist[$idx]);
			}
		}
		
		$listdata['total'] = $listdata['income'] + $listdata['incomeu'] - $listdata['expense'];
		
		$SMARTY->assign('listdata', $listdata);
		$SMARTY->assign('balancelist', $list);
		$SMARTY->display('printbalancelist.html');
	break;

	case 'incomereport': /********************************************/
	
		$from = $_POST['from'];
		$to = $_POST['to'];

		// date format 'yyyy/mm/dd'	
		list($year, $month, $day) = split('/',$from);
		$date['from'] = mktime(0,0,0,$month,$day,$year);
		
		if($to) {
			list($year, $month, $day) = split('/',$to);
			$date['to'] = mktime(23,59,59,$month,$day,$year);
		} else {
			$to = date("Y/m/d",time());
			$date['to'] = mktime(23,59,59); // end of today
		}
		
		$layout['pagetitle'] = trans('Total Invoiceless Income ($0 to $1)',($from ? $from : ''), $to);
		
		$incomelist = $DB->GetAll('SELECT floor(time/86400)*86400 AS date, SUM(value) AS value
			FROM cash LEFT JOIN documents ON (docid = documents.id)
			WHERE (cash.type=1 OR cash.type=3) AND time>=? AND time<=?
			AND docid=0
			GROUP BY date ORDER BY date ASC',
			array($date['from'], $date['to']));

		$SMARTY->assign('incomelist', $incomelist);
		$SMARTY->display('printincomereport.html');
	break;

	case 'invoices': /********************************************/
	
		$from = $_POST['invoicefrom'];
		$to = $_POST['invoiceto'];

		// date format 'yyyy/mm/dd'	
		if($to) {
			list($year, $month, $day) = split('/',$to);
			$date['to'] = mktime(23,59,59,$month,$day,$year);
		} else { 
			$to = date('Y/m/d',time());
			$date['to'] = mktime(23,59,59); //koniec dnia dzisiejszego
		}

		if($from) {
			list($year, $month, $day) = split('/',$from);
			$date['from'] = mktime(0,0,0,$month,$day,$year);
		} else { 
			$from = date('Y/m/d',time());
			$date['from'] = mktime(0,0,0); //pocz±tek dnia dzisiejszego
		}

		if($_POST['invoiceorg'] && !$_POST['invoicecopy']) $witch = trans('ORIGINAL');
		if(!$_POST['invoiceorg'] && $_POST['invoicecopy']) $witch = trans('COPY');
		
		$layout['pagetitle'] = trans('Invoices');
		header('Location: ?m=invoice&fetchallinvoices=1&which='.$witch.'&customerid='.$_POST['customer'].'&from='.$date['from'].'&to='.$date['to']);
	break;	

	case 'transferforms': /********************************************/
	
		$from = $_POST['invoicefrom'];
		$to = $_POST['invoiceto'];

		// date format 'yyyy/mm/dd'	
		list($year, $month, $day) = split('/',$from);
		$date['from'] = mktime(0,0,0,$month,$day,$year);

		if($to) {
			list($year, $month, $day) = split('/',$to);
			$date['to'] = mktime(23,59,59,$month,$day,$year);
		} else { 
			$to = date('Y/m/d',time());
			$date['to'] = mktime(23,59,59); //koniec dnia dzisiejszego
		}
		
		header('Location: ?m=transferforms&customerid='.$_POST['customer'].'&from='.$date['from'].'&to='.$date['to']);
	break;	

	case 'liabilityreport': /********************************************/
	
		if(isset($_POST['day']) && $_POST['day']) 
		{
			list($year, $month, $day) = split('/',$_POST['day']);
			$reportday = mktime(0,0,0,$month,$day,$year);
		} else 
			$reportday = time();
		
		$layout['pagetitle'] = trans('Liability Report on $0',date('Y/m/d', $reportday));

		$order = (isset($_POST['order']) ? $_POST['order'] : 'brutto').','.(isset($_POST['direction']) ? $_POST['direction'] : 'asc');
		$customerid = (isset($_POST['customer']) ? $_POST['customer'] : 0);

		$yearday = date('z', $reportday);
		$month = date('n', $reportday);
		$monthday = date('j', $reportday);
		$weekday = date('w', $reportday);
		
		switch($month) 
		{
		    case 1:
		    case 4:
		    case 7:
		    case 10: $quarterday = $monthday; break;
		    case 2:
		    case 5:
		    case 8:
		    case 11: $quarterday = $monthday + 100; break;
		    default: $quarterday = $monthday + 200; break;
		}
		
		list($order,$direction)=explode(',', $order);

		($direction != 'desc') ? $direction = 'ASC' : $direction = 'DESC';

		switch($order)
		{
			case 'customername':
				$sqlord = 'ORDER BY customername';
			break;
			default:
				$sqlord = 'ORDER BY brutto';
			break;
		}
		
		if($taxes = $LMS->GetTaxes($reportday, $reportday))
			foreach($taxes as $tax)
			{
				$list =  $DB->GetAllByKey('SELECT customerid AS id, '.$DB->Concat('UPPER(lastname)',"' '",'customers.name').' AS customername, '
					.$DB->Concat('city',"' '",'address').' AS address, ten, SUM(tariffs.value) AS value  
					FROM assignments, tariffs, customers
					WHERE customerid = customers.id AND tariffid = tariffs.id AND taxid=?
					AND deleted=0 AND (datefrom<=?) AND ((dateto>=?) OR dateto=0) 
					AND ((period=0 AND at=?) OR (period=1 AND at=?) OR (period=2 AND at=?) OR (period=3 AND at=?)) '
					.($customerid ? 'AND customerid='.$customerid : ''). 
					' GROUP BY customerid, lastname, customers.name, city, address, ten '
					.($sqlord != '' ? $sqlord.' '.$direction : ''), 'id',
					array($tax['id'],$reportday, $reportday, $weekday, $monthday, $quarterday, $yearday));
				if($list)
				{
					foreach($list as $idx => $row)
					{
						if(!isset($reportlist[$idx]))
						{ 
							$reportlist[$idx]['id'] = $row['id'];
							$reportlist[$idx]['customername'] = $row['customername'];
							$reportlist[$idx]['address'] = $row['address'];
							$reportlist[$idx]['ten'] = $row['ten'];
						}
						$reportlist[$idx]['value'] += $row['value'];
						$reportlist[$idx][$tax['id']]['netto'] = round($row['value']-$row['value']*$tax['value']/100, 2);
						$reportlist[$idx][$tax['id']]['tax'] = $row['value'] - $reportlist[$idx][$tax['id']]['netto'];
						$reportlist[$idx]['taxsum'] += $reportlist[$idx][$tax['id']]['tax'];
						$total['netto'][$tax['id']] += $reportlist[$idx][$tax['id']]['netto'];
						$total['tax'][$tax['id']] += $reportlist[$idx][$tax['id']]['tax'];
					}
				}
			}

		$SMARTY->assign('reportlist', $reportlist);
		$SMARTY->assign('total',$total);
		$SMARTY->assign('taxes', $taxes);
		$SMARTY->assign('taxescount', sizeof($taxes));
		$SMARTY->display('printliabilityreport.html');
	break;

	default: /*******************************************************/
	
		$layout['pagetitle'] = trans('Printing');
		
		$yearstart = date('Y',$DB->GetOne('SELECT MIN(time) FROM cash'));
		$yearend = date('Y',$DB->GetOne('SELECT MAX(time) FROM cash'));
		for($i=$yearstart; $i<$yearend+1; $i++)
			$cashyears[] = $i;
		
		$SMARTY->assign('cashyears', $cashyears);
		$SMARTY->assign('customers', $LMS->GetCustomerNames());
		$SMARTY->assign('users', $LMS->GetUserNames());
		$SMARTY->assign('networks', $LMS->GetNetworks());
		$SMARTY->assign('customergroups', $LMS->CustomergroupGetAll());
		$SMARTY->assign('printmenu', isset($_GET['menu']) ? $_GET['menu'] : '');
		$SMARTY->display('printindex.html');
	break;
}

?>
