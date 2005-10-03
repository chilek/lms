<?php

/*
 * LMS version 1.7-cvs
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

function GetDocumentList($order='cdate,asc', $type=NULL, $customer=NULL)
{
	global $DB;

	if(!$customer) return NULL;
	
	if($order=='')
		$order='cdate,asc';
	
	list($order,$direction) = sscanf($order, '%[^,],%s');
	($direction=='desc') ? $direction = 'desc' : $direction = 'asc';
	
	switch($order)
	{
		case 'type':
			$sqlord = ' ORDER BY type';
		break;
		case 'title':
			$sqlord = ' ORDER BY title';
		break;
		default:
			$sqlord = ' ORDER BY cdate';
		break;
	}

	$list = $DB->GetAll('SELECT docid, number, type, title, cdate, fromdate, todate, description, filename, md5sum, contenttype, template, closed
                	FROM documentcontents, documents
		        LEFT JOIN numberplans ON(numberplanid = numberplans.id)
			WHERE documents.id = documentcontents.docid
			AND customerid = ?'
			.($type ? ' AND type = '.$type : '')
			.$sqlord.' '.$direction, array($customer));

	$list['total'] = sizeof($list);
	$list['direction'] = $direction;
	$list['order'] = $order;
	
	return $list;
}

if(!isset($_GET['o']))
	$SESSION->restore('doclo', $o);
else
	$o = $_GET['o'];
$SESSION->save('doclo', $o);

if(!isset($_GET['t']))
	$SESSION->restore('doclt', $t);
else
	$t = $_GET['t'];
$SESSION->save('doclt', $t);

if(!isset($_GET['c']))
	$SESSION->restore('doclc', $c);
else
	$c = $_GET['c'];
$SESSION->save('doclc', $c);

$documentlist = GetDocumentList($o, $t, $c);
$listdata['total'] = $documentlist['total'];
$listdata['order'] = $documentlist['order'];
$listdata['direction'] = $documentlist['direction'];
$listdata['type'] = $t;
$listdata['customer'] = $c;

unset($documentlist['total']);
unset($documentlist['order']);
unset($documentlist['direction']);

if($SESSION->is_set('doclp') && !isset($_GET['page']))
	$SESSION->restore('doclp', $_GET['page']);
	    
$page = (!isset($_GET['page']) ? 1 : $_GET['page']); 
$pagelimit = (!isset($LMS->CONFIG['phpui']['documentlist_pagelimit']) ? $listdata['total'] : $LMS->CONFIG['phpui']['documentlist_pagelimit']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('doclp', $page);

$layout['pagetitle'] = trans('Documents List');
$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('customerlist', $LMS->GetCustomerNames());
$SMARTY->assign('documentlist', $documentlist);
$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('start', $start);
$SMARTY->assign('listdata', $listdata);
$SMARTY->display('documentlist.html');

?>
