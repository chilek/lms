<?php

/*
 * LMS version 1.5-cvs
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

switch($_GET['type'])
{
	case 'userlist':
		switch($_POST['filter'])
		{
			case 0:
				$layout['pagetitle'] = trans('Users List $0$1',($_POST['network'] ? trans(' (Net: $0)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['usergroup'] ? trans('(Group: $0)',$LMS->UsergroupGetName($_POST['usergroup'])) : ''));
				$SMARTY->assign('userlist', $LMS->GetUserList($_POST['order'].','.$_POST['direction'], $_POST['filter'], $_POST['network'], $_POST['usergroup']));
			break;
			case 1:
				$layout['pagetitle'] = trans('Interested Users List');
				$SMARTY->assign('userlist', $LMS->GetUserList($_POST['order'].','.$_POST['direction'], $_POST['filter']));
			break;
			case 2:
				$layout['pagetitle'] = trans('Awaiting Users List');
				$SMARTY->assign('userlist', $LMS->GetUserList($_POST['order'].','.$_POST['direction'], $_POST['filter']));
			break;
			case 3:
				$layout['pagetitle'] = trans('Connected Users List $0$1',($_POST['network'] ? trans(' (Net: $0)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['usergroup'] ? trans('(Group: $0)',$LMS->UsergroupGetName($_POST['usergroup'])) : '')); 
				$SMARTY->assign('userlist', $LMS->GetUserList($_POST['order'].','.$_POST['direction'], $_POST['filter'], $_POST['network'], $_POST['usergroup']));
			break;
			case 4: 
				$layout['pagetitle'] = trans('Disconnected Users List $0$1',($_POST['network'] ? trans(' (Net: $0)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['usergroup'] ? trans('(Group: $0)',$LMS->UsergroupGetName($_POST['usergroup'])) : ''));
				if($userlist=$LMS->GetUserList($_POST['order'].','.$_POST['direction'], NULL, $_POST['network'], $_POST['usergroup']))
				{
				unset($userlist['total']);
				unset($userlist['state']);
				unset($userlist['order']);
				unset($userlist['below']);
				unset($userlist['over']);
				unset($userlist['direction']);

				foreach($userlist as $idx => $row)
					if(!$row['nodeac'])
						$nuserlist[] = $userlist[$idx];
				}		
				$SMARTY->assign('userlist', $nuserlist);
			break;
			case 5: 
				$layout['pagetitle'] = trans('Indebted Users List $0$1',($_POST['network'] ? trans(' (Net: $0)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['usergroup'] ? trans('(Group: $0)',$LMS->UsergroupGetName($_POST['usergroup'])) : ''));
				if($userlist = $LMS->GetUserList($_POST['order'].','.$_POST['direction'], NULL, $_POST['network'], $_POST['usergroup']))
				{
				unset($userlist['total']);
				unset($userlist['state']);
				unset($userlist['order']);
				unset($userlist['below']);
				unset($userlist['over']);
				unset($userlist['direction']);

				foreach($userlist as $idx => $row)
					if($row['balance'] < 0)
						$nuserlist[] = $userlist[$idx];
				}
				$SMARTY->assign('userlist', $nuserlist);
			break;
			case 6: 
				$layout['pagetitle'] = trans('Users Without Nodes List $0$1',($_POST['network'] ? trans(' (Net: $0)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['usergroup'] ? trans('(Group: $0)',$LMS->UsergroupGetName($_POST['usergroup'])) : ''));
				if($userlist = $LMS->GetUserList($_POST['order'].','.$_POST['direction'], NULL, NULL, $_POST['usergroup']))
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
		$balancelist = $LMS->GetUserBalanceListByDate($_POST['user'],$date);
		$SMARTY->assign('balancelist', $balancelist);
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

		if($_POST['invoiceorg'] && !$_POST['invoicecopy']) $witch = 'ORYGINA£';
		if(!$_POST['invoiceorg'] && $_POST['invoicecopy']) $witch = 'KOPIA';
		
		$layout['pagetitle'] = trans('Invoices');
		header('Location: ?m=invoice&fetchallinvoices=1&which='.$witch.'&userid='.$_POST['user'].'&from='.$date['from'].'&to='.$date['to']);
	break;	

	case 'liabilityreport': /********************************************/
	
		if($_POST['day']) {
			list($year, $month, $day) = split('/',$_POST['day']);
			$reportday = mktime(0,0,0,$month,$day,$year);
		} else 
			$reportday = time();
		
		$layout['pagetitle'] = trans('Liability Report on $0',date('Y/m/d', $reportday));

		$SMARTY->assign('reportlist', $LMS->LiabilityReport($reportday, $_POST['order'].','.$_POST['direction'], $_POST['user']));
		$SMARTY->display('printliabilityreport.html');
	break;
		
	default: /*******************************************************/
		$layout['pagetitle'] = trans('Printing');
		$SMARTY->assign('users', $LMS->GetUserNames());
		$SMARTY->assign('admins', $LMS->GetAdminNames());
		$SMARTY->assign('networks', $LMS->GetNetworks());
		$SMARTY->assign('usergroups', $LMS->UsergroupGetAll());
		$SMARTY->assign('printmenu', $_GET['menu']);
		$SMARTY->display('printindex.html');
	break;
}

?>
