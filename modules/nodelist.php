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

$layout['pagetitle'] = trans('Nodes List');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if(!isset($_GET['o']))
	$SESSION->restore('nlo', $o);
else
	$o = $_GET['o'];
$SESSION->save('nlo', $o);

if(!isset($_GET['s']))
	$SESSION->restore('nls', $s);
else
	$s = $_GET['s'];
$SESSION->save('nls', $s);

if(!isset($_GET['n']))
	$SESSION->restore('nln', $n);
else
	$n = $_GET['n'];
$SESSION->save('nln', $n);

if(!isset($_GET['g']))
	$SESSION->restore('nlg', $g);
else
	$g = $_GET['g'];
$SESSION->save('nlg', $g);

if(!isset($_GET['ng']))
	$SESSION->restore('nlng', $ng);
else
	$ng = $_GET['ng'];
$SESSION->save('nlng', $ng);

if(!isset($_GET['p']))
	$SESSION->restore('nlfp', $p);
else
	$p = $_GET['p'];
$SESSION->save('nlfp', $p);

$total = intval($LMS->GetNodeList($o, array('project' => $p), NULL, $n, $s, $g, $ng, null, null, true));

$page = (!isset($_GET['page']) ? 1 : intval($_GET['page']));
$per_page = intval(ConfigHelper::getConfig('phpui.nodelist_pagelimit', $total));
$offset = ($page - 1) * $per_page;

$nodelist = $LMS->GetNodeList($o, array('project' => $p), NULL, $n, $s, $g, $ng, $per_page, $offset);
$pagination = LMSPaginationFactory::getPagination($page, $total, $per_page, ConfigHelper::checkConfig('phpui.short_pagescroller'));

$listdata['total'] = $nodelist['total'];
$listdata['order'] = $nodelist['order'];
$listdata['direction'] = $nodelist['direction'];
$listdata['totalon'] = $nodelist['totalon'];
$listdata['totaloff'] = $nodelist['totaloff'];
$listdata['network'] = $n;
$listdata['customergroup'] = $g;
$listdata['nodegroup'] = $ng;
$listdata['invprojectid'] = $p;
$listdata['state'] = $s;

unset($nodelist['total']);
unset($nodelist['order']);
unset($nodelist['direction']);
unset($nodelist['totalon']);
unset($nodelist['totaloff']);

if ($SESSION->is_set('nlp') && !isset($_GET['page']))
	$SESSION->restore('nlp', $_GET['page']);

$SESSION->save('nlp', $page);

$SMARTY->assign('nodelist',$nodelist);
$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('pagination',$pagination);
$SMARTY->assign('networks',$LMS->GetNetworks());
$SMARTY->assign('nodegroups', $LMS->GetNodeGroupNames());
$SMARTY->assign('customergroups', $LMS->CustomergroupGetAll());
$SMARTY->assign('NNprojects', $DB->GetAll("SELECT * FROM invprojects
	WHERE type<>? ORDER BY name", array(INV_PROJECT_SYSTEM)));

$SMARTY->display('node/nodelist.html');

?>
