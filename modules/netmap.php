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

if ( isset($_GET['ip']) && isset($_GET['mask']) ) {
    $ip = getnetaddr($_GET['ip'], $_GET['mask']);
    $br = getbraddr($_GET['ip'], $_GET['mask']);

    if ( !$ip || !$br ) {
        $error = array(
            'msg' => trans('Incorrect IP address or mask')
        );

        die( json_encode($error) );
    }

    // network address
    $ip_long = ip2long($ip);

    // broadcast address
    $br_long = ip2long($br);

    // get ips
    $used_ips_tmp = $DB->GetAll('
        SELECT
          ipaddr as ip, n.name, nd.name as netdev_name,
          CASE WHEN n.ownerid = 0 THEN nd.id ELSE n.id END as id
        FROM
          nodes n
          LEFT JOIN netdevices nd ON n.ownerid = 0 AND nd.id = n.netdev
        WHERE n.ipaddr > ? AND n.ipaddr < ?;', array($ip_long, $br_long)
    );

    $data = array(
        'data'     => array(),
        'ip_start' => $ip_long,
        'ip_end'   => $br_long
    );

    if ( $used_ips_tmp ) {
        foreach ($used_ips_tmp as $k => $v) {
            if (isset($used_ips['data'][$v['ip']])) {
                if (is_array($used_ips['data'][$v['ip']])) {
                    $data['data'][$v['ip']][] = $v;
                } else {
                    $data['data'][$v['ip']] = array($data['data'][$v['ip']], $v);
                }
            } else {
                $data['data'][$v['ip']] = $v;
            }
        }
    }

    die( json_encode($data) );
}

$layout['pagetitle'] = trans('IP Network Search');
$SMARTY->display('net/netmap.html');

?>
