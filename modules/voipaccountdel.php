<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

$voipaccountid = intval($_GET['id']);
$voipaccountlogin = $LMS->GetVoipAccountLogin($voipaccountid);

if (!$LMS->VoipAccountExists($voipaccountid)) {
    $layout['pagetitle'] = trans('Delete Voip Account $a', $voipaccountlogin);
    $body = '<P>' . trans('Incorrect ID number') . '</P>';
    $body .= '<A HREF="?' . $SESSION->get('backto') . '">' . trans('Back') . '</A></P>';
    $SMARTY->assign('body', $body);
    $SMARTY->display('dialog.html');
} else {
    $owner = $LMS->GetVoipAccountOwner($voipaccountid);
    $LMS->DeleteVoipAccount($voipaccountid);
    if ($SESSION->is_set('backto')) {
        $SESSION->redirect('?'.$SESSION->get('backto'));
    } else {
        $SESSION->redirect('?m=customerinfo&id=' . $owner);
    }
}
