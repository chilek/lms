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

$addbalance = $_POST['addbalance'];

$_SESSION['addtype'] = $addbalance['type'];
$_SESSION['addbc'] = $addbalance['comment'];
$_SESSION['addbt'] = $addbalance['time'];
$_SESSION['addbv'] = $addbalance['value'];
if ($addbalance['taxvalue'] == 'zw.')
	$addbalance['taxvalue'] = '';
$_SESSION['addbtax'] = $addbalance['taxvalue'];

$addbalance['value'] = str_replace(',','.',$addbalance['value']);
$addbalance['taxvalue'] = str_replace(',','.',$addbalance['taxvalue']);

if($addbalance['time']) {

	// date format 'yyyy/mm/dd hh:mm'	
	list($date,$time) = split(' ',$addbalance['time']);
	$date = explode('/',$date);
	$time = explode(':',$time);
	if(checkdate($date[1],$date[2],$date[0])) //if date is wrong, set today's date
		$addbalance['time'] = mktime($time[0],$time[1],0,$date[1],$date[2],$date[0]);
	else
		unset($addbalance['time']);
}

if($addbalance['type']=='3'||$addbalance['type']=='4')
	{
		if(isset($addbalance['muserid']))
		{
			foreach($addbalance['muserid'] as $value)
				if($LMS->UserExists($value))
				{
					$addbalance['userid'] = $value;
					$LMS->AddBalance($addbalance);
				}
		}
		else
		{
			if($LMS->UserExists($addbalance['userid']))
				$LMS->AddBalance($addbalance);
		}
	}

	if($addbalance['type']=='2'||$addbalance['type']=='1')
	{
		$addbalance['userid'] = '0';
		$LMS->AddBalance($addbalance);
	}

header('Location: ?'.$_SESSION['backto']);

?>
