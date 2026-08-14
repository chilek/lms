<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;

$addvlan = $_POST['addvlan'] ?? array();
$layout['pagetitle'] = trans('VLAN List');

$params['orderby'] = $_GET['orderby'] ?? null;

$vlanlist = $LMS->GetVlanList($params);
$netnodelist = $LMS->GetNetNodes();

switch ($action) {
    case 'add':
        if (!empty($addvlan['vlanid'])) {
            $LMS->AddVlan($addvlan);
            $SESSION->redirect('?m=vlanlist');
        }
        break;
    case 'modify':
        $vlaninfo = $LMS->GetVlanInfo($id);
        $SMARTY->assign('vlaninfo', $vlaninfo);
        if (!empty($id) && isset($addvlan)) {
            $addvlan['id'] = $id;
            $LMS->UpdateVlan($addvlan);
            $SESSION->redirect('?m=vlanlist');
        }
        break;
    case 'delete':
        if (!empty($id)) {
            $LMS->DeleteVlan($id);
            $SESSION->redirect('?m=vlanlist');
        }
        break;
    default:
        break;
}
$SMARTY->assign('action', $action);
$SMARTY->assign('vlanlist', $vlanlist);
$SMARTY->assign('netnodelist', $netnodelist);
$SMARTY->assign('pagetitle', $layout['pagetitle']);
$SMARTY->assign('pagelimit', ConfigHelper::getConfig('phpui.vlanlist_pagelimit', 100));
$SMARTY->display('vlan/vlanlist.html');
