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

$layout['pagetitle'] = trans('Tariff rule list');

function getGroupTableRow($name, $def_price = '', $def_unitsize = '') {

    $JSResponse = new xajaxResponse();

    if (empty($name)) {
        $group = null;
        $JSResponse->call('addGroup', '');
    } else {
        $group = LMSDB::getInstance()->GetRow('SELECT id, name
                                               FROM voip_prefix_groups
                                               WHERE name = ?', array($name));

        global $SMARTY;
        $SMARTY->assign('group', $group);
        $SMARTY->assign('def_price', $def_price);
        $SMARTY->assign('def_unitsize', $def_unitsize);
        $row = $SMARTY->fetch('voipaccount/voiptarifftablerow.html');

        $JSResponse->call('addGroup', array($group['id'], $row));
    }

    return $JSResponse;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction(array('getGroupTableRow'));
$SMARTY->assign('xajax', $LMS->RunXajax());

if (isset($_GET['ajax'])) {
    header('Content-type: text/plain');
    $search = urldecode(trim($_GET['what']));
    $mode = trim($_GET['mode']);

    switch ($mode) {
        case 'prefix':
            $result = $DB->GetAll('SELECT prefix as item
                                   FROM voip_prefixes
                                   WHERE prefix ?LIKE? ?
                                   LIMIT 20', array('%'.$search.'%'));
        break;

        case 'group':
            $result = $DB->GetAll('SELECT id, name as item
                                   FROM voip_prefix_groups
                                   WHERE name ?LIKE? ?
                                   LIMIT 20', array('%'.$search.'%'));
        break;
    }

    $eglible = $descriptions = array();

    if ($result)
        foreach ($result as $idx => $row) {
            $eglible[$row['item']] = escape_js($row['item']);
            $descriptions[$row['item']] = $row['id'] . ' :id';
        }

    if ($eglible) {
        print "this.eligible = [\"" . implode('","', $eglible) . "\"];\n";
        print "this.descriptions = [\"" . implode('","', $descriptions) . "\"];\n";
    }
    else
        print "false;\n";

    exit;
}

$rule = (isset($_POST['rule'])) ? $_POST['rule'] : NULL;
$rule_id = (isset($_GET['id'])) ? (int) $_GET['id'] : 0;
$error = array();

if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $DB->BeginTrans();

    $DB->Execute('DELETE FROM voip_rules WHERE id = ?', array($rule_id));
    $DB->Execute('DELETE FROM voip_group_rule_assignments WHERE ruleid = ?', array($rule_id));

    $rule = NULL;
    $rule_id = 0;

    $DB->CommitTrans();
}

if ($rule) {
    if (!$rule_id)
        $rule_id = $DB->GetOne("SELECT id FROM voip_rules WHERE name = ?", array($rule['name']));

    if (!$rule['name'])
        $error['name'] = trans("Tariff rule name is required!");
    else if (isset($rule_id) && $rule_id != $rule['id'])
        $error['name'] = trans("Tariff rule with specified name already exists!");

    if (empty($rule['group']))
        $error['group_search'] = trans('Tariff rule must contains at least one group!');
    else {
        foreach ($rule['group'] as $v) {
            $p = 'price' . $v['id'];
            $u = 'units' . $v['id'];

            if (!is_numeric($v['price']) && $v['price'] != '')
                $error[$p] = trans("Incorrect value!");
            else if ($v['price'] < 0)
                $error[$p] = trans("Number must be positive!");

            if (!is_numeric($v['units']) && $v['units'] != '')
                $error[$u] = trans("Incorrect value!");
            else if ($v['units'] < 0)
                $error[$u] = trans("Number must be positive!");
        }
    }

    if (!$error) {
        // if rule name doesn't exists then create and get id else clear all current groups assigned to this rule
        if (!$rule_id) {
            $DB->Execute('INSERT INTO voip_rules (name, description) VALUES (?, ?)', array($rule['name'], $rule['description']));
            $rule_id = $DB->GetOne("SELECT id FROM voip_rules WHERE name = ?", array($rule['name']));
            $rule['id'] = $rule_id;
        } else {
            $DB->Execute('DELETE FROM voip_group_rule_assignments WHERE ruleid = ?', array($rule_id));
        }

        $DB->Execute('UPDATE
                        voip_rules SET name = ?, description = ?
                     WHERE
                        id = ?', array($rule['name'], $rule['description'], $rule['id']));

        $group_values = array();
        foreach($rule['group'] as $v) {
            $group_values[] = "($rule_id, " . $v['id'] . ",'" . serialize(array('price' => str_replace(',', '.', $v['price']),
                                                                                'units' => str_replace(',', '.', $v['units']))) . "')";
        }

        $DB->Execute('INSERT INTO
                        voip_group_rule_assignments (ruleid, groupid, rule_settings)
                      VALUES ' . implode(',', $group_values));
    }

    $SMARTY->assign('rule', $rule);
}

if (isset($_GET['id']) && $rule_id) {
    $tmp_rule = $DB->GetAll('SELECT
                                r.id, r.name, r.description, gr.rule_settings,
                                gr.groupid, g.name as groupname
                             FROM
                                voip_rules r
                                left join voip_group_rule_assignments gr on r.id = gr.ruleid
                                left join voip_prefix_groups g on gr.groupid = g.id
                             WHERE
                                r.id = ?', array($rule_id));

    $rule['id'] = $tmp_rule[0]['id'];
    $rule['description'] = $tmp_rule[0]['description'];
    $rule['name'] = $tmp_rule[0]['name'];

    foreach ($tmp_rule as $single_rule) {
        $gid = $single_rule['groupid'];
        $settings = unserialize($single_rule['rule_settings']);

        $rule['group'][$gid] = array('id'    => $gid,
                                     'name'  => $single_rule['groupname'],
                                     'price' => $settings['price'],
                                     'units' => $settings['units']);
    }

    $SMARTY->assign('rule', $rule);
}

$SMARTY->assign('rule_list', $DB->GetAll('SELECT id, name FROM voip_rules'));
$SMARTY->assign('error', $error);
$SMARTY->display('voipaccount/voiptariffrules.html');

?>
