<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

$layout['pagetitle'] = trans('IP Networks');

$search = array(
    'count' => true,
);

$search['total'] = intval($LMS->GetNetworkList($search));

if (isset($_GET['page']))
	$search['page'] = intval($_GET['page']);
elseif ($SESSION->is_set('netlist_page'))
    $SESSION->restore('netlist_page', $search['page']);
else
	$search['page'] = 1;
$SESSION->save('netlist_page', $search['page']);

$search['limit'] = intval(ConfigHelper::getConfig('phpui.networklist_pagelimit', $search['total']));
$search['offset'] = ($search['page'] - 1) * $search['limit'];
$search['count'] = false;

if (isset($_GET['o']))
	$search['order'] = $_GET['o'];
$netlist = $LMS->GetNetworkList($search);

$pagination = LMSPaginationFactory::getPagination($search['page'], $search['total'], $search['limit'],
	ConfigHelper::checkConfig('phpui.short_pagescroller'));

$listdata['size'] = $netlist['size'];
$listdata['assigned'] = $netlist['assigned'];
$listdata['online'] = $netlist['online'];
$listdata['order'] = $netlist['order'];
$listdata['direction'] = $netlist['direction'];

unset($netlist['assigned']);
unset($netlist['size']);
unset($netlist['online']);
unset($netlist['order']);
unset($netlist['direction']);

$listdata['total'] = count($netlist);

$SMARTY->assign('pagination', $pagination);
$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('netlist',$netlist);
$SMARTY->assign('search', false);
$SMARTY->display('net/netlist.html');

?>
