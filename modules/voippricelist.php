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

/*!
 * \brief Build groups based on array forwarded as parameter.
 * 
 * WARNING !
 * You will not see effect when group have one price within himself.
 *
 * \param  array &$result handle to array
 * \return array
 */
function buildGroups(&$result) {
    $groups = array();

    foreach ($result as $groupName=>$content) {
        if (count($content) == 1)
            $groups[$groupName] = $content;
        else {
            foreach ($content as $price => $groupContent)
                $groups[$groupName." ($price)"][$price] = $groupContent;
        }
    }

    return $groups;
}


/*!
 * \brief Delete pricelist prefix groups from database by pricelist id.
 *
 * \param int $id pricelist id to clear
 */
function clearPricelist($id) {
    $DB = LMSDB::getInstance();
    $DB->Execute("DELETE FROM
                     voip_prefix_groups
                  WHERE
                     id in (SELECT prefix_group_id
                            FROM voip_price_groups
                            WHERE voip_tariff_id = ?)"
                  ,array($id));
}

/*!
 * \brief Change text to asociative array.
 *
 * \param string $row single row to parse
 * \return array associative array with paremeters
 */
function parseRow($row) {
    $pattern = '(?<prefix>.*)\|' .
               '(?<name>.*)\|' .
               '(?<unitsize>.*)\|' .
               '(?<purchase>.*)\|' .
               '(?<sell>.*)';

    $row = str_replace("\r", '', $row);
    preg_match('/^'.$pattern.'$/', $row, $matches);

    foreach ($matches as $k=>$v) {
        if (is_numeric($k))
            unset($matches[$k]);
    }

    return $matches;
}

function loadFromFile($list_id) {
    $prefixList = array();
    $error      = array();
    $lines      = file($_FILES['file']['tmp_name']);

    if (empty($lines)) {
        return 1;
    }

    while (($line = next($lines)) !== false) {
        if (empty($line))
            continue;

        $row    = parseRow($line);
        $name   = $row['name'];
        $prefix = $row['prefix'];
        $sell   = $row['sell'];

        $result[$name][$sell][] = $prefix;

        // CHECK FOR DUPLICATE PREFIXES
        if (isset($prefixList[$prefix]))
            $error['duplicate_item'][] = $prefix;
        else
            $prefixList[$prefix] = $name;
    }

    if ($error) {
        return $error;
    }

    $DB = LMSDB::getInstance();
    $groups = buildGroups($result);

    // -----------------------------------------------------
    // GENERATE INSERT QUERY TO `voip_prefix_groups` TABLE
    // -----------------------------------------------------
    $voip_prefix_group = 'INSERT INTO voip_prefix_groups (name, description) VALUES ';
    foreach ($groups as $groupName=>$prefixArray)
        $voip_prefix_group .= "('$groupName', ''),";

    $voip_prefix_group = rtrim($voip_prefix_group, ',') . ';';
    $DB->execute($voip_prefix_group);

    // -----------------------------------------------------
    // GENERATE INSERT QUERY TO `voip_prefix_groups` TABLE
    // -----------------------------------------------------
    $groupHelperArray = $DB->GetAllByKey("SELECT id, name FROM voip_prefix_groups", "name");
    $voip_prefix = 'INSERT INTO voip_prefixes (prefix, groupid) VALUES ';

    foreach ($groups as $groupName=>$v)
        foreach ($v as $prefixArray)
            foreach ($prefixArray as $singlePrefix) {
                $voip_prefix .= "('$singlePrefix', " . $groupHelperArray[$groupName]['id'] . "),";
            }

    $voip_prefix = rtrim($voip_prefix, ',') . ';';
    $DB->execute($voip_prefix);

    // -----------------------------------------------------
    // CREATE TARIFFS
    // -----------------------------------------------------
    $voip_tariff = 'INSERT INTO voip_price_groups (prefix_group_id, voip_tariff_id, price, unitsize) VALUES ';

    foreach ($groups as $groupName=>$prefixArray) {
        $price = key($prefixArray);
        $voip_tariff .= '(' . $groupHelperArray[$groupName]['id'] . ", $list_id, $price, 60),";
    }

    $voip_tariff = rtrim($voip_tariff, ',') . ';';
    $DB->execute($voip_tariff);

    return 0;
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
        $DB->BeginTrans();

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

        $file_err = 0;
        if (!empty($_FILES['file']['name'])) {
            clearPricelist($pricelist_id);
            $file_err = loadFromFile($pricelist_id);
        }

        if (!$error)
            $DB->CommitTrans();
        else {
            $SMARTY->assign('file_err', $file_err);
            $DB->RollbackTrans();
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
