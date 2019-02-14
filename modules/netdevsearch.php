<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

function NetDevSearch($order='name,asc', $search=NULL, $sqlskey='AND') {
	global $LMS;

	$DB = LMSDB::getInstance();

	list($order,$direction) = sscanf($order, '%[^,],%s');

	($direction=='desc') ? $direction = 'desc' : $direction = 'asc';

        switch($order)
        {
		case 'id':
		        $sqlord = ' ORDER BY d.id';
		break;
		case 'producer':
		        $sqlord = ' ORDER BY producer';
		break;
		case 'model':
		        $sqlord = ' ORDER BY model';
		break;
		case 'ports':
		        $sqlord = ' ORDER BY ports';
		break;
		case 'takenports':
		        $sqlord = ' ORDER BY takenports';
		break;
		case 'serialnumber':
		        $sqlord = ' ORDER BY serialnumber';
		break;
		case 'location':
		        $sqlord = ' ORDER BY a.location';
		break;
		case 'netnode':
				$sqlord = ' ORDER BY nn.name';
		break;
		default:
		        $sqlord = ' ORDER BY d.name';
		break;
	}

	if(count($search)) foreach($search as $idx => $value)
	{
		$value = trim($value);
	        if($value!='')
		{
			switch($idx)
			{
				case 'ipaddr':
					$searchargs[] = '(inet_ntoa(n.ipaddr) ?LIKE? '.$DB->Escape("%$value%")
						.' OR inet_ntoa(n.ipaddr_pub) ?LIKE? '.$DB->Escape("%$value%").')';
					$nodes = true;
				break;
				case 'mac':
					$searchargs[] = 'n.mac ?LIKE? '.$DB->Escape("%$value%");
					$nodes = true;
				break;
				case 'name':
				        $searchargs[] = '(d.name ?LIKE? '.$DB->Escape("%$value%")
						.' OR n.name ?LIKE? '.$DB->Escape("%$value%").')';
					$nodes = true;
				break;
				case 'ports':
				        $searchargs[] = "ports = ".intval($value);
				break;
				case 'location':
					$searchargs[] = "UPPER(a.$idx) ?LIKE? UPPER(".$DB->Escape("%$value%").')';
					break;
				default:
					// UPPER here is a postgresql ILIKE bug workaround
					$searchargs[] = "UPPER(d.$idx) ?LIKE? UPPER(".$DB->Escape("%$value%").')';
				break;
			}
		}
	}

	if(isset($searchargs))
                $searchargs = ' WHERE ('.implode(' '.$sqlskey.' ',$searchargs).')';

	$netdevlist = $DB->GetAll('SELECT DISTINCT d.id, d.name, a.location, d.description, d.producer,
					d.model, d.serialnumber, d.ports, p.name AS project,
					(SELECT COUNT(*) FROM vnodes WHERE netdev = d.id AND ownerid IS NOT NULL)
					+ (SELECT COUNT(*) FROM netlinks WHERE src = d.id OR dst = d.id) AS takenports,
					d.netnodeid, nn.name AS netnode,
					lb.name AS borough_name, lb.type AS borough_type, lb.ident AS borough_ident,
					ld.name AS district_name, ld.ident AS district_ident,
					ls.name AS state_name, ls.ident AS state_ident,
					a.state as location_state_name, a.state_id as location_state,
					a.zip as location_zip, a.country_id as location_country,
					a.city as location_city_name, a.city_id as location_city,
					lc.ident AS city_ident,
					a.street AS location_street_name, a.street_id as location_street,
					lst.ident AS street_ident,
					a.house as location_house, a.flat as location_flat, a.location
				FROM netdevices d
				LEFT JOIN vaddresses a ON d.address_id = a.id
				LEFT JOIN invprojects p         ON p.id = d.invprojectid
				LEFT JOIN netnodes nn            ON nn.id = d.netnodeid
				LEFT JOIN location_streets lst  ON lst.id = a.street_id
				LEFT JOIN location_cities lc    ON lc.id = a.city_id
				LEFT JOIN location_boroughs lb  ON lb.id = lc.boroughid
				LEFT JOIN location_districts ld ON ld.id = lb.districtid
				LEFT JOIN location_states ls    ON ls.id = ld.stateid'
				.(isset($nodes) ? ' LEFT JOIN vnodes n ON (netdev = d.id AND n.ownerid IS NULL)' : '')
				.(isset($searchargs) ? $searchargs : '')
				.($sqlord != '' ? $sqlord.' '.$direction : ''));

	if ($netdevlist) {
		$filecontainers = $DB->GetAllByKey('SELECT fc.netdevid, '
				. $DB->GroupConcat("CASE WHEN fc.description = '' THEN '---' ELSE fc.description END") . ' AS descriptions
			FROM filecontainers fc
			WHERE fc.netdevid IS NOT NULL
			GROUP BY fc.netdevid', 'netdevid');

		foreach ($netdevlist as &$netdev) {
			$netdev['customlinks'] = array();
			if (!$netdev['location'] && $netdev['ownerid']) {
				$netdev['location'] = $LMS->getAddressForCustomerStuff($netdev['ownerid']);
			}
			$netdev['terc'] = empty($netdev['state_ident']) ? null
				: $netdev['state_ident'] . $netdev['district_ident']
				. $netdev['borough_ident'] . $netdev['borough_type'];
			$netdev['simc'] = empty($netdev['city_ident']) ? null : $netdev['city_ident'];
			$netdev['ulic'] = empty($netdev['street_ident']) ? null : $netdev['street_ident'];
			$netdev['filecontainers'] = isset($filecontainers[$netdev['id']])
				? explode(',', $filecontainers[$netdev['id']]['descriptions'])
				: array();
		}
		unset($netdev);
	}

	$netdevlist['total'] = count($netdevlist);
	$netdevlist['order'] = $order;
	$netdevlist['direction'] = $direction;

	return $netdevlist;
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if(isset($_POST['search']))
        $netdevsearch = $_POST['search'];

if(!isset($netdevsearch))
        $SESSION->restore('netdevsearch', $netdevsearch);
else
        $SESSION->save('netdevsearch', $netdevsearch);

if(!isset($_GET['o']))
	$SESSION->restore('ndlso', $o);
else
	$o = $_GET['o'];
$SESSION->save('ndlso', $o);

if(!isset($_POST['k']))
        $SESSION->restore('ndlsk', $k);
else
        $k = $_POST['k'];
$SESSION->save('ndlsk', $k);

if(isset($_GET['search']))
{
	$layout['pagetitle'] = trans('Network Devices Search Results');

	$netdevlist = NetDevSearch($o, $netdevsearch, $k);

	$listdata['total'] = $netdevlist['total'];
	$listdata['order'] = $netdevlist['order'];
	$listdata['direction'] = $netdevlist['direction'];

	unset($netdevlist['total']);
	unset($netdevlist['order']);
	unset($netdevlist['direction']);

	if($listdata['total']==1)
                $SESSION->redirect('?m=netdevinfo&id='.$netdevlist[0]['id']);
	else
	{
		if(!isset($_GET['page']))
    			$SESSION->restore('ndlsp', $_GET['page']);
	
		$page = (! $_GET['page'] ? 1 : $_GET['page']);
		$pagelimit = ConfigHelper::getConfig('phpui.nodelist_pagelimit', $listdata['total']);
		$start = ($page - 1) * $pagelimit;

		$SESSION->save('ndlsp', $page);

		$SMARTY->assign('page', $page);
		$SMARTY->assign('pagelimit', $pagelimit);
		$SMARTY->assign('start', $start);
		$SMARTY->assign('netdevlist', $netdevlist);
		$SMARTY->assign('listdata', $listdata);

		$SMARTY->display('netdev/netdevsearchresults.html');
	}
}
else
{
	$layout['pagetitle'] = trans('Network Devices Search');

	$SESSION->remove('ndlsp');
	
	$SMARTY->assign('k',$k);
	$SMARTY->display('netdev/netdevsearch.html');
}

?>
