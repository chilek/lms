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

function GetNumberPlanList()
{
	global $DB;
	
	$currmonth = date('n');
	switch($currmonth)
	{
		case 1: case 2: case 3: $startq = 1; break;
		case 4: case 5: case 6: $startq = 4; break;
		case 7: case 8: case 9: $startq = 7; break;
		case 10: case 11: case 12: $startq = 10; break;
	}
	
	$yearstart = mktime(0,0,0,1,1);
	$quarterstart = mktime(0,0,0,$startq,1);
	$monthstart = mktime(0,0,0,$currmonth,1);
	$weekstart = mktime(0,0,0,$currmonth,date('j')-strftime('%u')+1);
	$daystart = mktime(0,0,0);

	if($list = $DB->GetAll('SELECT id, template, period, doctype, isdefault FROM numberplans ORDER BY id'))
	{
		$count = $DB->GetAllByKey('SELECT numberplanid AS id, COUNT(numberplanid) AS count
					    FROM documents 
					    GROUP BY numberplanid','id');

		$max = $DB->GetAllByKey('SELECT numberplanid AS id, MAX(number) AS max 
					    FROM documents LEFT JOIN numberplans ON (numberplanid = numberplans.id)
					    WHERE cdate >= (CASE period
						WHEN '.YEARLY.' THEN '.$yearstart.'
						WHEN '.QUARTERLY.' THEN '.$quarterstart.'
						WHEN '.MONTHLY.' THEN '.$monthstart.'
						WHEN '.WEEKLY.' THEN '.$weekstart.'
						WHEN '.DAILY.' THEN '.$daystart.' ELSE 0 END)
					    GROUP BY numberplanid','id');

		foreach ($list as $idx => $item)
		{
			$list[$idx]['next'] = isset($max[$item['id']]['max']) ? $max[$item['id']]['max']+1 : 1;
			$list[$idx]['issued'] = isset($count[$item['id']]['count']) ? $count[$item['id']]['count'] : 0;
		}
	}
	
	return $list;
}

if ($SESSION->is_set('nplp') && !isset($_GET['page']))
	$SESSION->restore('nplp', $_GET['page']);

$page = (!isset($_GET['page']) ? 1 : $_GET['page']); 
$pagelimit = ConfigHelper::getConfig('phpui.numberplanlist_pagelimit', $listdata['total']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('nplp', $page);

$layout['pagetitle'] = trans('Numbering Plans List');

$numberplanlist = GetNumberPlanList();
$listdata['total'] = empty($numberplanlist) ? 0 : count($numberplanlist);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('start', $start);
$SMARTY->assign('numberplanlist', $numberplanlist);
$SMARTY->assign('listdata', $listdata);
$SMARTY->display('numberplan/numberplanlist.html');

?>
