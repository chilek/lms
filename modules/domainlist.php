<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2009 LMS Developers
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

function GetDomainList($order='name,asc', $customer='', $filtr='')
{
	global $DB;

	list($order,$direction) = sscanf($order, '%[^,],%s');

	($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

	switch($order)
	{
		case 'id':
			$sqlord = " ORDER BY d.id $direction";
		break;
		case 'description':
			$sqlord = " ORDER BY d.description $direction";
		break;
		case 'customer':
			$sqlord = " ORDER BY customername $direction";
		break;
    		case 'type':
    			$sqlord = " ORDER BY type $direction";
                break;
		default:
			$sqlord = " ORDER BY d.name $direction";
		break;
                        
	}

	$list = $DB->GetAll('SELECT d.id AS id, d.name AS name, d.description, 
		d.ownerid, d.type, (SELECT COUNT(*) FROM passwd WHERE domainid = d.id) AS cnt, '
		.$DB->Concat('lastname', "' '",'c.name').' AS customername 
		FROM domains d
		LEFT JOIN customers c ON (d.ownerid = c.id) '
		. ' WHERE d.name '.($filtr==1?' REGEXP "^[0-9]"':' LIKE "'.$filtr.'%" ')
		.($customer != '' ? ' AND d.ownerid = '.intval($customer) : '')		
		.($sqlord != '' ? $sqlord : ''));
	
	$list['total'] = sizeof($list);
	$list['order'] = $order;
	$list['direction'] = $direction;
	$list['customer'] = $customer;

	return $list;
}

if(!isset($_GET['o']))
	$SESSION->restore('dlo', $o);
else
	$o = $_GET['o'];
$SESSION->save('dlo', $o);

if(!isset($_GET['c']))
        $SESSION->restore('dlc', $c);
else
	$c = $_GET['c'];
$SESSION->save('dlc', $c);

if ($SESSION->is_set('dlp') && !isset($_GET['page']))
	$SESSION->restore('dlp', $_GET['page']);

$layout['pagetitle'] = trans('Domains List');

if(!isset($_GET['f']))

$SESSION->restore('dfi', $f);

else
    $f = substr($_GET['f'],0,1);
	
$SESSION->save('dfi', $f);

$domainlist = GetDomainList($o, $c, $f);

$domainlistcount = GetDomainList($o, $c);
$domaincount="";
 if (is_array($domainlistcount))
   foreach ($domainlistcount as $line){   
    if (is_array($line))
     if (is_numeric(substr($line['name'],0,1)))
       $domaincount['numeric']+=1;     
         else
           $domaincount[substr($line['name'],0,1)]+=1;
   }
 
 
$listdata['total'] = $domainlist['total'];
$listdata['order'] = $domainlist['order'];
$listdata['direction'] = $domainlist['direction'];
$listdata['customer'] = $domainlist['customer'];

unset($domainlist['total']);
unset($domainlist['order']);
unset($domainlist['direction']);
unset($domainlist['customer']);

$page = (empty($_GET['page']) ? 1 : $_GET['page']); 
$pagelimit = (empty($CONFIG['phpui']['domainlist_pagelimit']) ? $listdata['total'] : $CONFIG['phpui']['domainlist_pagelimit']);

 if ($page > ceil($listdata['total']/$pagelimit)) $page=1;

$start = ($page - 1) * $pagelimit;

$SESSION->save('dlp', $page);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('f', $f);
$SMARTY->assign('start', $start);
$SMARTY->assign('domainlist', $domainlist);
$SMARTY->assign('domaincount',$domaincount);
$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('customerlist',$LMS->GetCustomerNames());
$SMARTY->display('domainlist.html');

?>
