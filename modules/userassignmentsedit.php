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

$userid = $LMS->DB->GetOne('SELECT userid FROM assignments WHERE id=?', array($_GET['id']));

if(!$userid)
{
	header('Location: ?m='.$_SESSION['backto']);
	die;
}

if($a = $_POST['assignmentedit'])
{
	foreach($a as $key => $val)
		$a[$key] = trim($val);
	
	$a['id'] = $_GET['id'];

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
				$error['editat'] = trans('Incorrect day of week (1-7)!');
		break;

		case 1:
			$at = sprintf('%d',$a['at']);
			
			if($_CONFIG['phpui']['use_current_payday'] && $at==0)
				$at = date('j', time());

			$a['at'] = $at;
			
			if($at > 28 || $at < 1)
				$error['editat'] = trans('Incorrect day of month (1-28)!');
		break;

		case 2:
			if(!eregi('^[0-9]{2}/[0-9]{2}$',$a['at']) && $a['at'])
			{
				$error['editat'] = trans('Incorrect date format (DD/MM)');
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
					$error['editat'] = trans('This month doesn\'t contain specified number of days');
				if($m>3 || $m<1)
					$error['editat'] = trans('Incorrect month number (max.3)');

				$at = ($m-1) * 100 + $d;
			}
		break;

		case 3:
			if(!eregi('^[0-9]{2}/[0-9]{2}$',$a['at']) && $a['at'])
			{
				$error['editat'] = trans('Incorrect date format (DD/MM)');
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
					$error['editat'] = trans('This month doesn\'t contain specified number of days');
				if($m>12 || $m<1)
					$error['editat'] = trans('Incorrect month number');
			
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
			$error['editdatefrom'] = trans('Incorrect charging start time!');
	}
	else
		$error['editdatefrom'] = trans('Incorrect charging start time!');

	if($a['dateto'] == '')
		$to = 0;
	elseif(eregi('^[0-9]{4}/[0-9]{2}/[0-9]{2}$',$a['dateto']))
	{
		list($y, $m, $d) = split('/', $a['dateto']);
		if(checkdate($m, $d, $y))
			$to = mktime(23, 59, 59, $m, $d, $y);
		else
			$error['editdateto'] = trans('Incorrect charging end time!');
	}
	else
		$error['editdateto'] = trans('Incorrect charging end time!');

	if($to < $from && $to != 0 && $from != 0)
		$error['editdateto'] = trans('Incorrect date range!');

	if(!$error) 
	{
		$LMS->DB->Execute('UPDATE assignments SET tariffid=?, userid=?, period=?, at=?, invoice=?, datefrom=?, dateto=? WHERE id=?',
			    array(  $a['tariffid'], 
				    $userid, 
				    $period, 
				    $at, 
				    sprintf('%d',$a['invoice']), 
				    $from, 
				    $to,
				    $a['id'] ));
		$LMS->SetTS('assignments');
		header('Location: ?'.$_SESSION['backto']);
		die;
	}
}
else
{
	$a = $LMS->DB->GetRow('SELECT assignments.id AS id, userid, tariffid, tariffs.name AS name, period, at, datefrom, dateto, value, invoice, '
				.$LMS->DB->Concat('users.lastname',"' '",'users.name').' AS username
				FROM assignments, users, tariffs 
				WHERE assignments.userid = users.id 
				AND tariffs.id = tariffid 
				AND assignments.id=?',array($_GET['id']));
				
	if($a['dateto']) 
		$a['dateto'] = date('Y/m/d', $a['dateto']);
	if($a['datefrom'])
		$a['datefrom'] = date('Y/m/d', $a['datefrom']);
	
	switch($a['period'])
	{
		case 2:
			$a['at'] = sprintf('%02d/%02d',$a['at']%100,$a['at']/100+1);
			break;
		case 3:
			$a['at'] = date('d/m',($a['at']-1)*86400);
			break;
	}
}

$layout['pagetitle'] = trans('Customer Charging Edit: $0',$a['username']);

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

$SMARTY->assign('tariffs', $LMS->GetTariffs());
$SMARTY->assign('error', $error);
$SMARTY->assign('assignmentedit', $a);
$SMARTY->assign('assignments', $LMS->GetUserAssignments($userid));
$balancelist['userid'] = $userid;
$SMARTY->assign('balancelist', $balancelist);
$SMARTY->display('userassignmentsedit.html');

?>
