<?php

/*
 * LMS version 1.6-cvs
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

$layout['pagetitle'] = trans('Select IP address');

$networks = $LMS->GetNetworks();

$p = $_GET['p'];

if(!isset($p))
	$js = 'var targetfield = window.opener.targetfield;';
if($p == 'main')
	$js = 'var targetfield = parent.targetfield;';

if (isset($_POST['netid']))
    $netid = $_POST['netid'];
elseif (isset($_GET['netid']))
    $netid = $_GET['netid'];
elseif ($SESSION->is_set('netid'))
    $SESSION->restore('netid', $netid);
else
    $netid = $networks[0]['id'];

if (isset($_POST['page']))
    $page = $_POST['page'];
elseif (isset($_GET['page']))
    $page = $_GET['page'];
elseif ($SESSION->is_set('ntlp.page.'.$netid))
    $SESSION->restore('ntlp.page.'.$netid, $page);
else
    $page = 1;

$SESSION->save('netid', $netid);
$SESSION->save('ntlp.page.'.$netid, $page);

if($p == 'main')
{
	$network = $LMS->GetNetworkRecord($netid, $page, $LMS->CONFIG['phpui']['networkhosts_pagelimit']);
	$SESSION->save('ntlp.pages.'.$netid, $network['pages']);
}

if($p == 'down' || $p == 'top')
{
	$SESSION->restore('ntlp.page.'.$netid, $network['page']);
	$SESSION->restore('ntlp.pages.'.$netid, $network['pages']);
	if (!isset($network['pages'])) 
	{
		$network = $LMS->GetNetworkRecord($netid, $page, $LMS->CONFIG['phpui']['networkhosts_pagelimit']);
		$SESSION->save('ntlp.pages.'.$netid, $network['pages']);
	}
}

$SMARTY->assign('part',$p);
$SMARTY->assign('js',$js);
$SMARTY->assign('networks',$networks);
$SMARTY->assign('network',$network);
$SMARTY->assign('netid',$netid);
$SMARTY->display('chooseip.html');

?>
