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

$layout['pagetitle'] = 'Wydruki';

switch($_GET['type'])
{
	case 'userlist':
		switch($_POST['filter'])
		{
			case 0:
				$layout['pagetitle'] = 'Lista u�ytkownik�w';
				$SMARTY->assign('userlist',$LMS->GetUserList($_POST['order'].','.$_POST['direction'],$_POST['filter']));
			break;
			case 1:
				$layout['pagetitle'] = 'Lista u�ytkownik�w zainteresowanych ';
				$SMARTY->assign('userlist',$LMS->GetUserList($_POST['order'].','.$_POST['direction'],$_POST['filter']));
			break;
			case 2:
				$layout['pagetitle'] = 'Lista u�ytkownik�w oczekuj�cych';
				$SMARTY->assign('userlist',$LMS->GetUserList($_POST['order'].','.$_POST['direction'],$_POST['filter']));
			break;
			case 3:
				$layout['pagetitle'] = 'Lista u�ytkownik�w pod��czonych';
				$SMARTY->assign('userlist',$LMS->GetUserList($_POST['order'].','.$_POST['direction'],$_POST['filter']));
			break;
			case 4: 
				$layout['pagetitle'] = 'Lista u�ytkownik�w od��czonych';
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
				$layout['pagetitle'] = 'Lista u�ytkownik�w zad�u�onych';
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
	
	case 'nodelist':
		$layout['pagetitle'] = 'Lista komputer�w';
		$SMARTY->assign('nodelist',$LMS->GetNodeList($_SESSION['nlo']));
		$SMARTY->display('printnodelist.html');
	break;
	
	default:
		$SMARTY->assign('printmenu',$_GET['menu']);
		$SMARTY->display('printindex.html');
	break;
}

?>
