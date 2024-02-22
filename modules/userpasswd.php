<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

$id = $_GET['id'] ?? Auth::GetCurrentUser();

if ($LMS->UserExists($id)) {
    $net = isset($_GET['net']) ? 1 : 0;

    if (isset($_POST['password'])) {
        $passwd = $_POST['password'];

        if ($net) {
            if ($id == Auth::GetCurrentUser()
                && $LMS->isUserNetworkPasswordSet($id) && !$LMS->checkPassword($passwd['currentpasswd'], true)) {
                $error['currentpasswd'] = trans('Wrong current password!');
            } elseif ($passwd['passwd'] != $passwd['confirm']) {
                $error['passwd'] = trans('Passwords do not match!');
            } elseif ($passwd['passwd'] != '' && !check_password_strength($passwd['passwd'])) {
                $error['passwd'] = trans('The password should contain at least one capital letter, one lower case letter, one digit and should consist of at least 8 characters!');
            }
        } else {
            if ($id == Auth::GetCurrentUser() && !$LMS->checkPassword($passwd['currentpasswd'])) {
                $error['currentpasswd'] = trans('Wrong current password!');
            } elseif ($passwd['passwd'] == '' || $passwd['confirm'] == '') {
                $error['passwd'] = trans('Empty passwords are not allowed!').'<BR>';
            } elseif ($passwd['passwd'] != $passwd['confirm']) {
                $error['passwd'] = trans('Passwords do not match!');
            } elseif (!check_password_strength($passwd['passwd'])) {
                $error['passwd'] = trans('The password should contain at least one capital letter, one lower case letter, one digit and should consist of at least 8 characters!');
            } elseif ($LMS->PasswdExistsInHistory($id, $passwd['passwd'])) {
                $error['passwd'] = trans('You already used this password!');
            }
        }

        if (!$error) {
            $LMS->SetUserPassword($id, $passwd['passwd'], $net);
            $SESSION->redirect_to_history_entry();
        }
    }

    $passwd['id'] = $id;
    if ($net) {
        $passwd['netpasswd'] = $LMS->isUserNetworkPasswordSet($id);
    }

    $layout['pagetitle'] = trans('Password Change for User $a', $DB->GetOne('SELECT name FROM vusers WHERE id = ?', array($id)));

    $SMARTY->assign('error', $error);
    $SMARTY->assign('passwd', $passwd);
    if ($id == Auth::GetCurrentUser()) {
        $SMARTY->assign('current_password_required', true);
    }
    $SMARTY->assign('net', $net);
    $SMARTY->display('user/userpasswd.html');
} else {
    $SESSION->redirect('?m='. $SESSION->get('lastmodule'));
}
