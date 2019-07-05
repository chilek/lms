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

if (!preg_match('/^[0-9]+$/', $_GET['id'])) {
    $SESSION->redirect('?m=voipaccountlist');
}

if (!$LMS->VoipAccountExists($_GET['id'])) {
    if (isset($_GET['ownerid'])) {
        $SESSION->redirect('?m=customerinfo&id='.$_GET['ownerid']);
    } else {
        $SESSION->redirect('?m=voipaccountlist');
    }
}

$voipaccountid = $_GET['id'];
$voipaccountinfo = $LMS->GetVoipAccount($voipaccountid);
$customerid = $voipaccountinfo['ownerid'];

include(MODULES_DIR.'/customer.inc.php');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if (!isset($_GET['ownerid'])) {
    $SESSION->save('backto', $SESSION->get('backto').'&ownerid='.$customerid);
}

$layout['pagetitle'] = trans('Voip Account Info: $a', $voipaccountinfo['login']);

$hook_data = $plugin_manager->executeHook(
    'voipaccountinfo_before_display',
    array(
        'voipaccountinfo' => $voipaccountinfo,
        'smarty' => $SMARTY,
    )
);

$voipaccountinfo = $hook_data['voipaccountinfo'];

$LMS->InitXajax();
$SMARTY->assign('xajax', $LMS->RunXajax());

$SMARTY->assign('voipaccountinfo', $voipaccountinfo);
$SMARTY->display('voipaccount/voipaccountinfo.html');
