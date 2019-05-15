<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

$layout['pagetitle'] = trans('Select IP address');

$networks = $LMS->GetNetworks(true);

$p = isset($_GET['p']) ? $_GET['p'] : '';

if (!$p || $p == 'main') {
    $js = 'var targetfield1 = window.parent.targetfield1;var targetfield2 = window.parent.targetfield2;';
} else {
    $js = '';
}

$privnetid = isset($_GET['privnetid']) ? $_GET['privnetid'] : null;

if (isset($_POST['netid']) && $_POST['netid']) {
    $netid = $_POST['netid'];
} elseif (isset($_GET['netid']) && $_GET['netid']) {
    $netid = $_GET['netid'];
} elseif ($privnetid) {
    $netid = $LMS->GetPublicNetworkID($privnetid);
} elseif ($SESSION->is_set('netid')) {
    $SESSION->restore('netid', $netid);
}

if ($privnetid) {
    // leave only network which is assigned to main (private) network (optionally)
    if (ConfigHelper::checkConfig('phpui.show_assigned_networks_only')) {
        foreach ($networks as $idx => $row) {
            if ($row['id'] != $netid) {
                unset($networks[$idx]);
            }
        }
    }
}

// hide private networks for public address selection
// and hide public networks for private address selection (optionally)
if (ConfigHelper::checkConfig('phpui.autodetect_network_types')) {
    foreach ($networks as $idx => $row) {
        if ($privnetid && preg_match('/^(192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.|100\.64\.|100\.68\.)/', $row['address'])) {
            unset($networks[$idx]);
        } else if (!$privnetid && !preg_match('/^(192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.|100\.64\.|100\.68\.)/', $row['address'])) {
            unset($networks[$idx]);
        }
    }
}

if (empty($netid)) {
    $network = reset($networks);
    $netid = $network['id'];
}

if (isset($_POST['page'])) {
    $page = $_POST['page'];
} elseif (isset($_GET['page'])) {
    $page = $_GET['page'];
} elseif ($SESSION->is_set('ntlp.page.'.$netid)) {
    $SESSION->restore('ntlp.page.'.$netid, $page);
} else {
    $page = 1;
    $firstfree = true;
}

if (isset($_POST['ip'])) {
    $ip = $_POST['ip'];
} elseif (isset($_GET['ip'])) {
    $ip = $_GET['ip'];
} else {
    $ip = null;
}

$network = array();

switch ($p) {
    case 'main':
        if (!empty($ip)) {
            $page = $LMS->GetNetworkPageForIp($netid, $ip);
        }

        $network = $LMS->GetNetworkRecord(
            $netid,
            $page,
            ConfigHelper::getConfig('phpui.networkhosts_pagelimit'),
            isset($firstfree) ? true : false
        );

        $page = $network['page'];

        $SESSION->save('ntlp.pages.' . $netid, $network['pages']);
        $SESSION->save('ntlp.page.' . $netid, $page);
        break;
    case 'top':
        $SESSION->save('ntlp.page.' . $netid, $page);
        break;
    case 'down':
        $SESSION->restore('ntlp.page.' . $netid, $network['page']);
        $SESSION->restore('ntlp.pages.' . $netid, $network['pages']);
        if (!isset($network['pages'])) {
            $network = $LMS->GetNetworkRecord(
                $netid,
                $page,
                ConfigHelper::getConfig('phpui.networkhosts_pagelimit'),
                isset($firstfree) ? true : false
            );
        }
        $SESSION->save('ntlp.pages.' . $netid, $network['pages']);
        $SESSION->save('ntlp.page.' . $netid, $network['page']);
        break;
    default:
        if (!isset($firstfree)) {
            $SESSION->save('ntlp.page.' . $netid, $page);
        }
        break;
}

$SESSION->save('netid', $netid);

$SMARTY->assign('part', $p);
$SMARTY->assign('js', $js);
$SMARTY->assign('networks', $networks);
$SMARTY->assign('network', $network);
$SMARTY->assign('netid', $netid);
$SMARTY->assign('privnetid', $privnetid);
$SMARTY->assign('device', isset($_GET['device']) ? $_GET['device'] : null);
$SMARTY->assign('ip', $ip);
$SMARTY->display('choose/chooseip.html');
