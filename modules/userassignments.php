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

if(! $LMS->UserExists($_GET['id']))
{
	$SESSION->redirect('?m=userlist');
}

if($_GET['action'] == 'delete')
{
	$LMS->DeleteAssignment($_GET['aid'],$_GET['balance']);
	$SESSION->redirect('?'.$SESSION->get('backto'));
}

if($_GET['action'] == 'suspend')
{
	$LMS->SuspendAssignment($_GET['aid'], $_GET['suspend']);
	$SESSION->redirect('?'.$SESSION->get('backto'));
}

$a = $_POST['assignment'];

if($_GET['action'] == 'add' && isset($a))
{
	foreach($a as $key => $val)
		$a[$key] = trim($val);
	
	$period = sprintf('%d',$a['period']);

	if($period < 0 || $period > 3)
		$period = 1;

	switch($period)
	{
		case 0:
			$at = sprintf('%d',$a['at']);
			
			if($_CONFIG['phpui']['use_current_payday'] && $at==0)
			{
				$at = strftime('%u', time());
			}
			
			if($at < 1 || $at > 7)
				$error['at'] = trans('Incorrect day of week (1-7)!');
		break;

		case 1:
			$at = sprintf('%d',$a['at']);
			
			if($_CONFIG['phpui']['use_current_payday'] && $at==0)
				$at = date('j', time());

			$a['at'] = $at;
			
			if($at > 28 || $at < 1)
				$error['at'] = trans('Incorrect day of month (1-28)!');
		break;

		case 2:
			if(!eregi('^[0-9]{2}/[0-9]{2}$',$a['at']) && $a['at'])
			{
				$error['at'] = trans('Incorrect date format (DD/MM)');
			}
			elseif($_CONFIG['phpui']['use_current_payday'] && !$a['at'])
			{
				$d = date('j', time());
				$m = date('n', time());
				$a['at'] = $d.'/'.$m;
			}
			else
			{
				list($d,$m) = split('/',$a['at']);
			}
			
			if(!$error)
			{
				if($d>30 || $d<1 || ($d>28 && $m==2))
					$error['at'] = trans('This month doesn\'t contain specified number of days');
				if($m>3 || $m<1)
					$error['at'] = trans('Incorrect month number (max.3)');

				$at = ($m-1) * 100 + $d;
			}
		break;

		case 3:
			if(!eregi('^[0-9]{2}/[0-9]{2}$',$a['at']) && $a['at'])
			{
				$error['at'] = trans('Incorrect date format (DD/MM)');
			}
			elseif($_CONFIG['phpui']['use_current_payday'] && !$a['at'])
			{
				$d = date('j', time());
				$m = date('n', time());
				$a['at'] = $d.'/'.$m;
			}
			else
			{
				list($d,$m) = split('/',$a['at']);
			}
			
			if(!$error)
			{
				if($d>30 || $d<1 || ($d>28 && $m==2))
					$error['at'] = trans('This month doesn\'t contain specified number of days');
				if($m>12 || $m<1)
					$error['at'] = trans('Incorrect month number');
			
				$ttime = mktime(12, 0, 0, $m, $d, 1990);
				$at = date('z',$ttime) + 1;
			}
		break;
	}

	if($a['datefrom'] == '')
		$from = 0;
	elseif(eregi('^[0-9]{4}/[0-9]{2}/[0-9]{2}$',$a['datefrom']))
	{
		list($y, $m, $d) = split('/', $a['datefrom']);
		if(checkdate($m, $d, $y))
			$from = mktime(0, 0, 0, $m, $d, $y);
		else
			$error['datefrom'] = trans('Incorrect charging start time!');
	}
	else
		$error['datefrom'] = trans('Incorrect charging start time!');

	if($a['dateto'] == '')
		$to = 0;
	elseif(eregi('^[0-9]{4}/[0-9]{2}/[0-9]{2}$',$a['dateto']))
	{
		list($y, $m, $d) = split('/', $a['dateto']);
		if(checkdate($m, $d, $y))
			$to = mktime(23, 59, 59, $m, $d, $y);
		else
			$error['dateto'] = trans('Incorrect charging end time!');
	}
	else
		$error['dateto'] = trans('Incorrect charging end time!');

	if($to < $from && $to != 0 && $from != 0)
		$error['dateto'] = trans('Incorrect date range!');

	if($a['tariffid']=='')
		$error['tariffid'] = trans('Tariff not selected!');

	if($a['tariffid']==0)
	{
		unset($error['at']);
		$a['at'] = 0;
	}

	if(!$error) 
	{
		$LMS->AddAssignment(array('tariffid' => $a['tariffid'], 'userid' => $_GET['id'], 'period' => $period, 'at' => $at, 'invoice' => sprintf('%d',$a['invoice']), 'datefrom' => $from, 'dateto' => $to ));
		$SESSION->redirect('?'.$SESSION->get('backto'));
	}
}

$userinfo = $LMS->GetUser($_GET['id']);

$layout['pagetitle'] = trans('Customer Information: $0',$userinfo['username']);

$SMARTY->assign('usernodes',$LMS->GetUserNodes($userinfo['id']));
$SMARTY->assign('balancelist',$LMS->GetUserBalanceList($userinfo['id']));
$SMARTY->assign('tariffs',$LMS->GetTariffs());
$SMARTY->assign('assignments',$LMS->GetUserAssignments($_GET['id']));
$SMARTY->assign('usergroups',$LMS->UsergroupGetForUser($_GET['id']));
$SMARTY->assign('otherusergroups',$LMS->GetGroupNamesWithoutUser($_GET['id']));
$SMARTY->assign('userinfo',$userinfo);
$SMARTY->assign('recover',($_GET['action'] == 'recover' ? 1 : 0));
$SMARTY->assign('error', $error);
$SMARTY->assign('assignment', $a);
$SMARTY->display('userinfo.html');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

?>
