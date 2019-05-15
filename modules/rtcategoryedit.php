<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

if (! $LMS->CategoryExists($_GET['id'])) {
    $SESSION->redirect('?m=rtcategorylist');
}

if (isset($_POST['category'])) {
    $category = $_POST['category'];

    $category['id'] = $_GET['id'];

    if ($category['name'] == '') {
        $error['name'] = trans('Category name must be defined!');
    }

    if (isset($category['users'])) {
        foreach ($category['users'] as $key => $value) {
            $category['owners'][] = array('id' => $key, 'value' => $value);
        }
    }

    if (!$error) {
        $DB->Execute(
            'UPDATE rtcategories SET name=?, description=?, style=? WHERE id=?',
            array(trim($category['name']),
                    $category['description'],
                    $category['style'],
                    $category['id'])
        );

        $DB->Execute('DELETE FROM rtcategoryusers WHERE categoryid=?', array($category['id']));

        if (isset($category['owners'])) {
            foreach ($category['owners'] as $val) {
                $DB->Execute(
                    'INSERT INTO rtcategoryusers(userid, categoryid) VALUES(?, ?)',
                    array($val['id'], $category['id'])
                );
            }
        }

        $SESSION->redirect('?m=rtcategoryinfo&id='.$category['id']);
    }

    $users = $LMS->GetUserNames();

    foreach ($users as $user) {
        $user['owner'] = isset($category['users'][$user['id']]);
        $category['nowners'][] = $user;
    }
    $category['owners'] = $category['nowners'];
} else {
    $category = $LMS->GetCategory($_GET['id']);
}

$layout['pagetitle'] = trans('Category Edit: $a', $category['name']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('category', $category);
$SMARTY->assign('error', $error);
$SMARTY->display('rt/rtcategoryedit.html');
