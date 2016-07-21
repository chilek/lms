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

$layout['pagetitle'] = trans('Price lists');

$pricelist = (isset($_POST['pricelist'])) ? $_POST['pricelist'] : NULL;
$error = array();

if (isset($_GET['id']))
    $pricelist_id = (int) $_GET['id'];


function getGroupTableRow($name, $def_price = '', $def_unitsize = '') {

    $JSResponse = new xajaxResponse();

    if (empty($name)) {
        $pricelist = null;
        $JSResponse->call('addGroup', '');
    } else {
        $pricelist = LMSDB::getInstance()->GetRow('SELECT id, name
                                                   FROM voip_prefix_groups
                                                   WHERE name = ?', array($name));

        global $SMARTY;
        $SMARTY->assign('group', $pricelist);
        $SMARTY->assign('def_price', $def_price);
        $SMARTY->assign('def_unitsize', $def_unitsize);
        $row = $SMARTY->fetch('voipaccount/voippricelisttablerow.html');

        $JSResponse->call('addGroup', array($pricelist['id'], $row));
    }

    return $JSResponse;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction(array('getGroupTableRow'));
$SMARTY->assign('xajax', $LMS->RunXajax());

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

    return $exists ? $exists : NULL;
}

function clearPricelist($id) {
    $DB = LMSDB::getInstance();
    $DB->Execute("DELETE FROM
                     voip_prefix_groups
                  WHERE
                     id in
                     (SELECT prefix_group_id
                      FROM voip_price_groups
                      WHERE voip_tariff_id = ?)" , array($id));
}

function loadFromFile($list_id) {
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

    if ($warnings && 1==0)
        print_r($warnings);
        //$SMARTY->assign('warnings', $warnings);
    else {
        $DB = LMSDB::getInstance();
        $DB->BeginTrans();

        clearPricelist($pricelist_id);

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
        $voip_tariff = 'INSERT INTO voip_price_groups (prefix_group_id, voip_tariff_id, price, unitsize) VALUES ';

        foreach ($groups as $groupName=>$prefixArray) {
            $price = key($prefixArray);
            $voip_tariff .= '(' . $groupHelperArray[$groupName]['id'] . ", $list_id, $price, 60),";
        }

        $voip_tariff = rtrim($voip_tariff, ',') . ';';
        $DB->execute($voip_tariff);
        $DB->CommitTrans();
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    clearPricelist($pricelist_id);

    $DB->Execute('DELETE FROM voip_tariffs WHERE id = ?', array($pricelist_id));
    unset($pricelist_id);
} else if (!empty($pricelist)) {
    $pricelist_exists = $DB->GetOne("SELECT id
                                     FROM voip_tariffs
                                     WHERE
                                        name = ? AND
                                        id != ?",
                                     array($pricelist['name'], (int) $pricelist_id));

    if (!$pricelist['name'])
        $error['name'] = trans("Price list name is required!");
    else if ($pricelist_exists)
        $error['name'] = trans("Price list with specified name already exists!");

    if (empty($_FILES['file']['name']) && !$pricelist_id) {
        $error['file'] = trans('Price list must contains at least one group!');
    }

    if (!$error) {
        if ($pricelist_id == NULL) {
            $DB->Execute("INSERT INTO voip_tariffs (name, description)
                         VALUES (?, ?)", array($pricelist['name'], $pricelist['description']));
            $pricelist_id = $DB->GetOne("SELECT id FROM voip_tariffs WHERE name = ?", array($pricelist['name']));
        } else {
            $DB->Execute('UPDATE
                             voip_tariffs SET name = ?, description = ?
                          WHERE
                             id = ?', array($pricelist['name'], $pricelist['description'], $pricelist_id));
        }

        if (!empty($_FILES['file']['name'])) {
            clearPricelist($pricelist_id);
            loadFromFile($pricelist_id);
        }
    }

    unset($pricelist);
}

if (isset($_GET['id']) && $pricelist_id) {
    $tmp_rule = $DB->GetAll('SELECT
                                 t.id as tariffid, t.name, t.description,
                                 p.price, p.unitsize, g.id as groupid, g.name as groupname
                             FROM
                                 voip_tariffs t
                                 left join voip_price_groups p on t.id = p.voip_tariff_id
                                 left join voip_prefix_groups g on g.id = p.prefix_group_id
                             WHERE
                                 t.id = ?', array($pricelist_id));
    $pricelist = array();
    foreach ($tmp_rule as $v) {
        $gid = $v['groupid'];

        $pricelist['group'][$gid] = array('id'    => $gid,
                                          'name'  => $v['groupname'],
                                          'price' => $v['price'],
                                          'units' => $v['unitsize']);
    }

    $pricelist['id'] = $tmp_rule[0]['tariffid'];
    $pricelist['description'] = $tmp_rule[0]['description'];
    $pricelist['name'] = $tmp_rule[0]['name'];
}

$SMARTY->assign('pricelist', $pricelist);
$SMARTY->assign('price_list', $DB->GetAll('SELECT id, name FROM voip_tariffs'));
$SMARTY->assign('error', $error);
$SMARTY->display('voipaccount/voippricelist.html');

?>
