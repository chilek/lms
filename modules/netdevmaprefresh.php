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

if (isset($_GET['live'])) {
    $netdevmaprefresh_helper = ConfigHelper::getConfig('phpui.netdevmaprefresh_helper');
    if (empty($netdevmaprefresh_helper)) {
        $cmd = 'sudo /sbin/pinger-addresses';
    } else {
        $cmd = $netdevmaprefresh_helper;
    }
    exec($cmd, $output);
    if (count($output)) {
        $curtime = time();
        foreach ($output as $ip) {
            if (check_ip($ip)) {
                $DB->Execute(
                    'UPDATE nodes SET lastonline = ? WHERE ipaddr = INET_ATON(?)',
                    array($curtime, $ip)
                );
            }
        }
    }
}

include(MODULES_DIR.'/map.inc.php');

header('Content-Type: text/plain');

echo '{"devices":'.json_encode($devices).',"nodes":'.json_encode($nodes).'}';
