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

$id = intval($_GET['id']);

// LEFT join with domains for backward compat.
$account = $DB->GetRow('SELECT p.id, p.login, d.name AS domain 
		FROM passwd p
		LEFT JOIN domains d ON (p.domainid = d.id)
		WHERE p.id = ?', array($id));

if (!$account) {
    $SESSION->redirect('?'.$SESSION->get('backto'));
}
    
if (isset($_POST['passwd'])) {
    $account['passwd1'] = $_POST['passwd']['passwd'];
    $account['passwd2'] = $_POST['passwd']['confirm'];
    
    if ($account['passwd1'] != $account['passwd2']) {
        $error['passwd'] = trans('Passwords does not match!');
    } elseif ($account['passwd1'] == '') {
        $error['passwd'] = trans('Empty passwords are not allowed!');
    }
    
    if (!$error) {
        $DB->Execute(
            'UPDATE passwd SET password = ? WHERE id = ?',
            array(crypt($account['passwd1']), $id)
        );
    
        $SESSION->redirect('?'.$SESSION->get('backto'));
    }
}

$layout['pagetitle'] = trans('Password Change for Account: $a', $account['login'].'@'.$account['domain']);
        
$SMARTY->assign('error', $error);
$SMARTY->assign('account', $account);

$SMARTY->display('account/accountpasswd.html');
