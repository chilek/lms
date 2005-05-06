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

$layout['pagetitle'] = trans('Accounts Clear With Customer ID: $0',sprintf("%04d",$_GET['id']));
$SMARTY->assign('userid',$_GET['id']);

if (!$LMS->UserExists($_GET['id']))
{
	$body = '<H1>'.$layout['pagetitle'].'</H1><P>'.trans('Incorrect Customer ID.').'</P>';
}
else
{
	$user_id = $_GET['id'];
		
	$stan = array(
			'22.0' => $LMS->GetUserBalance($user_id, '22.0'),
			'7.0' => $LMS->GetUserBalance($user_id, '7.0'),
			'0.0' => $LMS->GetUserBalance($user_id, '0.0'),
			trans('tax-free') => $LMS->GetUserBalance($user_id, trans('tax-free'))
	);
	asort($stan);
		
	foreach($stan as $key => $val)
	{
		if(($balance = $LMS->GetUserBalance($user_id)) >= 0)
			break;
	
		if($balance > $val)
			$val = -($balance);
		else		
			$val = -$val;
	
		if ($key == trans('tax-free'))
			$ret[$key] = $LMS->DB->Execute('INSERT INTO cash (time, adminid, type, value, taxvalue, userid, comment) VALUES (?NOW?, ?, ?, ?, NULL, ?, ?)', array($LMS->AUTH->id, 3 , round($val,2) , $user_id, trans('Accounted')));
		else
			$ret[$key] = $LMS->DB->Execute('INSERT INTO cash (time, adminid, type, value, taxvalue, userid, comment) VALUES (?NOW?, ?, ?, ?, ?, ?, ?)', array($LMS->AUTH->id, 3 , round($val,2) , $key, $user_id, trans('Accounted')));
	}
	$LMS->SetTS('cash');
	
	header('Location: ?'.$SESSION->get('backto'));
}

$SMARTY->display('header.html');
$SMARTY->assign('body',$body);
$SMARTY->display('dialog.html');
$SMARTY->display('footer.html');

?>

