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

/*!
 * \brief Function responsible for insert groups to database.
 *
 * \param  array   $groups array with groups
 * \return boolean         query result
 */
function insertGroups(array $groups)
{
    if (!$groups) {
        return false;
    }

    $DB = LMSDB::getInstance();
    $r = $DB->Execute('INSERT INTO
                        voip_rules
                        (rule_group_id, prefix_group_id, settings)
                       VALUES ' . implode(',', $groups));

    return $r ? true : false;
}

/*!
 * \param Function serialize selected values from array.
 *
 * \param  array  $rule array with paramteres
 * \return string       serialized array
 */
function serializeRuleParams(array $rule)
{
    $tmp = array();

    if (isset($rule['price'])) {
        $tmp['price'] = str_replace(',', '.', $rule['price']);
    }

    if (isset($rule['units'])) {
        $tmp['units'] = str_replace(',', '.', $rule['units']);
    }

    if (isset($rule['unit_size'])) {
        $tmp['unit_size'] = str_replace(',', '.', $rule['unit_size']);
    }

    return serialize($tmp);
}

/*!
 * \brief Function load rule groups to array
 *
 * \param  int   rule id to load
 * \return array
 */
function getRuleGroups($id)
{
    $DB = LMSDB::getInstance();
    $tmp = $DB->GetAll('SELECT
                            r.id, r.name, r.description, vr.settings,
                            vr.prefix_group_id, g.name as groupname, vr.id as ruleid
                         FROM
                            voip_rule_groups r
                            left join voip_rules vr on r.id = vr.rule_group_id
                            left join voip_prefix_groups g on vr.prefix_group_id = g.id
                         WHERE
                            r.id = ?', array($id));

    $rules = array();
    $rules['id']          = $tmp[0]['id'];
    $rules['description'] = $tmp[0]['description'];
    $rules['name']        = $tmp[0]['name'];

    foreach ($tmp as $rule) {
        $rid = $rule['ruleid'];
        $settings = unserialize($rule['settings']);

        $rules['group'][$rid] = array('groupid'   => $rule['prefix_group_id'],
                                      'ruleid'    => $rid,
                                      'name'      => $rule['groupname'],
                                      'price'     => $settings['price'],
                                      'units'     => $settings['units'],
                                      'unit_size' => $settings['unit_size']);
    }

    return $rules;
}

/*!
 * \brief Xajax function responsible for return table row with values to web browser.
 *
 * \param  string $name         group name
 * \param  int    $def_price    default price
 * \param  int    $def_units    default number of units to use
 * \param  int    $def_unitsize default unit size in seconds
 * \return NULL                 when name doesn't exists or is incorrect
 * \return array                when all it's good
 */
function getGroupTableRow($name, $def_price = '', $def_units = '', $def_unit_size = '')
{

    $JSResponse = new xajaxResponse();

    if (empty($name)) {
        $group = null;
        $JSResponse->call('addGroup', '');
    } else {
        $group = LMSDB::getInstance()->GetRow('SELECT id as groupid, name
                                               FROM voip_prefix_groups
                                               WHERE name = ?', array($name));
        $id = time();
        $default = array('price'     => $def_price,
                         'units'     => $def_units,
                         'unit_size' => $def_unit_size);

        global $SMARTY;
        $SMARTY->assign('default', $default);
        $SMARTY->assign('row_id', $id);
        $SMARTY->assign('group', $group);

        $row = $SMARTY->fetch('voipaccount/voiptarifftablerow.html');

        $JSResponse->call('addGroup', array($id, $row));
    }

    return $JSResponse;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction(array('getGroupTableRow'));
$SMARTY->assign('xajax', $LMS->RunXajax());

if (isset($_GET['ajax'])) {
    header('Content-type: text/plain');
    $search = urldecode(trim($_GET['what']));

    $candidates = $DB->GetAll('SELECT id, name as item
                           FROM voip_prefix_groups
                           WHERE name ?LIKE? ?
                           LIMIT 20', array('%'.$search.'%'));

    $result = array();
    if ($candidates) {
        foreach ($candidates as $idx => $row) {
            $name = $row['item'];
            $name_class = '';
            $description = $row['id'] . ' :id';
            $description_class = '';
            $action = '';

            $result[$row['item']] = compact('name', 'name_class', 'description', 'description_class', 'action');
        }
    }
    header('Content-Type: application/json');
    echo json_encode(array_values($result));
    exit;
}

$rule    = (isset($_POST['rule'])) ? $_POST['rule'] : null;
$rule_id = (isset($_GET['id'])) ? (int) $_GET['id'] : 0;
$error   = array();

if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $DB->BeginTrans();

    $DB->Execute('DELETE FROM voip_rule_groups
                  WHERE id = ?', array($rule_id));

    $DB->Execute('DELETE FROM voip_rules
                  WHERE rule_group_id = ?', array($rule_id));

    $rule    = null;
    $rule_id = 0;

    $DB->CommitTrans();
} else if ($rule) {
    if (!$rule_id) {
        $rule_id = $DB->GetOne("SELECT id FROM voip_rule_groups WHERE name = ?", array($rule['name']));
    }

    if (!$rule['name']) {
        $error['name'] = trans("Tariff rule name is required!");
    } else if (isset($rule_id) && $rule_id != $rule['id']) {
        $error['name'] = trans("Tariff rule with specified name already exists!");
    }

    if (empty($rule['group'])) {
        $error['group_search'] = trans('Tariff rule must contains at least one group!');
    } else {
        foreach ($rule['group'] as $v) {
            $p  = 'price' . $v['ruleid'];
            $u  = 'units' . $v['ruleid'];
            $us = 'unit_size' . $v['ruleid'];

            if (!is_numeric($v['price']) && $v['price'] != '') {
                $error[$p] = trans("Incorrect value!");
            } else if ($v['price'] < 0) {
                $error[$p] = trans("Number must be positive!");
            }

            if (!is_numeric($v['units']) && $v['units'] != '') {
                $error[$u] = trans("Incorrect value!");
            } else if ($v['units'] < 0) {
                $error[$u] = trans("Number must be positive!");
            }

            if (!is_numeric($v['unit_size']) && $v['unit_size'] != '') {
                $error[$us] = trans("Incorrect value!");
            } else if ($v['units'] < 0) {
                $error[$us] = trans("Number must be positive!");
            }
        }
    }

    $SMARTY->assign('rule', $rule);

    if (!$error) {
        $new_groups = array();

        if (!$rule_id) {
            $DB->Execute('INSERT INTO voip_rule_groups (name, description) VALUES (?, ?)', array($rule['name'], $rule['description']));
            $rule['id'] = $rule_id = $DB->GetLastInsertID('voip_rule_groups');

            foreach ($rule['group'] as $v) {
                $settings = serializeRuleParams($v);
                $new_groups[] = "($rule_id, " . $v['groupid'] . ",'$settings')";
            }
        } else {
            $DB->Execute(
                'UPDATE voip_rule_groups SET name = ?, description = ? WHERE id = ?',
                array($rule['name'], $rule['description'], $rule_id)
            );

            $dbrules = $DB->GetAllByKey('SELECT id, rule_group_id, prefix_group_id, settings
	                                     FROM voip_rules
	                                     WHERE rule_group_id = ?', 'id', array($rule_id));

            foreach ($rule['group'] as $v) {
                $settings = serializeRuleParams($v);

                if (isset($dbrules[$v['ruleid']])) {
                    $DB->Execute('UPDATE voip_rules SET settings = ?
	                              WHERE id = ?', array($settings, $v['ruleid']));
                } else {
                    $new_groups[] = "($rule_id, " . $v['groupid'] . ",'$settings')";
                }
            }

            foreach ($dbrules as $v) {
                if (!isset($rule['group'][$v['id']])) {
                    $DB->Execute('DELETE FROM voip_rules
                                  WHERE id = ?', array($v['id']));
                }
            }
        }

        insertGroups($new_groups);
        $SMARTY->clearAssign('rule');
        $SMARTY->assign('rule', getRuleGroups($rule_id));
    }
} else if (isset($_GET['id']) && $rule_id) {
    $SMARTY->clearAssign('rule');
    $SMARTY->assign('rule', getRuleGroups($rule_id));
}

$SMARTY->assign('rule_list', $DB->GetAll('SELECT id, name FROM voip_rule_groups'));
$SMARTY->assign('error', $error);
$SMARTY->display('voipaccount/voiptariffrules.html');
