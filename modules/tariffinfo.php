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
	global $DB;
	$prefixHelperArray = $DB->GetAllByKey("SELECT id, prefix FROM voip_prefix", "prefix");

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

	// check prefix already exists
		$exists = $DB->GetAll('SELECT id FROM voip_prefix WHERE prefix like ?', array($prefix['prefix']));
		if ($exists)
			$error['prefix'] = trans('Prefix already exists!');

	// if no errors add prefix
		if (!$error) {
			// add prefix
			$DB->Execute('INSERT INTO voip_prefix (prefix, description) VALUES (?, ?)', array($prefix['prefix'], $prefix['description']));

			// add prefix to group
			$DB->Execute('INSERT INTO
									voip_prefix_group_assignments (prefixid, groupid)
								VALUES
									((SELECT id FROM voip_prefix p WHERE p.prefix like ?), (SELECT id FROM voip_prefix_group WHERE id = ?))', array($prefix['prefix'], $_POST['group_id']));						
		} else {
			$SMARTY->assign('error', $error);
			$SMARTY->assign('args', $prefix);
		}
}
else if(isset($_GET['file']))
{
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
			$tmp = findAlreadyExists($prefixList);
			if ($tmp)
				$warnings['already_exists'] = $tmp;

		if ($warnings)
			$SMARTY->assign('warnings', $warnings);
		else {
			$DB->BeginTrans();
			$DB->execute('delete from voip_tariff where tariffid =4');

			// GENERATE INSERT QUERY TO `voip_prefix` TABLE
				$voip_prefix = 'INSERT INTO voip_prefix (prefix, name, description) VALUES ';
				foreach ($groups as $groupName=>$v)
					foreach ($v as $prefixArray)
						foreach ($prefixArray as $singlePrefix)
							$voip_prefix .= "('$singlePrefix', '$groupName', ''),";

				$voip_prefix = rtrim($voip_prefix, ',') . ';';
				//$DB->execute($voip_prefix);

			// GENERATE INSERT QUERY TO `voip_prefix_group` TABLE
				$voip_prefix_group = 'INSERT INTO voip_prefix_group (name, description) VALUES ';
				foreach ($groups as $groupName=>$prefixArray)
					$voip_prefix_group .= "('$groupName', ''),";

				$voip_prefix_group = rtrim($voip_prefix_group, ',') . ';';
				//$DB->execute($voip_prefix_group);

			// GENERATE INSERT QUERY TO `voip_prefix_group_assignment` TABLE
				$voip_prefix_group_assignment = 'INSERT INTO voip_prefix_group_assignments (prefixid, groupid) VALUES ';
				$prefixHelperArray = $DB->GetAllByKey("SELECT id, prefix FROM voip_prefix", "prefix");
				$groupHelperArray = $DB->GetAllByKey("SELECT id, name FROM voip_prefix_group", "name");

				foreach ($groups as $groupName=>$v) {
					$groupID = $groupHelperArray[$groupName]['id'];

					foreach ($v as $prefixArray)
						foreach ($prefixArray as $singlePrefix) {
							$prefixID = $prefixHelperArray[$singlePrefix]['id'];
							$voip_prefix_group_assignment .= "($prefixID, $groupID),";
						}
				}
				$voip_prefix_group_assignment = rtrim($voip_prefix_group_assignment, ',') . ';';
				$DB->execute($voip_prefix_group_assignment);

			// CREATE TARIFFS
				$TARIFF_ID = $_GET['id'];
				$groups_id = $DB->GetAllByKey('SELECT name, id FROM voip_prefix_group', 'name');

				$voip_tariff = 'INSERT INTO voip_tariff (groupid, tariffid, prefixid, price, unitsize) VALUES ';

				foreach ($groups as $groupName=>$prefixArray) {
					$price = key($prefixArray);
					$voip_tariff .= '(' . $groups_id[$groupName]['id'] . ", $TARIFF_ID, NULL, $price, 60),";
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
if ($tariff['type'] == 4) {
	$tariff_rows = $DB->GetAll('SELECT DISTINCT
											 g.id as group_id,
											 g.name as group_name,
											 p.prefix as prefix_number
										FROM
											(voip_tariff t LEFT JOIN voip_prefix_group g ON t.groupid = g.id)
											RIGHT JOIN voip_prefix_group_assignments assignment on g.id = assignment.groupid
											RIGHT JOIN voip_prefix p ON assignment.prefixid = p.id
										WHERE
											tariffid = ?
										ORDER BY
											g.id', array($tariff['id']));

	$prefixList = array();
	foreach ($tariff_rows as $row) {
		$id = $row['group_id'];

		$prefixList[$id]['group_id'] = $id;
		$prefixList[$id]['group_name'] = $row['group_name'];
		$prefixList[$id]['prefix_list'][] = $row['prefix_number'];
	}

	$SMARTY->assign('tariffGroups', $prefixList);
}

$SMARTY->assign('netid', $netid);
$SMARTY->assign('tariff', $tariff);
$SMARTY->assign('tariffs', $LMS->GetTariffs());
$SMARTY->assign('networks', $LMS->GetNetworks());
$SMARTY->display('tariff/tariffinfo.html');

?>
