<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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
	
if($DB->GetOne('SELECT rights FROM cashrights WHERE userid=? AND regid=?', array(Auth::GetCurrentUser(), $regid))<256)
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

        if(!empty($reglog['time']))
        {       
                $time = datetime_to_timestamp($reglog['time']);
                if(empty($time)) 
                	$error['time'] = trans('Wrong datetime format!');
        }
        else
                $time = time();

	if (!$error) {
		$args = array(
			'time' => $time,
			'description' => $reglog['description'],
			'value' => $reglog['value'],
			SYSLOG::RES_USER => Auth::GetCurrentUser(),
			SYSLOG::RES_CASHREGHIST => intval($_GET['id'])
		);
		$DB->Execute('UPDATE cashreglog SET time=?, description=?, value=?, userid=?
				WHERE id=?', array_values($args));
		if ($SYSLOG) {
			$args[SYSLOG::RES_CASHREG] = $regid;
			$SYSLOG->AddMessage(SYSLOG::RES_CASHREGHIST, SYSLOG::OPER_UPDATE, $args);
		}

		$SESSION->redirect('?'.$SESSION->get('backto'));
	}
}

$layout['pagetitle'] = trans('Cash History Entry Edit');

$SMARTY->assign('reglog', $reglog);
$SMARTY->assign('error', $error);
$SMARTY->display('cash/cashreglogedit.html');

?>
