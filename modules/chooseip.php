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

$layout['pagetitle'] = trans('Select IP address');

$p = $_GET['p'];

if(!isset($p))
	$js = 'var targetfield = window.opener.targetfield;';
if($p == 'main')
	$js = 'var targetfield = parent.targetfield;';

if (isset($_POST['netid']))
    $netid= $_POST['netid'];
elseif (isset($_GET['netid']))
    $netid = $_GET['netid'];
elseif (isset($_SESSION['netid']))
    $netid = $_SESSION['netid'];
else
    $netid = $networks[0]['id'];

$_SESSION['netid'] = $netid;

if($p == 'main')
	$network = $LMS->GetNetwork($netid);

$networks = $LMS->GetNetworks();

$SMARTY->assign('part',$p);
$SMARTY->assign('js',$js);
$SMARTY->assign('networks',$networks);
$SMARTY->assign('network',$network);
$SMARTY->assign('netid',$netid);
$SMARTY->display('chooseip.html');

?>
