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

$typelist = $DOCTYPES;

foreach($typelist as $idx => $name)
	if($idx >= 0)
		unset($typelist[$idx]);

if ($SESSION->is_set('dtlp') && !isset($_GET['page']))
	$SESSION->restore('dtlp', $_GET['page']);

$listdata['total'] = count($typelist);

$page = (!isset($_GET['page']) ? 1 : $_GET['page']); 
$pagelimit = ConfigHelper::getConfig('phpui.documenttypes_pagelimit', $listdata['total']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('dtlp', $page);

$layout['pagetitle'] = trans('Document Types List');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('start', $start);
$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('typelist', $typelist);
$SMARTY->display('document/documenttypes.html');

?>
