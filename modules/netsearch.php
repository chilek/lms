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

if (isset($_GET['ajax'])) {
	header('Content-type: text/plain');
	$search = urldecode(trim($_GET['what']));

	switch ($_GET['mode']) {
		case 'netname':
			$mode = 'name';
			break;

		case 'interface':
			$mode = 'interface';
			break;

		case 'gateway':
			$mode = 'gateway';
			break;
			
		case 'dns':
			$mode = 'dns';
			break;	
			
		case 'wins':
			$mode = 'wins';
			break;	
			
		case 'domain':
			$mode = 'domain';
			break;		
			
		case 'host':
			$mode = 'host';
			break;		
			
		case 'description':
			$mode = 'notes';
			break;			
			
		default:
			print 'false;';
			exit;
	}

	$candidates = $DB->GetAll('SELECT
											' . $mode . ' as item,
											count(id) AS entries
										FROM
											networks
										WHERE
											' . $mode . ' != \'\' AND lower(' . $mode . ') ?LIKE? lower(' . $DB->Escape('%' . $search . '%') . ')
										GROUP BY
											item
										ORDER BY
											entries DESC, item ASC
										LIMIT 15');
		
	if ($mode == 'dns') {		
		$candidates2 = $DB->GetAll('SELECT
												dns2 as item,
												count(id) AS entries
											FROM
												networks
											WHERE
												dns2 != \'\' AND lower(dns2) ?LIKE? lower(' . $DB->Escape('%' . $search . '%') . ')
											GROUP BY
												item
											ORDER BY
												entries DESC, item ASC
											LIMIT 15');

		$candidates = array_merge($candidates, $candidates2);							
	}
	elseif ($mode == 'host') {
		$candidates = $DB->GetAll('SELECT
												h.name as item,
												count(*) AS entries
											FROM
												networks n left join hosts h on n.hostid = h.id
											WHERE
												h.name != \'\' AND lower(h.name) ?LIKE? lower(' . $DB->Escape('%' . $search . '%') . ')
											GROUP BY
												item
											ORDER BY
												entries DESC, item ASC
											LIMIT 15');
	}
										
	$eglible = $descriptions = array();

	if ($candidates)
		foreach ($candidates as $idx => $row) {
			$eglible[$row['item']] = escape_js($row['item']);
			$descriptions[$row['item']] = escape_js($row['entries'] . ' ' . trans('entries'));
		}
	if ($eglible) {
		print "this.eligible = [\"" . implode('","', $eglible) . "\"];\n";
		print "this.descriptions = [\"" . implode('","', $descriptions) . "\"];\n";
	} else
		print "false;\n";
	exit;
}

if (isset($_GET['search'])) {
	$layout['pagetitle'] = trans('IP Network Search Results');

	$netlist = $LMS->GetNetworkList($_POST['search']);

	$listdata['total'] = $netlist['total'];
	$listdata['order'] = $netlist['order'];
	$listdata['direction'] = $netlist['direction'];

	unset($netlist['order'], $netlist['direction'], $netlist['online'], $netlist['assigned'], $netlist['size']);

	if ($listdata['total'] == 1)
		$SESSION->redirect('?m=netsearch&id='.$netdevlist[0]['id']);
	else {
		if(!isset($_GET['page']))
    		$SESSION->restore('ndlsp', $_GET['page']);

		$page = (! $_GET['page'] ? 1 : $_GET['page']);
		$pagelimit = ConfigHelper::getConfig('phpui.nodelist_pagelimit', $listdata['total']);
		$start = ($page - 1) * $pagelimit;

		$SESSION->save('ndlsp', $page);

		$SMARTY->assign('page', $page);
		$SMARTY->assign('pagelimit', $pagelimit);
		$SMARTY->assign('start', $start);
		$SMARTY->assign('netlist', $netlist);
		$SMARTY->assign('listdata', $listdata);
		$SMARTY->display('net/netlist.html');
	}
} else {
	$layout['pagetitle'] = trans('IP Network Search');

	$SESSION->remove('ndlsp');
	$SMARTY->assign('autosuggest_placement', ConfigHelper::getConfig('phpui.default_autosuggest_placement'));
	$SMARTY->assign('k',$k);
	$SMARTY->display('net/netsearch.html');
}

?>
