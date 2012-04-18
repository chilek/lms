<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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

$reglog = $DB->GetRow('SELECT * FROM cashreglog WHERE id = ?', array(intval($_GET['id'])));

$regid = $reglog['regid'];
$reglog['time'] = strftime('%Y/%m/%d %H:%M', $reglog['time']);

if(!$regid)
{
        $SESSION->redirect('?m=cashreglist');
}
	
if($DB->GetOne('SELECT rights FROM cashrights WHERE userid=? AND regid=?', array($AUTH->id, $regid))<256)
{
        $SMARTY->display('noaccess.html');
        $SESSION->close();
        die;
}

if(isset($_POST['reglog']))
{
	$reglog = $_POST['reglog'];
	
	foreach($reglog as $key => $value)
	        $reglog[$key] = trim($value);

	$reglog['value'] = str_replace(',','.', $reglog['value']);

	if($reglog['value'] == '')
		$error['value'] = trans('Cash state value is required!');
	elseif(!preg_match('/^[-]?[0-9.,]+$/', $reglog['value']))
	        $error['value'] = trans('Incorrect value!');

	if($reglog['time'])
	{
		if(preg_match('/^([0-9]{4}\/[0-9]{2}\/[0-9]{2})\s+([0-9]{2}:[0-9]{2})$/', $reglog['time'], $matches))
		{
	    		// date format 'yyyy/mm/dd hh:mm'
			$date = explode('/', $matches[1]);
			$time = explode(':', $matches[2]);

			if(checkdate($date[1],$date[2],(int)$date[0]))
			{
		    		if (!strlen($time[0]) || !strlen($time[1]))
		    			$time[0] = $time[1] = 0;
				$time = mktime($time[0],$time[1],0,$date[1],$date[2],$date[0]);
			}
			else
				$error['time'] = trans('Wrong datetime format!');
		}
		else
			$error['time'] = trans('Wrong datetime format!');    
	}
	else
		$time = time();

	if(!$error)
	{
		$DB->Execute('UPDATE cashreglog SET time=?, description=?, value=?, userid=?
				WHERE id=?',
				array($time,
					$reglog['description'],
					$reglog['value'],
					$AUTH->id,
					intval($_GET['id'])
				));
				
		$SESSION->redirect('?'.$SESSION->get('backto'));
	}
}

$layout['pagetitle'] = trans('Cash History Entry Edit');

$SMARTY->assign('reglog', $reglog);
$SMARTY->assign('error', $error);
$SMARTY->display('cashreglogedit.html');

?>
