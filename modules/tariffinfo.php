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

function buildGroups(&$result) {
	$groups = array();

	foreach ($result as $groupName=>$content) {
		if (count($content) == 1)
			$groups[$groupName] = $content;
		else {
			foreach ($content as $price=>$groupContent)
				$groups[$groupName." ($price)"][$price] = $groupContent;
		}
	}

	return $groups;
}

function findAlreadyExists(&$prefixList) {
	$DB = LMSDB::getInstance();
	$prefixHelperArray = $DB->GetAllByKey("SELECT id, prefix FROM voip_prefixes", "prefix");

	$exists = array();
	foreach ($prefixList as $v=>$k)
		if (isset($prefixHelperArray[$v]))
			$exists[] = $v;

	return $exists	? $exists : NULL;
}

if(isset($_GET['single']))
{
	$layout['pagetitle'] = trans('Voip prefix management');
	$prefix = $_POST['prefix'];

	// check prefix is a number
	if (!is_numeric($prefix['prefix']))
		$error['prefix'] = trans('Prefix number must be integer!');
	
	// check group is selected
	if ($_POST['group_id'] != 'none') {
		$group_id = (int) $_POST['group_id'];	
	} else {
		$error['group_select'] = trans('Prefix must belong to a group!');
	}
	
	// check prefix already exists
	$exists = $DB->GetAll('SELECT id FROM voip_prefixes WHERE prefix ?LIKE? ?', array($prefix['prefix']));
	if ($exists)
		$error['prefix'] = trans('Prefix already exists!');

	// if no errors add prefix
	if (!$error) {
		$DB->Execute('INSERT INTO voip_prefixes (prefix, groupid) VALUES (?, ?)', array($prefix['prefix'], $group_id));
	} else {
		$SMARTY->assign('error', $error);
		$SMARTY->assign('args', $prefix);
	}
}
else if(isset($_GET['file']))
{
	$TARIFF_ID = $_GET['id'];

	$result = array();
	$warnings = array();

	if (!empty($_FILES['file']['tmp_name'])) {
		$prefixList = array();

		// CREATE MAIN ARRAY
		$lines = file($_FILES['file']['tmp_name']);

		if (!empty($lines)) {
			$colnames = explode('|', strtolower(trim(reset($lines))));

			while (($line = next($lines)) !== false) {
				if (empty($line))
					continue;
				$row = array_map('trim', array_combine($colnames, explode('|', trim($line))));
				$name = $row['name'];
				$prefixes = preg_split('/\s*,\s*/', $row['prefixes']);
				$purchase_price = str_replace(',', '.', $row['purchase']);
				$sell_price = str_replace(',', '.', $row['sell']);

				$firstprefix = reset($prefixes);
				$result[$name][$sell_price][] = $firstprefix;

				// CHECK FOR DUPLICATE PREFIXES
				if (isset($prefixList[$firstprefix])) {
					$warnings['duplicate_item'][$firstprefix]['prefix'] = $firstprefix;
					$warnings['duplicate_item'][$firstprefix]['group'][$name] = $name;
					$warnings['duplicate_item'][$firstprefix]['group'][$prefixList[$firstprefix]] = $prefixList[$firstprefix];
				} else
					$prefixList[$firstprefix] = $name;

				while (($prefix = next($prefixes)) !== false) {
					$prefix = substr($firstprefix, 0, -strlen($prefix)) . $prefix;
					$result[$name][$sell_price][] = $prefix;

					// CHECK FOR DUPLICATE PREFIXES
					if (isset($prefixList[$prefix])) {
						$warnings['duplicate_item'][$prefix]['prefix'] = $prefix;
						$warnings['duplicate_item'][$prefix]['group'][$name] = $name;
						$warnings['duplicate_item'][$prefix]['group'][$prefixList[$firstprefix]] = $prefixList[$firstprefix];
					} else
						$prefixList[$prefix] = $name;
				}
			}
		}

		// BUILD GROUPS FROM PREFIX ARRAY
			$groups = buildGroups($result);

		// FIND ALREADY EXISTS PREFIXES
			if ($tmp = findAlreadyExists($prefixList))
				$warnings['already_exists'] = $tmp;
			unset($tmp);

		if ($warnings)
			$SMARTY->assign('warnings', $warnings);
		else {
			$DB->BeginTrans();

			// GENERATE INSERT QUERY TO `voip_prefix_groups` TABLE
				$voip_prefix_group = 'INSERT INTO voip_prefix_groups (name, description) VALUES ';
				foreach ($groups as $groupName=>$prefixArray)
					$voip_prefix_group .= "('$groupName', ''),";

				$voip_prefix_group = rtrim($voip_prefix_group, ',') . ';';
				$DB->execute($voip_prefix_group);

			// GENERATE INSERT QUERY TO `voip_prefixes` TABLE
				$groupHelperArray = $DB->GetAllByKey("SELECT id, name FROM voip_prefix_groups", "name");
				$voip_prefix = 'INSERT INTO voip_prefixes (prefix, groupid) VALUES ';
				foreach ($groups as $groupName=>$v)
					foreach ($v as $prefixArray)
						foreach ($prefixArray as $singlePrefix) {
							$voip_prefix .= "('$singlePrefix', " . $groupHelperArray[$groupName]['id'] . "),";
						}

				$voip_prefix = rtrim($voip_prefix, ',') . ';';
				$DB->execute($voip_prefix);

			// CREATE TARIFFS
				$voip_tariff = 'INSERT INTO voip_tariffs (groupid, tariffid, price, unitsize) VALUES ';

				foreach ($groups as $groupName=>$prefixArray) {
					$price = key($prefixArray);
					$voip_tariff .= '(' . $groupHelperArray[$groupName]['id'] . ", $TARIFF_ID, $price, 60),";
				}

				$voip_tariff = rtrim($voip_tariff, ',') . ';';
				$DB->execute($voip_tariff);
				$DB->CommitTrans();
		}
	}
}

$netid = isset($_GET['netid']) ? intval($_GET['netid']) : NULL;

if(!$LMS->TariffExists($_GET['id']) || ($netid != 0 && !$LMS->NetworkExists($netid))) {
	$SESSION->redirect('?m=tarifflist');
}

$tariff = $LMS->GetTariff($_GET['id'], $netid);

$tariff['promotions'] = $DB->GetAll('SELECT DISTINCT p.name, p.id
    FROM promotionassignments a
    JOIN promotionschemas s ON (s.id = a.promotionschemaid)
    JOIN promotions p ON (p.id = s.promotionid)
    WHERE a.tariffid = ? OR s.ctariffid = ?
    ORDER BY p.name', array($tariff['id'], $tariff['id']));

if (!empty($tariff['numberplanid']))
	$tariff['numberplan'] = $DB->GetRow('SELECT template, period FROM numberplans WHERE id = ?', array($tariff['numberplanid']));

$layout['pagetitle'] = trans('Subscription Info: $a',$tariff['name']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

// if selected tariff is phone tariff then load prefixes assigned to this tariff
if ($tariff['type'] == TARIFF_PHONE) {
	$tariff_rows = $DB->GetAll('SELECT DISTINCT
									p.prefix as prefix_number, g.id as group_id, g.name as group_name, price
								FROM
									voip_tariffs t
									LEFT JOIN voip_prefix_groups g ON g.id  = t.groupid
									LEFT JOIN voip_prefixes p ON p.groupid = t.groupid
								WHERE
									t.tariffid = ?
								ORDER BY g.name', array($tariff['id']));

	$prefixList = array();
	foreach ($tariff_rows as $row) {
		$id = $row['group_id'];

		$prefixList[$id]['group_id'] = $id;
		$prefixList[$id]['price'] = $row['price'];
		$prefixList[$id]['group_name'] = $row['group_name'];
		$prefixList[$id]['prefix_list'][] = $row['prefix_number'];
	}

	$SMARTY->assign('tariffGroups', $prefixList);
}

$SMARTY->assign('netid', $netid);
$SMARTY->assign('tariff',$tariff);
$SMARTY->assign('tariffs',$LMS->GetTariffs());
$SMARTY->assign('networks',$LMS->GetNetworks());
$SMARTY->display('tariff/tariffinfo.html');

?>
