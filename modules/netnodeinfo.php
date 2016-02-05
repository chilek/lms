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

$id = intval($_GET['id']);
$result = $DB->GetRow('SELECT n.*, p.name AS projectname,
	lb.name AS borough_name, lb.type AS borough_type,
	ld.name AS district_name, ls.name AS state_name,
        (SELECT d.shortname FROM divisions d WHERE d.id = n.divisionid) AS division
	FROM netnodes n
	LEFT JOIN invprojects p ON n.invprojectid = p.id
	LEFT JOIN location_cities lc ON lc.id = n.location_city
	LEFT JOIN location_boroughs lb ON lb.id = lc.boroughid
	LEFT JOIN location_districts ld ON ld.id = lb.districtid
	LEFT JOIN location_states ls ON ls.id = ld.stateid
	WHERE n.id=? ',array($id));
if (!$result)
	$SESSION->redirect('?m=netnodelist');


//$netdevinfo = $LMS->GetNetDev($_GET['id']);


$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = trans('Net Device Node Info: $a', $info['name']);

$SMARTY->assign('nodeinfo', $result);
$SMARTY->assign('objectid', $result['id']);

$nlist = $DB->GetAll("SELECT * FROM netdevices WHERE netnodeid=".$id." ORDER BY NAME");
$SMARTY->assign('netdevlist', $nlist);



$SMARTY->display('netnode/netnodeinfo.html');

?>
