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

if (isset($_POST['voippoolnumber']))
{
    $voippoolnumber = array_map('trim', $_POST['voippoolnumber']);

    if (empty($voippoolnumber['name']))
        $error['name'] = trans('Network name is required!');
    elseif (preg_match('/[^a-zA-Z0-9\s]+/', $voippoolnumber['name']))
        $error['name'] = trans('Network name contains forbidden characters!');
    else if ($DB->Execute('SELECT 1 FROM voip_pool_numbers WHERE name ?LIKE? ?;', array($voippoolnumber['name'])))
        $error['name'] = trans('Name is already in use!');

    if (empty($voippoolnumber['poolstart']))
        $error['poolstart'] = trans('Pool start is required!');
    else if (strlen($voippoolnumber['poolstart']) > 20)
        $error['poolstart'] = trans('Value is too long (max. $a characters)!', 20);
    else if (preg_match('/[^0-9]/i', $voippoolnumber['poolstart']))
        $error['poolstart'] = trans('Incorrect format! Only values 0 to 9.');
    else if ($DB->Execute('SELECT 1 FROM voip_pool_numbers WHERE ? BETWEEN poolstart AND poolend', array((string)$voippoolnumber['poolend'])))
        $error['poolstart'] = trans('Pool start is already in use!');

    if (empty($voippoolnumber['poolend']))
        $error['poolend'] = trans('Pool end is required!');
    else if (strlen($voippoolnumber['poolend']) > 20)
        $error['poolend'] = trans('Value is too long (max. $a characters)!', 20);
    else if (preg_match('/[^0-9]/i', $voippoolnumber['poolend']))
        $error['poolend'] = trans('Incorrect format! Only values 0 to 9.');
    else if ($DB->Execute('SELECT 1 FROM voip_pool_numbers WHERE ? BETWEEN poolstart AND poolend', array((string)$voippoolnumber['poolend'])))
        $error['poolend'] = trans('Pool end is already in use!');

    if (!isset($error['poolstart']) && !isset($error['poolend']) && $voippoolnumber['poolstart'] >= $voippoolnumber['poolend'])
        $error['poolstart'] = trans('Pool start must be lower that end number!');

    if (!$error && 1==0) {
        $DB->BeginTrans();

        $disabled = ($voippoolnumber['disabled'] == '1') ? 1 : 0;

        $DB->Execute('INSERT INTO voip_pool_numbers (disabled, name, poolstart, poolend, description)
                      VALUES (?,?,?,?,?)', array($disabled, $voippoolnumber['name'], $voippoolnumber['poolstart'], $voippoolnumber['poolend'], $voippoolnumber['description']));

        $DB->CommitTrans();
    }

    $SMARTY->assign('error', $error);
    $SMARTY->assign('voippoolnumber', $voippoolnumber);
}

$layout['pagetitle'] = trans('New Network');

if (!ConfigHelper::checkConfig('phpui.big_networks'))
    $SMARTY->assign('customers', $LMS->GetCustomerNames());

$SMARTY->assign('prefixlist', $LMS->GetPrefixList());
$SMARTY->assign('hostlist', $LMS->DB->GetAll('SELECT id, name FROM hosts ORDER BY name'));
$SMARTY->display('voipaccount/voippoolnumberadd.html');

?>
