<?php

/*
 * LMS version 1.3-cvs
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
				$layout['pagetitle'] = 'Lista u¿ytkowników';
				$SMARTY->assign('userlist', $LMS->GetUserList($_POST['order'].','.$_POST['direction'],$_POST['filter']));
			break;
			case 1:
				$layout['pagetitle'] = 'Lista u¿ytkowników zainteresowanych ';
				$SMARTY->assign('userlist', $LMS->GetUserList($_POST['order'].','.$_POST['direction'],$_POST['filter']));
			break;
			case 2:
				$layout['pagetitle'] = 'Lista u¿ytkowników oczekuj±cych';
				$SMARTY->assign('userlist', $LMS->GetUserList($_POST['order'].','.$_POST['direction'],$_POST['filter']));
			break;
			case 3:
				$layout['pagetitle'] = 'Lista u¿ytkowników pod³±czonych';
				$SMARTY->assign('userlist', $LMS->GetUserList($_POST['order'].','.$_POST['direction'],$_POST['filter']));
			break;
			case 4: 
				$layout['pagetitle'] = 'Lista u¿ytkowników od³±czonych';
				$userlist=$LMS->GetUserList($_POST['order'].','.$_POST['direction']);
				unset($userlist['total']);
				unset($userlist['state']);
				unset($userlist['order']);
				unset($userlist['below']);
				unset($userlist['over']);
				unset($userlist['direction']);

				foreach($userlist as $idx => $row)
					if(!$row['nodeac'])
						$nuserlist[] = $userlist[$idx];
						
				$SMARTY->assign('userlist', $nuserlist);
			break;
			case 5: 
				$layout['pagetitle'] = 'Lista u¿ytkowników zad³u¿onych';
				$userlist=$LMS->GetUserList($_POST['order'].','.$_POST['direction']);
				unset($userlist['total']);
				unset($userlist['state']);
				unset($userlist['order']);
				unset($userlist['below']);
				unset($userlist['over']);
				unset($userlist['direction']);

				foreach($userlist as $idx => $row)
					if($row['balance'] < 0)
						$nuserlist[] = $userlist[$idx];
				
				$SMARTY->assign('userlist', $nuserlist);
			break;
		}		
		$SMARTY->display('printuserlist.html');
	break;

	case 'userbalance':
	
		$from = $_POST['from'];
		$to = $_POST['to'];

		// date format 'yyyy/mm/dd'	
		if($from) {
			list($year, $month, $day) = split('/',$from);
			$date['from'] = mktime(0,0,0,$month,$day,$year);
		} else { 
			$from = date("Y/m/d",time());
			$date['from'] = mktime(0,0,0); //pocz±tek dnia dzisiejszego
		}
		if($to) {
			list($year, $month, $day) = split('/',$to);
			$date['to'] = mktime(0,0,0,$month,$day,$year);
		} else { 
			$to = date("Y/m/d",time());
			$date['to'] = mktime(23,59,59); //koniec dnia dzisiejszego
		}

		$layout['pagetitle'] = 'Bilans u¿ytkownika '.$LMS->GetUserName($_POST['user']).' za okres '.$from.'-'.$to;	
		$balancelist = $LMS->GetUserBalanceListByDate($_POST['user'],$date);
		$SMARTY->assign('balancelist', $balancelist);
		$SMARTY->display('printuserbalance.html');
	break;	
	
	case 'nodelist':
		switch($_POST['filter'])
		{
			case 0:
				$layout['pagetitle'] = 'Lista komputerów';
				$SMARTY->assign('nodelist', $LMS->GetNodeList($_POST['order'].','.$_POST['direction']));
			break;
			case 1:
				$layout['pagetitle'] = 'Lista komputerów od³±czonych';
				$nodelist = $LMS->GetNodeList($_POST['order'].','.$_POST['direction']);
				unset($nodelist['total']);
				unset($nodelist['totalon']);
				unset($nodelist['totaloff']);
				unset($nodelist['order']);
				unset($nodelist['direction']);
				
				foreach($nodelist as $idx => $row)
					if(!$row['access'])
						$nnodelist[] = $nodelist[$idx];
				
				$SMARTY->assign('nodelist', $nnodelist);
			break;
			case 2:
				$layout['pagetitle'] = 'Lista komputerów pod³±czonych';
				$nodelist = $LMS->GetNodeList($_POST['order'].','.$_POST['direction']);
				unset($nodelist['total']);
				unset($nodelist['totalon']);
				unset($nodelist['totaloff']);
				unset($nodelist['order']);
				unset($nodelist['direction']);
				
				foreach($nodelist as $idx => $row)
					if($row['access'])
						$nnodelist[] = $nodelist[$idx];
				
				$SMARTY->assign('nodelist', $nnodelist);
			break;
		}	
		$SMARTY->display('printnodelist.html');
	break;
	
	default:
		$layout['pagetitle'] = 'Wydruki';
		$SMARTY->assign('users', $LMS->GetUserNames());
		$SMARTY->assign('printmenu', $_GET['menu']);
		$SMARTY->display('printindex.html');
	break;
}

?>
