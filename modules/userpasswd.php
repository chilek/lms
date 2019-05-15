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

$id = (isset($_GET['id'])) ? $_GET['id'] : Auth::GetCurrentUser();

if ($LMS->UserExists($id)) {
    if (isset($_POST['passwd'])) {
        $passwd = $_POST['passwd'];

        if ($passwd['passwd'] == '' || $passwd['confirm'] == '') {
            $error['password'] = trans('Empty passwords are not allowed!').'<BR>';
        }

        if ($passwd['passwd'] != $passwd['confirm']) {
            $error['password'] = trans('Passwords does not match!');
        }

        if (!check_password_strength($passwd['passwd'])) {
            $error['password'] = trans('The password should contain at least one capital letter, one lower case letter, one digit and should consist of at least 8 characters!');
        }
        
        if ($LMS->PasswdExistsInHistory($id, $passwd['passwd'])) {
            $error['password'] = trans('You already used this password!');
        }

        if (!$error) {
            $LMS->SetUserPassword($id, $passwd['passwd']);
            header('Location: ?'. $SESSION->get('backto'));
        }
    }

    $passwd['id'] = $id;

    $layout['pagetitle'] = trans('Password Change for User $a', $DB->GetOne('SELECT name FROM vusers WHERE id = ?', array($id)));

    $SMARTY->assign('error', $error);
    $SMARTY->assign('passwd', $passwd);
    $SMARTY->display('user/userpasswd.html');
} else {
    $SESSION->redirect('?m='. $SESSION->get('lastmodule'));
}
