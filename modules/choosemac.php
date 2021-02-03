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

$layout['pagetitle'] = trans('Select MAC address');

$p = isset($_GET['p']) ? $_GET['p'] : '';
$js = '';

if (!$p) {
    $js = 'var targetfield = window.parent.targetfield;';
} elseif ($p == 'main') {
    $js = 'var targetfield = window.parent.targetfield;';

    $maclist = $LMS->GetMACs();

    if (ConfigHelper::getConfig('phpui.arpd_servers')) {
        $servers = preg_split('/[\t ]+/', ConfigHelper::getConfig('phpui.arpd_servers'));
        foreach ($servers as $server) {
            $res = explode(':', $server);
            if (!isset($res[1]) || $res[1] == '') {
                $res[1] = 1029;
            }

            $remote = $LMS->GetRemoteMACs($res[0], $res[1]);
            $maclist = array_merge($maclist, $remote);
        }
    }

    if (count($maclist)) {
        array_multisort($maclist['longip'], $maclist['mac'], $maclist['ip'], $maclist['nodename']);
    }

    $SMARTY->assign('maclist', $maclist);
}

$SMARTY->assign('part', $p);
$SMARTY->assign('js', $js);
$SMARTY->display('choose/choosemac.html');
