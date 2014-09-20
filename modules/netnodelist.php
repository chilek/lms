<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

require ('lib/netnodehelper.php');




$layout['pagetitle'] = trans('Network Device Nodes');

if(!isset($_GET['o']))
	$SESSION->restore('ndlo', $o);
else
	$o = $_GET['o'];
$SESSION->save('ndlo', $o);

list($order,$dir) = sscanf($o,'%[^,],%s');
($dir == 'desc') ? $dir='desc' : $dir='asc';
switch ($order) {
	case 'id':
		$ostr = 'ORDER BY id';
		break;
	case 'name':
		$ostr = 'ORDER BY name';
		break;
	case 'type':
		$ostr = 'ORDER BY type';
		break;
	case 'status':
		$ostr = 'ORDER BY status';
		break;
	default:
		$ostr = 'ORDER BY name';
		break;		
}
$nlist = $DB->GetAll('SELECT n.id,n.name,n.type,n.status,n.invprojectid,p.name AS project FROM netnodes n LEFT JOIN invprojects p ON (n.invprojectid = p.id) '.$ostr.' '.$dir);

$listdata['total'] = sizeof($nlist);
$listdata['order'] = $order;
$listdata['direction'] = $dir;

if(!isset($_GET['page']))
        $SESSION->restore('ndlp', $_GET['page']);
	
$page = (! $_GET['page'] ? 1 : $_GET['page']);
$pagelimit = ConfigHelper::getConfig('phpui.nodelist_pagelimit', $listdata['total']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('ndlp', $page);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('page',$page);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('start',$start);
$SMARTY->assign('nlist',$nlist);
$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('NNstatus',$NNstatus);
$SMARTY->assign('NNtype',$NNtype);

$SMARTY->display('netnodelist.html');

?>
