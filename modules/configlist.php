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

function GetConfigList($order='var,asc')
{
	global $LMS;

	list($order, $direction) = sscanf($order, '%[^,],%s');
	
	$direction = ($direction != 'desc') ? 'asc' : 'desc';

	switch($order)
	{
		case 'section':
			$sqlord = " ORDER BY section $direction, var";
		break;
		default:
			$sqlord = " ORDER BY var $direction";
		break;
	}

	$config = $LMS->DB->GetAll('SELECT id, section, var, value, description, disabled FROM uiconfig WHERE section != \'userpanel\''.$sqlord);

	$config['total'] = sizeof($config);
	$config['order'] = $order;
	$config['direction'] = $direction;

	return $config;
}

$layout['pagetitle'] = trans('User Interface Configuration');

if(!isset($_GET['o']))
	$SESSION->restore('clo', $o);
else
	$o = $_GET['o'];
$SESSION->save('clo', $o);

if ($SESSION->is_set('clp') && !isset($_GET['page']))
	$SESSION->restore('clp', $_GET['page']);

$configlist = GetConfigList($o);
$listdata['total'] = $configlist['total'];
$listdata['order'] = $configlist['order'];
$listdata['direction'] = $configlist['direction'];
unset($configlist['total']);
unset($configlist['order']);
unset($configlist['direction']);
	    
$page = (!isset($_GET['page']) ? 1 : $_GET['page']); 
$pagelimit = (! $LMS->CONFIG['phpui']['configlist_pagelimit'] ? $listdata['total'] : $LMS->CONFIG['phpui']['configlist_pagelimit']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('clp', $page);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('start', $start);
$SMARTY->assign('configlist', $configlist);
$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('layout',$layout);
$SMARTY->display('configlist.html');

?>
