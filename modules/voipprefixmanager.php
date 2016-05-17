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

	foreach ($result as $groupName=>$group)
		if (count($group) == 1)
			$groups[$groupName] = array_values($group[key($group)]);
		else {
			foreach ($group as $k=>$singleGroup)
				foreach($singleGroup as $k2=>$v)
					$groups[$groupName . " ($k)"][] = $v;
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
		if (!$error)
			$DB->Execute('INSERT INTO voip_prefix (prefix, description) VALUES (?, ?)', array($prefix['prefix'], $prefix['description']));
		else {
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
					//echo "$prefix\n";

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

			// GENERATE INSERT QUERY TO `voip_prefix` TABLE
				$voip_prefix = 'INSERT INTO voip_prefix (prefix, name, description) VALUES ';
				foreach ($groups as $groupName=>$prefixArray)
					foreach ($prefixArray as $singlePrefix)
						$voip_prefix .= "('$singlePrefix', '$groupName', ''),";
				$voip_prefix = rtrim($voip_prefix, ',') . ';';

			// GENERATE INSERT QUERY TO `voip_prefix_group` TABLE
				$voip_prefix_group = 'INSERT INTO voip_prefix_group (name, description) VALUES ';
				foreach ($groups as $groupName=>$prefixArray)
					$voip_prefix_group .= "('$groupName', ''),";
				$voip_prefix_group = rtrim($voip_prefix_group, ',') . ';';

			// GENERATE INSERT QUERY TO `voip_prefix_group_assignment` TABLE
				$voip_prefix_group_assignment = 'INSERT INTO voip_prefix_group_assignments (prefixid, groupid) VALUES ';
				$prefixHelperArray = $DB->GetAllByKey("SELECT id, prefix FROM voip_prefix", "prefix");
				$groupHelperArray = $DB->GetAllByKey("SELECT id, name FROM voip_prefix_group", "name");

				foreach ($groups as $groupName=>$prefixArray) {
					$groupID = $groupHelperArray[$groupName]['id'];

					foreach ($prefixArray as $singlePrefix) {
						$prefixID = $prefixHelperArray[$singlePrefix]['id'];
						$voip_prefix_group_assignment .= "($prefixID, $groupID),";
					}
				}
				$voip_prefix_group_assignment = rtrim($voip_prefix_group_assignment, ',') . ';';
		}
	}
}
else
{
	$layout['pagetitle'] = trans('Voip prefix management');
}

$SMARTY->display('voipaccount/voipprefixmanager.html');

?>
