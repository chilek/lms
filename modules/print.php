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
	case 'userlist':

		$date = 0;
		
		if($_POST['day'])
		{
			list($year, $month, $day) = split('/',$_POST['day']);
			$date = mktime(0,0,0,$month,$day+1,$year);
		}
		
		switch($_POST['filter'])
		{
			case 0:
				$layout['pagetitle'] = trans('Customers List $0$1',($_POST['network'] ? trans(' (Net: $0)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['usergroup'] ? trans('(Group: $0)',$LMS->UsergroupGetName($_POST['usergroup'])) : ''));
				$SMARTY->assign('userlist', $LMS->GetUserList($_POST['order'].','.$_POST['direction'], $_POST['filter'], $_POST['network'], $_POST['usergroup'], NULL, $date));
			break;
			case 1:
				$layout['pagetitle'] = trans('Interested Customers List');
				$SMARTY->assign('userlist', $LMS->GetUserList($_POST['order'].','.$_POST['direction'], $_POST['filter'], NULL, NULL, NULL, $date));
			break;
			case 2:
				$layout['pagetitle'] = trans('Awaiting Customers List');
				$SMARTY->assign('userlist', $LMS->GetUserList($_POST['order'].','.$_POST['direction'], $_POST['filter'], NULL, NULL, NULL, $date));
			break;
			case 3:
				$layout['pagetitle'] = trans('Connected Customers List $0$1',($_POST['network'] ? trans(' (Net: $0)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['usergroup'] ? trans('(Group: $0)',$LMS->UsergroupGetName($_POST['usergroup'])) : '')); 
				$SMARTY->assign('userlist', $LMS->GetUserList($_POST['order'].','.$_POST['direction'], $_POST['filter'], $_POST['network'], $_POST['usergroup'], NULL, $date));
			break;
			case 5:
				$layout['pagetitle'] = trans('Disconnected Customers List $0$1',($_POST['network'] ? trans(' (Net: $0)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['usergroup'] ? trans('(Group: $0)',$LMS->UsergroupGetName($_POST['usergroup'])) : ''));
				$SMARTY->assign('userlist', $LMS->GetUserList($_POST['order'].','.$_POST['direction'], $_POST['filter'], $_POST['network'], $_POST['usergroup'], NULL, $date));
			break;
			case 6:
				$layout['pagetitle'] = trans('Indebted Customers List $0$1',($_POST['network'] ? trans(' (Net: $0)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['usergroup'] ? trans('(Group: $0)',$LMS->UsergroupGetName($_POST['usergroup'])) : ''));
				$SMARTY->assign('userlist', $LMS->GetUserList($_POST['order'].','.$_POST['direction'], $_POST['filter'], $_POST['network'], $_POST['usergroup'], NULL, $date));
			break;
			case -1:
				$layout['pagetitle'] = trans('Customers Without Nodes List $0$1',($_POST['network'] ? trans(' (Net: $0)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['usergroup'] ? trans('(Group: $0)',$LMS->UsergroupGetName($_POST['usergroup'])) : ''));
				if($userlist = $LMS->GetUserList($_POST['order'].','.$_POST['direction'], NULL, NULL, $_POST['usergroup'], NULL, $date))
				{
				unset($userlist['total']);
				unset($userlist['state']);
				unset($userlist['order']);
				unset($userlist['below']);
				unset($userlist['over']);
				unset($userlist['direction']);

				foreach($userlist as $idx => $row)
					if(! $row['account'])
						$nuserlist[] = $userlist[$idx];
				}
				$SMARTY->assign('userlist', $nuserlist);
			break;	
		}		
		$SMARTY->display('printuserlist.html');
	break;

	case 'userbalance': /********************************************/
	
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

		$layout['pagetitle'] = trans('Customer $0 Balance Sheet ($1 to $2)',$LMS->GetUserName($_POST['user']), ($from ? $from : ''), $to);
		
		$id = $_POST['user'];

		if($tslist = $LMS->DB->GetAll('SELECT cash.id AS id, time, type, value, taxvalue, userid, comment, invoiceid, name AS adminname FROM cash LEFT JOIN admins ON admins.id=adminid WHERE userid=? ORDER BY time', array($id)))
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
					$list['taxvalue'][] = $saldolist['taxvalue'][$i];
					$list['name'][] = $saldolist['name'][$i];
					switch($saldolist['type'][$i])
					{ 
						case '3': $list['summary'] += $saldolist['value'][$i]; break;
						case '4': $list['summary'] -= $saldolist['value'][$i]; break;
					}	
					$list['date'][] = date('Y/m/d H:i',$saldolist['time'][$i]);
					$list['adminname'][] = $saldolist['adminname'][$i];
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

		$list['userid'] = $id;
		
		$SMARTY->assign('balancelist', $list);
		$SMARTY->display('printuserbalance.html');
	break;	
	
	case 'nodelist': /***********************************************/
		switch($_POST['filter'])
		{
			case 0:
				$layout['pagetitle'] = trans('Nodes List');
				$SMARTY->assign('nodelist', $LMS->GetNodeList($_POST['order'].','.$_POST['direction']));
			break;
			case 1:
				$layout['pagetitle'] = trans('Disconnected Nodes List');
				if($nodelist = $LMS->GetNodeList($_POST['order'].','.$_POST['direction']))
				{
				unset($nodelist['total']);
				unset($nodelist['totalon']);
				unset($nodelist['totaloff']);
				unset($nodelist['order']);
				unset($nodelist['direction']);
				
				foreach($nodelist as $idx => $row)
					if(!$row['access'])
						$nnodelist[] = $nodelist[$idx];
				}
				$SMARTY->assign('nodelist', $nnodelist);
			break;
			case 2:
				$layout['pagetitle'] = trans('Connected Nodes List');
				if($nodelist = $LMS->GetNodeList($_POST['order'].','.$_POST['direction']))
				{
				unset($nodelist['total']);
				unset($nodelist['totalon']);
				unset($nodelist['totaloff']);
				unset($nodelist['order']);
				unset($nodelist['direction']);
				
				foreach($nodelist as $idx => $row)
					if($row['access'])
						$nnodelist[] = $nodelist[$idx];
				}
				$SMARTY->assign('nodelist', $nnodelist);
			break;
			case 3:
				$layout['pagetitle'] = trans('In Debt Customer\'s Nodes List');

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

				$nodelist = $LMS->DB->GetAll('SELECT nodes.id AS id, inet_ntoa(ipaddr) AS ip, mac, 
					    nodes.name AS name, nodes.info AS info, 
					    COALESCE(SUM((type * -2 + 7) * value), 0.00)/(CASE COUNT(DISTINCT nodes.id) WHEN 0 THEN 1 ELSE COUNT(DISTINCT nodes.id) END) AS balance, '
					    .$LMS->DB->Concat('UPPER(lastname)',"' '",'users.name').' AS owner
					    FROM nodes LEFT JOIN users ON (ownerid = users.id)
					    LEFT JOIN cash ON (cash.userid = users.id)
					    GROUP BY nodes.id, ipaddr, mac, nodes.name, nodes.info, users.lastname, users.name
					    HAVING SUM((type * -2 + 7) * value) < 0'
					    .($sqlord != '' ? $sqlord.' '.$direction : ''));
				
				$SMARTY->assign('nodelist', $nodelist);
				$SMARTY->display('printindebtnodelist.html');
				$SESSION->close();
				die;
			break;
		}	
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

		if($admin = $_POST['admin'])
			$layout['pagetitle'] = trans('Balance Sheet of User: $0 ($1 to $2)', $LMS->GetAdminName($admin), ($from ? $from : ''), $to);
		else
			$layout['pagetitle'] = trans('Balance Sheet ($0 to $1)', ($from ? $from : ''), $to);
			
		$userslist = $DB->GetAllByKey('SELECT id, '.$DB->Concat('UPPER(lastname)',"' '",'name').' AS username FROM users','id');
		
		if($date['from'])
			$lastafter = $DB->GetOne('SELECT SUM(CASE type WHEN 2 THEN value*-1 WHEN 4 THEN 0 ELSE value END) FROM cash WHERE time<?', array($date['from']));
		
		if($balancelist = $DB->GetAll('SELECT id, time, adminid, type, value, taxvalue, userid, comment 
			    FROM cash WHERE time>=? AND time<=? ORDER BY time ASC', array($date['from'], $date['to'])))
		{
			$x = 0;
			foreach($balancelist as $idx => $row)
			{
				if($admin)
					if($row['adminid']!=$admin)
					{
						if($row['type']==1 || $row['type']==3)
							$lastafter += $row['value'];
						elseif($row['type']==2)
							$lastafter -= $row['value'];

						unset($balancelist[$idx]);
						continue;
					}

				$list[$x]['value'] = $row['value'];
				$list[$x]['taxvalue'] = $row['taxvalue'];
				$list[$x]['time'] = $row['time'];
				$list[$x]['comment'] = $row['comment'];
				$list[$x]['username'] = $userslist[$row['userid']]['username'];

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
			$date['to'] = mktime(23,59,59); //koniec dnia dzisiejszego
		}
		
		$layout['pagetitle'] = trans('Total Invoiceless Income ($0 to $1)',($from ? $from : ''), $to);

		$incomelist = $LMS->GetIncomeList($date);
		$totalincomelist = $LMS->GetTotalIncomeList($date);
		$SMARTY->assign('incomelist', $incomelist);
		$SMARTY->assign('totalincomelist', $totalincomelist);
		$SMARTY->display('printincomereport.html');
	break;

	case 'invoices': /********************************************/
	
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

		if($_POST['invoiceorg'] && !$_POST['invoicecopy']) $witch = trans('ORIGINAL');
		if(!$_POST['invoiceorg'] && $_POST['invoicecopy']) $witch = trans('COPY');
		
		$layout['pagetitle'] = trans('Invoices');
		header('Location: ?m=invoice&fetchallinvoices=1&which='.$witch.'&userid='.$_POST['user'].'&from='.$date['from'].'&to='.$date['to']);
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
		$userid = (isset($_POST['user']) ? $_POST['user'] : 0);

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
			case 'username':
				$sqlord = 'ORDER BY username';
			break;
			default:
				$sqlord = 'ORDER BY brutto';
			break;
		}
		
		if($reportlist =  $LMS->DB->GetAll('SELECT userid, '.$LMS->DB->Concat('UPPER(lastname)',"' '",'users.name').' AS username, '
			    .$LMS->DB->Concat('city',"' '",'address').' AS address, nip, 
			    SUM(CASE taxvalue WHEN 22.00 THEN value ELSE 0 END) AS val22,  
			    SUM(CASE taxvalue WHEN 7.00 THEN value ELSE 0 END) AS val7, 
			    SUM(CASE taxvalue WHEN 0.00 THEN value ELSE 0 END) AS val0, 
			    SUM(CASE WHEN taxvalue IS NULL THEN value ELSE 0 END) AS valfree,
			    SUM(value) AS brutto  
			    FROM assignments, tariffs, users  
			    WHERE userid = users.id AND tariffid = tariffs.id 
			    AND deleted=0 AND (datefrom<=?) AND ((dateto>=?) OR dateto=0) 
			    AND ((period=0 AND at=?) OR (period=1 AND at=?) OR (period=2 AND at=?) OR (period=3 AND at=?)) '
			    .($userid ? 'AND userid='.$userid : ''). 
			    ' GROUP BY userid, lastname, users.name, city, address, nip '
			    .($sqlord != '' ? $sqlord.' '.$direction : ''),
			    array($reportday, $reportday, $weekday, $monthday, $quarterday, $yearday))
		)
			foreach($reportlist as $idx => $row)
			{
				$reportlist[$idx]['tax7'] = round($row['val7']-$row['val7']/1.07, 2);
				$reportlist[$idx]['tax22'] = round($row['val22']-$row['val22']/1.22,2);
				$reportlist[$idx]['netto7'] = $row['val7'] - $reportlist[$idx]['tax7'];
				$reportlist[$idx]['netto22'] = $row['val22'] - $reportlist[$idx]['tax22'];
				$reportlist[$idx]['taxsum'] = $reportlist[$idx]['tax22'] + $reportlist[$idx]['tax7'];
			}

		$SMARTY->assign('reportlist', $reportlist);
		$SMARTY->display('printliabilityreport.html');
	break;

	case 'covenantreport': /********************************************/
	
		$layout['pagetitle'] = trans('Covenants Realization Report - $0', $_POST['year']);
		
		if($_POST['user'])
			$layout['pagetitle'] .= '<BR>'.$LMS->GetUserName($_POST['user']);
	
		$from = mktime(0,0,0,1,1,$_POST['year']);
		$to = mktime(0,0,0,1,1,$_POST['year']+1);

		$payments = $LMS->DB->GetAllByKey('SELECT SUM(value) AS value, invoiceid AS id
					FROM cash
					WHERE invoiceid > 0 AND type = 3 AND time >= ?'
					.($_POST['user'] ? ' AND userid = '.$_POST['user'] : '')
					.' GROUP BY invoiceid', 'id', array($from));
					
		if($invoices = $LMS->DB->GetAll('SELECT SUM(value) AS value, MIN(time) AS time, invoiceid AS id
					FROM cash
					WHERE invoiceid > 0 AND type = 4 AND time >= ? AND time < ?'
					.($_POST['user'] ? ' AND userid = '.$_POST['user'] : '')
					.' GROUP BY invoiceid', array($from, $to)))

			foreach($invoices as $row)
			{
				$month = date('n', $row['time']);
				$list[$month]['covenant'] += $row['value'];
				$list[$month]['payment'] += $payments[$row['id']]['value'];
				$list[$month]['diff'] = $list[$month]['diff'] - $row['value'] + $payments[$row['id']]['value'];
				$list['totalcovenant'] += $row['value'];
				$list['totalpayment'] += $payments[$row['id']]['value'];
			}	
			$list['totaldiff'] = $list['totalpayment'] - $list['totalcovenant']; 
			
		for($i=1; $i<13; $i++) $months[$i] = strftime('%B', mktime(0,0,0,$i,1,1970));

		$SMARTY->assign('list', $list);
		$SMARTY->assign('monthnames', $months);
		$SMARTY->assign('monthlist', array(1,2,3,4,5,6,7,8,9,10,11,12));
		$SMARTY->display('printcovenantreport.html');
	break;
		
	default: /*******************************************************/
	
		$layout['pagetitle'] = trans('Printing');
		
		$yearstart = date('Y',$LMS->DB->GetOne('SELECT MIN(time) FROM cash'));
		$yearend = date('Y',$LMS->DB->GetOne('SELECT MAX(time) FROM cash'));
		for($i=$yearstart; $i<$yearend+1; $i++)
			$cashyears[] = $i;
		
		$SMARTY->assign('cashyears', $cashyears);
		$SMARTY->assign('users', $LMS->GetUserNames());
		$SMARTY->assign('admins', $LMS->GetAdminNames());
		$SMARTY->assign('networks', $LMS->GetNetworks());
		$SMARTY->assign('usergroups', $LMS->UsergroupGetAll());
		$SMARTY->assign('printmenu', isset($_GET['menu']) ? $_GET['menu'] : '');
		$SMARTY->display('printindex.html');
	break;
}

?>
