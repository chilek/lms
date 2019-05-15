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

function refresh($params)
{
    global $SESSION;

    // xajax response
    $objResponse = new xajaxResponse();

    $iface = $params['interface'];
    $ipaddr = $params['ipaddr'];
    $received = $params['received'];
    $transmitted = $params['transmitted'];
    $type = $params['type'];

    switch ($type) {
        case 2:
            $arping_helper = ConfigHelper::getConfig('phpui.arping_helper');
            if (empty($arping_helper)) {
                $cmd = 'arping %i -c 1 -w 1.0';
            } else {
                $cmd = $arping_helper;
            }
            $summary_regexp = '/^sent+[[:blank:]]+[0-9]+[[:blank:]]+probes/i';
            $reply_regexp = '/unicast/i';
            $reply_detailed_regexp = '/\ \[(.*?)\].+\ ([0-9\.]+.+)$/';
            break;
        case 1:
        default:
            $ping_helper = ConfigHelper::getConfig('phpui.ping_helper');
            if (empty($ping_helper)) {
                $cmd = 'ping %i -c 1 -s 1450 -w 1.0';
            } else {
                $cmd = $ping_helper;
            }
            $summary_regexp = '/^[0-9]+[[:blank:]]+packets[[:blank:]]+transmitted/i';
            $reply_regexp = '/icmp_[rs]eq/';
            $reply_detailed_regexp = '/^([0-9]+).+icmp_[rs]eq=([0-9]+).+ttl=([0-9]+).+time=([0-9\.]+.+)$/';
            break;
    }
    if (preg_match('/^[a-zA-Z0-9\.:]+$/', $iface)) {
        $cmd = preg_replace('/%if/', $iface, $cmd);
    }
    if (check_ip($ipaddr)) {
        $cmd = preg_replace('/%i/', $ipaddr, $cmd);
    }
    exec($cmd, $output);
    $sent = preg_grep($summary_regexp, $output);
    $replies = preg_grep($reply_regexp, $output);
    $times = array();
    if (count($replies)) {
        if (count($sent) && preg_match('/^([0-9]+)/', current($sent), $matches)) {
            $transmitted += $matches[1];
        } else {
            $transmitted++;
        }

        $output = '';
        $seqs = array();
        $oldreceived = $received;
        foreach ($replies as $reply) {
            if (preg_match($reply_detailed_regexp, $reply, $matches)) {
                if (!isset($seqs[$matches[2]])) {
                    $seqs[$matches[2]] = true;
                    $received++;
                }
                if ($type == 1) {
                    $output .= trans(
                        '$a bytes from $b: icmp_req=$c ttl=$d time=$e',
                        $matches[1],
                        $ipaddr,
                        $oldreceived + $matches[2],
                        $matches[3],
                        $matches[4]
                    ).'<br>';
                    $times[] = $matches[4];
                } elseif ($type == 2) {
                    $output .= trans(
                        'Unicast reply from $a [$b]: time=$c',
                        $ipaddr,
                        $matches[1],
                        $matches[2]
                    ).'<br>';
                    $times[] = $matches[2];
                }
            }
        }
    } else {
        $output = trans('Destination Host Unreachable').'<br>';
    }
    if (empty($received)) {
        $received = '0';
    }

    $SESSION->save('ping_type', $type);
    $SESSION->close(); // force session state save

    $objResponse->append('data', 'innerHTML', $output);
    $objResponse->assign('transmitted', 'value', $transmitted);
    $objResponse->assign('received', 'value', $received);
    $objResponse->assign('total', 'innerHTML', trans(
        'Total: $a% ($b/$c)',
        ($transmitted ? round(($received / $transmitted) * 100) : 0),
        $received,
        $transmitted
    ));
    $objResponse->assign('times', 'value', urlencode(json_encode($times)));
    $objResponse->call('ping_reply');

    return $objResponse;
}

if (isset($_GET['p'])) {
    $SMARTY->assign('part', $_GET['p']);
    switch ($_GET['p']) {
        case 'main':
            $LMS->InitXajax();
            $LMS->RegisterXajaxFunction('refresh');
            $SMARTY->assign('xajax', $LMS->RunXajax());
            break;
        case 'titlebar':
        case 'ipform':
            if (!isset($_GET['popupid'])) {
                die;
            }
            $SMARTY->assign('popupid', $_GET['popupid']);
            break;
    }
}

$layout['pagetitle'] = trans('Ping');

if (isset($_GET['ip']) && check_ip($_GET['ip'])) {
    if (!empty($_GET['type'])) {
        $type = intval($_GET['type']);
    } else {
        $SESSION->restore('ping_type', $type);
    }

    if (!$type) {
        $type = ConfigHelper::getConfig('phpui.ping_type');
    }

    $SESSION->save('ping_type', $type);

    $SMARTY->assign('type', $type);
    $SMARTY->assign('ipaddr', $_GET['ip']);

    $netid = $LMS->GetNetIDByIP($_GET['ip']);

    if ($netid) {
        $SMARTY->assign('interface', $DB->GetOne('SELECT interface FROM networks WHERE id = ?', array($netid)));
    }
}

$SMARTY->display('ping.html');
