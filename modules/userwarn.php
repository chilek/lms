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

$setwarnings = $_POST['setwarnings'];

if(sizeof($setwarnings['muserid']))
{
	foreach($setwarnings['muserid'] as $uid)
	{
		if($setwarnings['warnon'])
			$LMS->NodeSetWarnU($uid, TRUE);
		if($setwarnings['warnoff']) 
			$LMS->NodeSetWarnU($uid, FALSE);
		
		if (isset($setwarnings['message']))
		{
			$LMS->SetTS('users');
			$LMS->DB->Execute('UPDATE users SET message=? WHERE id=?', array($setwarnings['message'],$uid));
		}
	}

	$_SESSION['warnmessage'] = $setwarnings['message'];
	$_SESSION['warnon'] = $setwarnings['warnon'];
	$_SESSION['warnoff'] = $setwarnings['warnoff'];
	
	header('Location: ?'.$_SESSION['backto']);
	die;
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = trans('Warnings');

$userlist = $LMS->DB->GetAll(
		    'SELECT users.id AS id, MAX(warning) AS warning, '.
		    $LMS->DB->Concat('UPPER(lastname)',"' '",'users.name').' AS username 
		    FROM users LEFT JOIN nodes ON users.id = ownerid WHERE deleted = 0 
		    GROUP BY users.id, lastname, users.name ORDER BY username ASC');

$SMARTY->assign('warnmessage', $_SESSION['warnmessage']);
$SMARTY->assign('warnon', $_SESSION['warnon']);
$SMARTY->assign('warnoff', $_SESSION['warnoff']);
$SMARTY->assign('userlist',$userlist);
$SMARTY->display('userwarnings.html');

?>

