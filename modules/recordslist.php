<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2009 Webvisor Sp. z o.o.
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
 */


if(!isset($_GET['d']))
	$SESSION->restore('ald', $d);
else
	$d = $_GET['d'];
$SESSION->save('ald', $d);
	    
$recordslist=$DB->GetAll('SELECT *,
	(CASE WHEN type=\'TXT\' THEN 1
		WHEN type=\'MX\' THEN 2
		WHEN type=\'NS\' THEN 3
		WHEN type=\'SOA\' THEN 4
		ELSE 0 END) AS ord
	FROM records WHERE domain_id='.$d.' ORDER BY ord desc');

$listdata['total'] = count($recordslist);
$listdata['domain'] = $d;


$page = (!isset($_GET['page']) ? 1 : $_GET['page']);
$pagelimit = (!isset($CONFIG['phpui']['recordslist_pagelimit']) ? $listdata['total'] : $CONFIG['phpui']['recordslist_pagelimit']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('alp', $page);

$layout['pagetitle'] = trans('Records list');



$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('start', $start);
$SMARTY->assign('recordslist',$recordslist);
$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('domainlist',$DB->GetAll('SELECT id, name FROM domains  ORDER BY name'));
$SMARTY->display('recordslist.html');

?>
