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

if(sizeof($setwarnings['mnodeid']))
{
	foreach($setwarnings['mnodeid'] as $value)
	{
		if($setwarnings['warnon'])
			$LMS->NodeSetWarn($value, TRUE);
		if($setwarnings['warnoff']) 
			$LMS->NodeSetWarn($value, FALSE);
		
		if (isset($setwarnings['message']))
		{
			$LMS->SetTS('users');
			$LMS->DB->Execute('UPDATE users SET message=? WHERE id=?', array($setwarnings['message'],$LMS->GetNodeOwner($value)));
		}
	}
	$SESSION->save('warnmessage', $setwarnings['message']);
	$SESSION->save('warnon', $setwarnings['warnon']);
	$SESSION->save('warnoff', $setwarnings['warnoff']);
	
	header('Location: ?'.$SESSION->get('backto'));
	die;
}

if($backid = $_GET['ownerid'])
{
	if($LMS->UserExists($backid))
	{
		$LMS->NodeSetWarnU($backid, $_GET['warning']);
		header('Location: ?'.$SESSION->get('backto').'#'.$backid);
		die;
	}
}

if($backid = $_GET['id'])
{
	if($LMS->NodeExists($backid))
	{
		$LMS->NodeSwitchWarn($backid);
		header('Location: ?'.$SESSION->get('backto').'#'.$backid);
		die;
	}
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = trans('Messages');

$nodelist = $LMS->GetNodeList();
unset($nodelist['total']);
unset($nodelist['order']);
unset($nodelist['direction']);
unset($nodelist['totalon']);
unset($nodelist['totaloff']);

$SMARTY->assign('warnmessage', $SESSION->get('warnmessage'));
$SMARTY->assign('warnon', $SESSION->('warnon'));
$SMARTY->assign('warnoff', $SESSION->('warnoff'));
$SMARTY->assign('nodelist',$nodelist);
$SMARTY->display('nodewarnings.html');

?>
