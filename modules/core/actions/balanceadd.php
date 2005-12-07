<?php

/*
 * LMS version 1.8-cvs
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

$addbalance = $_POST['addbalance'];

$SESSION->save('addbc', $addbalance['comment']);
$SESSION->save('addbt', $addbalance['time']);
$SESSION->save('addbv', $addbalance['value']);
$SESSION->save('addbtax', $addbalance['taxid']);

$addbalance['value'] = str_replace(',','.',$addbalance['value']);

if($addbalance['time'])
{
	// date format 'yyyy/mm/dd hh:mm'	
	list($date,$time) = split(' ',$addbalance['time']);
	$date = explode('/',$date);
	$time = explode(':',$time);
	if(checkdate($date[1],$date[2],$date[0])) //if date is wrong, set today's date
		$addbalance['time'] = mktime($time[0],$time[1],0,$date[1],$date[2],$date[0]);
	else
		unset($addbalance['time']);
}

if(isset($addbalance['mcustomerid']))
{
	foreach($addbalance['mcustomerid'] as $value)
		if($LMS->CustomerExists($value))
		{
			$addbalance['customerid'] = $value;
			if($addbalance['type']) 
				$addbalance['taxid'] = 0;
			
			if($addbalance['value'] != 0)
				$LMS->AddBalance($addbalance);
		}
}
elseif(isset($addbalance['customerid']))
{
	if($LMS->CustomerExists($addbalance['customerid']))
	{
		if($addbalance['type']) 
			$addbalance['taxid'] = 0;
			
		if($addbalance['value'] != 0)
			$LMS->AddBalance($addbalance);
	}
}
else
{
	$addbalance['customerid'] = '0';
	$addbalance['taxid'] = '0';
	$addbalance['type'] = '1';
	
	if($addbalance['value'] != 0)
		$LMS->AddBalance($addbalance);
}

header('Location: ?'.$SESSION->get('backto'));

?>
