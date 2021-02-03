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

class NetContainer
{
    private $networks;

    private $ip_start;
    private $ip_end;
    private $netsize2mask;

    public function __construct($ip_start, $ip_end)
    {
        $this->networks = array();
        $this->networks[$ip_start] = array();

        $this->ip_start = $ip_start;
        $this->ip_end   = $ip_end;

        $this->netsize2mask = array(
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
    }

    public function add(array $n)
    {
        for ($i=$n['ip_long']; $i<=$n['br_long']; ++$i) {
            if (isset($this->networks[$i])) {
                $this->networks[$i][] = array('host'=>$n['host'], 'net_name'=>$n['net_name']);
            } else {
                $this->networks[$i] = array(array('host'=>$n['host'], 'net_name'=>$n['net_name']));
            }
        }
    }

    public function fetchNetworks()
    {
        ksort($this->networks);

        $spaces  = array();
        $curr_ip = $this->ip_start;
        $end     = $this->ip_end + 1;

        for ($i=$this->ip_start+1; $i<=$end; ++$i) {
            if ($this->networks[$i-1] != $this->networks[$i] || $i==$end) {
                $size          = $i - $curr_ip;
                $matched_masks = array();

                foreach ($this->netsize2mask as $s => $m) {
                    if ($size < $s) {
                        continue;
                    }

                    $size -= $s;
                    $matched_masks[] = array('mask'=>$m, 'size'=>$s);

                    if ($size == 0) {
                        break;
                    }
                }

                foreach (array_reverse($matched_masks) as $v) {
                    $spaces[] = array(
                        'ip'    => long_ip($curr_ip),
                        'mask'  => $v['mask'],
                        'hosts' => $this->networks[$i-1]
                    );

                    $curr_ip += $v['size'];
                }
            }
        }

        return $spaces;
    }
}

/*!
 * \brief Function return netwoks by ip and broadcast address.
 *
 * \param  string $ip   IP address (192.168.0.0 etc.)
 * \param  string $br   Broadcast address (255.255.255.0 etc.)
 * \param  string $host Optional parameter for narrow netowrks to single host.
 * \return array
 */
function getNetworks($ip, $br, $host = null)
{
    $ip_long = ip_long($ip); // network address
    $br_long = ip_long($br); // broadcast address

    if ($host) {
        $sql  = 'AND h.name ?LIKE? ?';
        $data =  array($ip_long, $br_long, $host);
    } else {
        $sql  = '';
        $data =  array($ip_long, $br_long);
    }

    $networks = LMSDB::GetInstance()->GetAll('
        SELECT
            n.address as ip_long, n.mask as mask_ip, 
            h.name as host, h.id as host_id, n.name as net_name                  
        FROM networks n
            LEFT JOIN hosts h ON n.hostid = h.id
        WHERE address >= ? AND address < ? ' . $sql . '
        ORDER BY ip_long;', $data);

    foreach ($networks as $k => $v) {
        $networks[$k]['ip']      = long_ip($networks[$k]['ip_long']);
        $networks[$k]['br_long'] = ip_long(getbraddr(long_ip($v['ip_long']), $v['mask_ip']));

        if ($v['ip_long'] < $ip_long) {
            $ip_long = $v['ip_long'];
        }

        if ($networks[$k]['br_long'] > $br_long) {
            $br_long = $networks[$k]['br_long'];
        }
    }

    $nc = new NetContainer($ip_long, $br_long);

    foreach ($networks as $v) {
        $nc->add($v);
    }

    return $nc->fetchNetworks();
    ;
}

if (isset($_GET['ajax'])) {
    $ip   = $_POST['ip'];
    $mask = intval($_POST['mask']);
    $host = empty($_POST['host']) ? null : trim($_POST['host']);
    $html = '';

    $SMARTY->assign('ip', $ip);

    if ($mask < 24) {
        $SMARTY->assign('mask', 24);

        $counter = 2 * pow(2, 24-$mask-1) - 1;
        for ($i=0; $i<=$counter; ++$i) {
            $SMARTY->assign('ip', long_ip(ip_long($ip) + $i * 256));
            $SMARTY->assign('hosts', array(array('host'=>$host, 'net_name'=>$_POST['netname'])));

            $html .= $SMARTY->fetch('net/network_container.html');
        }
    } else {
        $ip_start = ip_long($ip);
        $ip_end   = $ip_start + pow(2, 32-$mask) - 1;
        $data     = array($ip_start , $ip_end);

        // if host is set then get only networks for specified host
        if ($host) {
            $data[] = $host;
        }

        $used_ips = $DB->GetAllByKey('
            SELECT
                ipaddr as ip, nod.name, nd.name as netdev_name,
                CASE WHEN nod.ownerid IS NULL THEN nd.id ELSE nod.id END as id
            FROM
                nodes nod
                LEFT JOIN netdevices nd ON nod.ownerid IS NULL AND nd.id = nod.netdev
                LEFT JOIN networks net  ON net.id = nod.netid
                LEFT JOIN hosts h       ON h.id = net.hostid
            WHERE
                nod.ipaddr >= ? AND
                nod.ipaddr <= ? ' . ($host ? ' AND h.name ?LIKE? ?' : ''), 'ip', $data);

        $full_network = $DB->GetRow('SELECT * FROM networks WHERE name ?LIKE? ?', array($_POST['netname']));

        $SMARTY->assign('used_ips', $used_ips);
        $SMARTY->assign('pool', array('start'=>$ip_start, 'end'=>$ip_end));
        $SMARTY->assign('network', $ip_start == $full_network['address'] ? 1 : 0);
        $SMARTY->assign('broadcast', long_ip($ip_end) == getbraddr(long_ip($ip_start), $full_network['mask']) ? 1 : 0);
        $SMARTY->assign('hostid', $full_network['id']);

        $html .= $SMARTY->fetch('net/network_container.html');
    }

    die($html);
}

if (isset($_POST['ip']) && isset($_POST['mask'])) {
    $ip   = getnetaddr($_POST['ip'], $_POST['mask']);
    $br   = getbraddr($_POST['ip'], $_POST['mask']);
    $host = isset($_POST['host']) ? trim($_POST['host']) : null;

    if (!$ip) {
        $error['ip'] = trans('Incorrect IP address or mask');
        $SMARTY->assign('error', $error);
    } else {
        $SMARTY->assign('network_list', getNetworks($ip, $br, $host));
    }
}

$layout['pagetitle'] = trans('IP Network Search');

$SMARTY->assign('host_list', $DB->GetAll('SELECT name FROM hosts;'));
$SMARTY->assign('selected_host', $_POST['host']);
$SMARTY->assign('mask', isset($_POST['mask']) ? mask2prefix($_POST['mask']) : 24);
$SMARTY->assign('ip', !empty($ip) ? $ip : $_POST['ip']);

$SMARTY->display('net/netusage.html');
