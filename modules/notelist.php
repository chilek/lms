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

function GetNotesList($search=NULL, $cat=NULL, $group=NULL, $hideclosed=NULL, $order, $pagelimit=100, $page=NULL)
{
	global $DB;
	
	if($order=='')
		$order='id,asc';
	
	list($order,$direction) = sscanf($order, '%[^,],%s');
	($direction=='desc') ? $direction = 'desc' : $direction = 'asc';
	
	switch($order)
	{
		case 'id':
			$sqlord = ' ORDER BY d.id';
		break;
		case 'cdate':
			$sqlord = ' ORDER BY d.cdate';
		break;
		case 'number':
			$sqlord = ' ORDER BY number';
		break;
		case 'value':
			$sqlord = ' ORDER BY value';
		break;
		case 'count':
			$sqlord = ' ORDER BY count';
		break;
		case 'name':
			$sqlord = ' ORDER BY name';
		break;
	}
	
	$where = '';
	
	if($search!='' && $cat)
        {
	        switch($cat)
		{
			case 'number':
				$where = ' AND number = '.intval($search);
			break;
			case 'cdate':
				$where = ' AND cdate >= '.intval($search).' AND cdate < '.(intval($search)+86400);
			break;
			case 'month':
				$last = mktime(23,59,59, date('n',$search) + 1, 0, date('Y', $search));
				$where = ' AND cdate >= '.intval($search).' AND cdate <= '.$last;
			break;
			case 'ten':
			        $where = ' AND ten = '.$DB->Escape($search);
			break;
			case 'customerid':
				$where = ' AND d.customerid = '.intval($search);
			break;
			case 'name':
				$where = ' AND UPPER(d.name) ?LIKE? UPPER('.$DB->Escape('%'.$search.'%').')';
			break;
			case 'address':
				$where = ' AND UPPER(address) ?LIKE? UPPER('.$DB->Escape('%'.$search.'%').')';
			break;
			case 'value':
				$having = ' HAVING SUM(n.value) = '.str_replace(',','.',f_round($search)).' ';
			break;
		}
	}

	$result['page'] = null;
        
	if($hideclosed)
		$where .= ' AND closed = 0';

	if($res = $DB->Exec('SELECT d.id AS id, number, cdate, template, closed, 
			d.customerid, d.name, address, zip, city, c.name AS country,
			SUM(n.value) AS value, COUNT(n.docid) AS count
			FROM documents d
			JOIN debitnotecontents n ON (n.docid = d.id)
			LEFT JOIN countries c ON (c.id = d.countryid)
			LEFT JOIN numberplans ON (d.numberplanid = numberplans.id)
			LEFT JOIN (
				SELECT DISTINCT a.customerid FROM customerassignments a
				JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
				WHERE e.userid = lms_current_user()
				) e ON (e.customerid = d.customerid) 
			WHERE e.customerid IS NULL AND type = '.DOC_DNOTE
			.$where
			.(!empty($group['group']) ?
			            ' AND '.(!empty($group['exclude']) ? 'NOT' : '').' EXISTS (
			            SELECT 1 FROM customerassignments WHERE customergroupid = '.intval($group['group']).'
			            AND customerid = d.customerid)' : '')
			.' GROUP BY d.id, number, cdate, d.customerid, 
			d.name, address, zip, city, template, closed, c.name '
			.(isset($having) ? $having : '')
			.$sqlord.' '.$direction))
	{
		if ($page > 0) {
	                $start =  ($page - 1) * $pagelimit;
		        $stop = $start + $pagelimit;
		}
		$id = 0;

		while($row = $DB->FetchRow($res))
		{
			$result[$id] = $row;
			// free memory for rows which will not be displayed
	                if($page > 0)
			{
			        if(($id < $start || $id > $stop) && isset($result[$id]))
			                $result[$id] = NULL;
			}
			elseif(isset($result[$id-$pagelimit]))
		                $result[$id-$pagelimit] = NULL;

			$id++;
		}

		$result['page'] = $page > 0 ? $page : ceil($id / $pagelimit);
	}

	$result['order'] = $order;
	$result['direction'] = $direction;
			
	return $result;
}

$layout['pagetitle'] = trans('Debit Notes List<!long>');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SESSION->restore('dnlm', $marks);
if(isset($_POST['marks']))
	foreach($_POST['marks'] as $id => $mark)
		$marks[$id] = $mark;
$SESSION->save('dnlm', $marks);

if(isset($_POST['search']))
	$s = $_POST['search'];
else
	$SESSION->restore('dnls', $s);
$SESSION->save('dnls', $s);

if(isset($_GET['o']))
	$o = $_GET['o'];
else
	$SESSION->restore('dnlo', $o);
$SESSION->save('dnlo', $o);

if(isset($_POST['cat']))
	$c = $_POST['cat'];
else
	$SESSION->restore('dnlc', $c);
$SESSION->save('dnlc', $c);

if (isset($_POST['search']))
	$h = isset($_POST['hideclosed']);
elseif (($h = $SESSION->get('dnlh')) === NULL)
	$h = ConfigHelper::checkConfig('notes.hide_closed');
$SESSION->save('dnlh', $h);

if(isset($_POST['group'])) {
	$g = $_POST['group'];
	$ge = isset($_POST['groupexclude']) ? $_POST['groupexclude'] : NULL;
} else {
	$SESSION->restore('dnlg', $g);
	$SESSION->restore('dnlge', $ge);
}
$SESSION->save('dnlg', $g);
$SESSION->save('dnlge', $ge);

if($c == 'cdate' && $s && preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $s))
{
	list($year, $month, $day) = explode('/', $s);
	$s = mktime(0,0,0, $month, $day, $year);
}
elseif($c == 'month' && $s && preg_match('/^[0-9]{4}\/[0-9]{2}$/', $s))
{
	list($year, $month) = explode('/', $s);
        $s = mktime(0,0,0, $month, 1, $year);
}

$pagelimit = ConfigHelper::getConfig('phpui.debitnotelist_pagelimit');
$page = !isset($_GET['page']) ? 0 : intval($_GET['page']);

$notelist = GetNotesList($s, $c, array('group' => $g, 'exclude'=> $ge), $h, $o, $pagelimit, $page);

$SESSION->restore('dnlc', $listdata['cat']);
$SESSION->restore('dnls', $listdata['search']);
$SESSION->restore('dnlg', $listdata['group']);
$SESSION->restore('dnlge', $listdata['groupexclude']);
$SESSION->restore('dnlh', $listdata['hideclosed']);

$listdata['order'] = $notelist['order'];
$listdata['direction'] = $notelist['direction'];
$page = $notelist['page'];

unset($notelist['page']);
unset($notelist['order']);
unset($notelist['direction']);

$listdata['total'] = sizeof($notelist);

if($note = $SESSION->get('noteprint'))
{
        $SMARTY->assign('note', $note);
        $SESSION->remove('noteprint');
}

$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('start', ($page - 1) * $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('marks', $marks);
$SMARTY->assign('grouplist', $LMS->CustomergroupGetAll());
$SMARTY->assign('notelist', $notelist);
$SMARTY->display('note/notelist.html');

?>
