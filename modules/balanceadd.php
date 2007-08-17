<?php

/*
 * LMS version 1.10-cvs
 *
 *  (C) Copyright 2001-2007 LMS Developers
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

$addbalance['value'] = str_replace(',','.',$addbalance['value']);

if(isset($addbalance['time']) && $addbalance['time'] != '' && ! ereg('^[0-9]{4}/[0-9]{2}/[0-9]{2} [0-9]{2}:[0-9]{2}$', $addbalance['time']))
{
	// here we should throw error back to user about fucked up date format or something
	// otherwise mktime invokes error about expected parameters
}
elseif(isset($addbalance['time']) && $addbalance['time']!='')
{
	// date format 'yyyy/mm/dd hh:mm'	
	list($date,$time) = split(' ', $addbalance['time']);
	$date = explode('/',$date);
	$time = explode(':',$time);

	if(checkdate($date[1],$date[2],(int)$date[0])) //if date is wrong, set today's date
	{
		if (!strlen($time[0]) || !strlen($time[1]))
			$time[0] = $time[1] = 0;
		$addbalance['time'] = mktime($time[0],$time[1],0,$date[1],$date[2],$date[0]);
	}
	else
		// here too. geez, what the matter with you guys? if($user==E_LAME){ we_know_better(); } ???
		// pls, fix it
		$addbalance['time'] = time();
}
else
	$addbalance['time'] = time();

$SESSION->save('addbc', $addbalance['comment']);
$SESSION->save('addbt', $addbalance['time']);
$SESSION->save('addbv', $addbalance['value']);
$SESSION->save('addbtax', isset($addbalance['taxid']) ? $addbalance['taxid'] : 0);

if(isset($addbalance['mcustomerid']))
{
	foreach($addbalance['mcustomerid'] as $value)
		if($LMS->CustomerExists($value))
		{
			$addbalance['customerid'] = $value;
			if($addbalance['type']) 
				$addbalance['taxid'] = 0;
			
			if($addbalance['type'] == 0)
				$addbalance['value'] *= -1;
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
			
		if($addbalance['type'] == 0)
			$addbalance['value'] *= -1;
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
