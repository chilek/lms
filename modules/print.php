<?php

/*
 * LMS version 1.5-cvs
 *
 *  (C) Copyright 2001-2004 LMS Developers
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
				$layout['pagetitle'] = 'Lista u�ytkownik�w'.($_POST['network'] ? ' sieci '.$LMS->GetNetworkName($_POST['network']) : '').($_POST['usergroup'] ? ' w grupie '.$LMS->UsergroupGetName($_POST['usergroup']) : '');
				$SMARTY->assign('userlist', $LMS->GetUserList($_POST['order'].','.$_POST['direction'], $_POST['filter'], $_POST['network'], $_POST['usergroup']));
			break;
			case 1:
				$layout['pagetitle'] = 'Lista u�ytkownik�w zainteresowanych ';
				$SMARTY->assign('userlist', $LMS->GetUserList($_POST['order'].','.$_POST['direction'], $_POST['filter']));
			break;
			case 2:
				$layout['pagetitle'] = 'Lista u�ytkownik�w oczekuj�cych';
				$SMARTY->assign('userlist', $LMS->GetUserList($_POST['order'].','.$_POST['direction'], $_POST['filter']));
			break;
			case 3:
				$layout['pagetitle'] = 'Lista u�ytkownik�w pod��czonych'.($_POST['network'] ? ' do sieci '.$LMS->GetNetworkName($_POST['network']) : '').($_POST['usergroup'] ? ' w grupie '.$LMS->UsergroupGetName($_POST['usergroup']) : '');
				$SMARTY->assign('userlist', $LMS->GetUserList($_POST['order'].','.$_POST['direction'], $_POST['filter'], $_POST['network'], $_POST['usergroup']));
			break;
			case 4: 
				$layout['pagetitle'] = 'Lista u�ytkownik�w od��czonych'.($_POST['network'] ? ' od sieci '.$LMS->GetNetworkName($_POST['network']) : '').($_POST['usergroup'] ? ' w grupie '.$LMS->UsergroupGetName($_POST['usergroup']) : '');
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
				$layout['pagetitle'] = 'Lista u�ytkownik�w zad�u�onych'.($_POST['network'] ? ' w sieci '.$LMS->GetNetworkName($_POST['network']) : '').($_POST['usergroup'] ? ' w grupie '.$LMS->UsergroupGetName($_POST['usergroup']) : '');
				if($userlist=$LMS->GetUserList($_POST['order'].','.$_POST['direction'], NULL, $_POST['network'], $_POST['usergroup']))
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
				$layout['pagetitle'] = 'Lista u�ytkownik�w bez komputer�w'.($_POST['usergroup'] ? ' w grupie '.$LMS->UsergroupGetName($_POST['usergroup']) : '');
				if($userlist=$LMS->GetUserList($_POST['order'].','.$_POST['direction'], NULL, NULL, $_POST['usergroup']))
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
			$date['to'] = mktime(0,0,0,$month,$day,$year);
		} else { 
			$to = date("Y/m/d",time());
			$date['to'] = mktime(23,59,59); //koniec dnia dzisiejszego
		}

		$layout['pagetitle'] = 'Bilans u�ytkownika '.$LMS->GetUserName($_POST['user']).' za okres '.($from ? 'od '.$from.' ' : '').'do '.$to;	
		$balancelist = $LMS->GetUserBalanceListByDate($_POST['user'],$date);
		$SMARTY->assign('balancelist', $balancelist);
		$SMARTY->display('printuserbalance.html');
	break;	
	
	case 'nodelist': /***********************************************/
		switch($_POST['filter'])
		{
			case 0:
				$layout['pagetitle'] = 'Lista komputer�w';
				$SMARTY->assign('nodelist', $LMS->GetNodeList($_POST['order'].','.$_POST['direction']));
			break;
			case 1:
				$layout['pagetitle'] = 'Lista komputer�w od��czonych';
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
				$layout['pagetitle'] = 'Lista komputer�w pod��czonych';
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
		$date['from'] = mktime(0,0,1,$month,$day,$year);
		
		if($to) {
			list($year, $month, $day) = split('/',$to);
			$date['to'] = mktime(0,0,0,$month,$day,$year);
		} else {
			$to = date("Y/m/d",time());
			$date['to'] = mktime(23,59,59); //koniec dnia dzisiejszego
		}
		
		$admin = $_POST['admin'];
		
		$layout['pagetitle'] = 'Bilans finansowy '.($admin ? 'dla administratora '.$LMS->GetAdminName($admin).' ' : '').'za okres '.($from ? ' od '.$from.' ' : '').'do '.$to;

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
						$list[$x]['type'] = 'przych�d';
						$list[$x]['after'] = $lastafter + $list[$x]['value'];
						$listdata['income'] += $list[$x]['value'];
					break;
					case 2:
						$list[$x]['type'] = 'rozch�d';
						$list[$x]['after'] = $lastafter - $list[$x]['value'];
						$listdata['expense'] += $list[$x]['value'];
					break;
					case 3:
						$list[$x]['type'] = 'wp�ata u�.';
						$list[$x]['after'] = $lastafter + $list[$x]['value'];
						$listdata['incomeu'] += $list[$x]['value'];
					break;
					case 4:
						$list[$x]['type'] = 'obci��enie u�.';
						$list[$x]['after'] = $lastafter;
					break;
					default:
						$list[$x]['type'] = '<FONT COLOR="RED">???</FONT>';
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
			$date['to'] = mktime(0,0,0,$month,$day,$year);
		} else {
			$to = date("Y/m/d",time());
			$date['to'] = mktime(23,59,59); //koniec dnia dzisiejszego
		}
		
		$layout['pagetitle'] = '��czny przych�d bezrachunkowy za okres '.($from ? ' od '.$from.' ' : '').'do '.$to;

		$incomelist = $LMS->GetIncomeList($date);
		$totalincomelist = $LMS->GetTotalIncomeList($date);
		$SMARTY->assign('incomelist', $incomelist);
		$SMARTY->assign('totalincomelist', $totalincomelist);
		$SMARTY->display('printincomereport.html');
	break;

	case 'liabilityreport': /********************************************/
	
		if($_POST['day']) {
			list($year, $month, $day) = split('/',$_POST['day']);
			$reportday = mktime(0,0,0,$month,$day,$year);
		} else 
			$reportday = time();
		
		$layout['pagetitle'] = 'Raport wierzytelno�ci na dzie� '.date('Y/m/d', $reportday);

		$SMARTY->assign('reportlist', $LMS->LiabilityReport($reportday, $_POST['order'].','.$_POST['direction'], $_POST['user']));
		$SMARTY->display('printliabilityreport.html');
	break;
		
	default: /*******************************************************/
		$layout['pagetitle'] = 'Wydruki';
		$SMARTY->assign('users', $LMS->GetUserNames());
		$SMARTY->assign('admins', $LMS->GetAdminNames());
		$SMARTY->assign('networks', $LMS->GetNetworks());
		$SMARTY->assign('usergroups', $LMS->UsergroupGetAll());
		$SMARTY->assign('printmenu', $_GET['menu']);
		$SMARTY->display('printindex.html');
	break;
}

?>
