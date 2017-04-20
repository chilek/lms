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

define('NETUSAGE_STATE_FREE', 0);
define('NETUSAGE_STATE_USED', 1);

// network_size => mask
$netsize2mask = array(
    65536 => 16,
    32768 => 17,
    16384 => 18,
    8192  => 19,
    4096  => 20,
    2048  => 21,
    1024  => 22,
    512   => 23,
    256   => 24,
    128   => 25,
    64    => 26,
    32    => 27,
    16    => 28,
    8     => 29,
    4     => 30
);

function matchNetwork( &$networks, $n ) {
    global $netsize2mask;
    $size = $n['br_long'] - $n['ip_long'] + 1;
    $curr_ip = $n['ip_long'];
    $matched_masks = array();

    foreach ( $netsize2mask as $s => $m ) {
        if ( $size < $s ) {
            continue;
        }

        $size -= $s;
        $matched_masks[] = array('mask'=>$m, 'size'=>$s);
    }

    foreach ( array_reverse($matched_masks) as $v ) {
        $networks[$curr_ip] = array(
            'ip'    => long2ip($curr_ip),
            'mask'  => $v['mask'],
            'state' => NETUSAGE_STATE_FREE
        );

        $curr_ip += $v['size'];
    }
}

function getNetworks( $ip, $br ) {
    $ip_long = ip_long($ip); // network address
    $br_long = ip_long($br); // broadcast address

    $networks = LMSDB::GetInstance()->GetAllByKey('
        SELECT address as ip_long, mask as mask_ip
        FROM networks
        WHERE address >= ? AND address < ?
        ORDER BY ip_long;', 'ip_long', array($ip_long, $br_long)
    );

    foreach ( $networks as $k=>$v ) {
        $networks[$k]['ip']      = long2ip($networks[$k]['ip_long']);
        $networks[$k]['br_long'] = ip_long(getbraddr(long2ip($v['ip_long']), $v['mask_ip']));
        $networks[$k]['mask']    = mask2prefix($v['mask_ip']);
        $networks[$k]['state']   = NETUSAGE_STATE_USED;
    }

    $spaces  = array();
    $counter = -1;

    // find leaks in network range
    //
    for ( $i=$ip_long; $i<=$br_long; ++$i) {
        if ( isset($networks[$i]) ) {
            if ( $counter != -1 ) {
                $spaces[] = array(
                    'ip_long' => $counter,
                    'br_long' => $i-1,
                    'state'   => NETUSAGE_STATE_FREE);
            }

            $i = $networks[$i]['br_long'];
            $counter = -1;
        } else if ( $counter == -1 ) {
            $counter = $i;
        } else if ( $i == $br_long ) {
            $spaces[] = array(
                'ip_long' => $counter,
                'br_long' => $i,
                'state'   => NETUSAGE_STATE_FREE);
        }
    }

    // foreach by every leak and explode to single networks
    //
    foreach ( $spaces as $v ) {
        matchNetwork( $networks, $v);
    }

    // sort asc
    //
    ksort($networks);

    return $networks;
}

if ( isset($_GET['ajax']) ) {
    $ip   = $_POST['ip'];
    $mask = intval($_POST['mask']);
    $html = '';

    $SMARTY->assign('ip', $ip);

    if ( $mask < 24 ) {
        $SMARTY->assign('mask', 24);

        $counter = 2 * pow(2, 24-$mask-1) - 1;
        for ($i=0; $i<=$counter; ++$i) {
            $SMARTY->assign('ip'       , long2ip(ip_long($ip) + $i * 256));
            $SMARTY->assign('network'  , $i == 0        ? true : false);
            $SMARTY->assign('broadcast', $i == $counter ? true : false);

            $html .= $SMARTY->fetch('net/network_container.html');
        }
    } else {
        $ip_start = ip_long($ip);
        $ip_end   = $ip_start + pow(2, 32-$mask) - 1;

        $used_ips = $DB->GetAllByKey('
            SELECT
                ipaddr as ip, n.name, nd.name as netdev_name,
                CASE WHEN n.ownerid = 0 THEN nd.id ELSE n.id END as id
            FROM
                nodes n
                LEFT JOIN netdevices nd ON n.ownerid = 0 AND nd.id = n.netdev
            WHERE
                n.ipaddr >= ? AND
                n.ipaddr <= ?;', 'ip', array($ip_start , $ip_end)
        );

        $SMARTY->assign('used_ips' , $used_ips);
        $SMARTY->assign('network'  , isset($_POST['network'])   ? true : false);
        $SMARTY->assign('broadcast', isset($_POST['broadcast']) ? true : false);
        $SMARTY->assign('pool'     , array('start'=>$ip_start, 'end'=>$ip_end));

        $html .= $SMARTY->fetch('net/network_container.html');
    }

    die( $html );
}

if ( isset($_POST['ip']) && isset($_POST['mask']) ) {
    $ip = getnetaddr($_POST['ip'], $_POST['mask']);
    $br = getbraddr($_POST['ip'], $_POST['mask']);

    if ( !$ip ) {
        $error['ip'] = trans('Incorrect IP address or mask');
    }

    if ( $error ) {
        $SMARTY->assign('error', $error);
    } else {
        $SMARTY->assign('ip', $ip);
        $SMARTY->assign('network_list', getNetworks($ip, $br));
    }
}

$layout['pagetitle'] = trans('IP Network Search');

$SMARTY->assign('mask', mask2prefix($_POST['mask']));
$SMARTY->assign('ip'  , $_POST['ip']);
$SMARTY->display('net/netusage.html');

?>
