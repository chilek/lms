<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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


$layout['pagetitle'] = trans('Network Device Nodes');

if(!isset($_GET['o']))
	$SESSION->restore('ndlo', $o);
else
	$o = $_GET['o'];
$SESSION->save('ndlo', $o);

if(!isset($_GET['t']))
	$SESSION->restore('ndft', $t);
else
	$t = $_GET['t'];
$SESSION->save('ndft', $t);

if(!isset($_GET['s']))
	$SESSION->restore('ndfs', $s);
else
	$s = $_GET['s'];
$SESSION->save('ndfs', $s);

if(!isset($_GET['p']))
	$SESSION->restore('ndfp', $p);
else
	$p = $_GET['p'];
$SESSION->save('ndfp', $p);

if(!isset($_GET['w']))
	$SESSION->restore('ndfw', $w);
else
	$w = $_GET['w'];
$SESSION->save('ndfw', $w);

if(!isset($_GET['d']))
	$SESSION->restore('ndfd', $d);
else
	$d = $_GET['d'];
$SESSION->save('ndfd', $d);



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

$warr = array();
if (strlen(trim($t)) && $t!=-1) {
	$warr[] = "n.type=$t";
}
if (strlen(trim($s)) && $s!=-1) {
	$warr[] = "n.status=$s";
}
if (strlen(trim($p))) {
	if ($p == -2)
		$warr[] = "n.invprojectid IS NULL";
	elseif ($p != -1)
		$warr[] = "n.invprojectid=$p";
}
if (strlen(trim($w)) && $w!=-1) {
	$warr[] = "n.ownership=$w";
}

if (strlen(trim($d)) && $d!=-1) {
	$warr[] = "n.divisionid=$d";
}


$fstr = empty($warr) ? '' : ' WHERE ' . implode(' AND ', $warr);

$nlist = $DB->GetAll('SELECT n.id, n.name, n.type, n.status, n.invprojectid, p.name AS project,
		n.location, n.divisionid,
		lb.name AS borough_name, lb.type AS borough_type,
		ld.name AS district_name, ls.name AS state_name
	FROM netnodes n
	LEFT JOIN invprojects p ON (n.invprojectid = p.id) 
	LEFT JOIN location_cities lc ON lc.id = n.location_city
	LEFT JOIN location_boroughs lb ON lb.id = lc.boroughid
	LEFT JOIN location_districts ld ON ld.id = lb.districtid
	LEFT JOIN location_states ls ON ls.id = ld.stateid ' . $fstr . ' ' . $ostr . ' ' . $dir);

$listdata['total'] = sizeof($nlist);
$listdata['order'] = $order;
$listdata['direction'] = $dir;
$listdata['status'] = $s;
$listdata['type'] = $t;
$listdata['invprojectid'] = $p;
$listdata['ownership'] = $w;
$listdata['divisionid'] = $d;

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
$SMARTY->assign('divisions', $DB->GetAll('SELECT id, shortname FROM divisions ORDER BY shortname'));

$nprojects = $DB->GetAll("SELECT * FROM invprojects WHERE type<>? ORDER BY name",
	array(INV_PROJECT_SYSTEM));

$SMARTY->assign('NNprojects',$nprojects);


$SMARTY->display('netnode/netnodelist.html');

?>
