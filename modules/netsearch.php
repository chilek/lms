<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

if (isset($_GET['ajax'])) {
    header('Content-type: text/plain');
    $netsearch = urldecode(trim($_GET['what']));

    switch ($_GET['mode']) {
        case 'name':
        case 'inet_ntoa(address)':
        case 'interface':
        case 'notes':
        case 'domain':
        case 'wins':
        case 'gateway':
            $candidates = $DB->GetAll('SELECT
													' . $_GET['mode'] . ' as item,
													count(id) AS entries
												FROM
													networks
												WHERE
													' . $_GET['mode'] . ' != \'\' AND lower(' .$_GET['mode'] . ') ?LIKE? lower(' . $DB->Escape('%' . $netsearch . '%') . ')
												GROUP BY
													item
												ORDER BY
													entries DESC, item ASC
												LIMIT 15');
            break;

        case 'host':
            $candidates = $DB->GetAll('SELECT
													h.name as item,
													count(*) AS entries
												FROM
													networks n left join hosts h on n.hostid = h.id
												WHERE
													h.name != \'\' AND lower(h.name) ?LIKE? lower(' . $DB->Escape('%' . $netsearch . '%') . ')
												GROUP BY
													item
												ORDER BY
													entries DESC, item ASC
												LIMIT 15');
            break;

        case 'dns':
            $candidates = $DB->GetAll('SELECT
													dns as item,
													count(id) AS entries
												FROM
													networks
												WHERE
													dns != \'\' AND lower(dns) ?LIKE? lower(' . $DB->Escape('%' . $netsearch . '%') . ')
												GROUP BY
													item
												ORDER BY
													entries DESC, item ASC
												LIMIT 15');
    
            $candidates2 = $DB->GetAll('SELECT
													dns2 as item,
													count(id) AS entries
												FROM
													networks
												WHERE
													dns2 != \'\' AND lower(dns2) ?LIKE? lower(' . $DB->Escape('%' . $netsearch . '%') . ')
												GROUP BY
													item
												ORDER BY
													entries DESC, item ASC
												LIMIT 15');

            if (empty($candidates)) {
                $candidates = array();
            }
            
            if (empty($candidates2)) {
                $candidates2 = array();
            }

            $candidates = array_merge($candidates, $candidates2);
            break;

        default:
            exit;
    }
                                        
    $result = array();

    if ($candidates) {
        foreach ($candidates as $idx => $row) {
            $name = $row['item'];
            $name_class = '';
            $description = $row['entries'] . ' ' . trans('entries');
            $description_class = '';
            $action = '';

            $result[$row['item']] = compact('name', 'name_class', 'description', 'description_class', 'action');
        }
    }
    header('Content-Type: application/json');
    if (!empty($result)) {
        echo json_encode(array_values($result));
    }
    exit;
}

if (isset($_POST['search'])) {
    $netsearch = $_POST['search'];
}

if (!isset($netsearch)) {
    $SESSION->restore('netsearch', $netsearch);
} else {
    $SESSION->save('netsearch', $netsearch);
}

if (!isset($_GET['searchform']) && !empty($netsearch)) {
    $layout['pagetitle'] = trans('IP Network Search Results');

    $netsearch['count'] = true;
    $count = intval($LMS->GetNetworkList($netsearch));

    $netsearch['count'] = false;
    if ($count == 1) {
        $netsearch['offset'] = 0;
        $netsearch['limit'] = 1;
    } else {
        if (isset($_GET['o'])) {
            $netsearch['order'] = $_GET['o'];
        }

        if (!isset($_GET['page'])) {
            $SESSION->restore('ndlsp', $_GET['page']);
        }

        $SESSION->save('ndlsp', $page);
        $page = (! $_GET['page'] ? 1 : intval($_GET['page']));
        $netsearch['limit'] = intval(ConfigHelper::getConfig('phpui.networklist_pagelimit', $count));
        $netsearch['offset']= ($page - 1) * $netsearch['limit'];

        $SESSION->save('ndlsp', $page);
    }
    $netlist = $LMS->GetNetworkList($netsearch);

    if ($count == 1) {
        $SESSION->redirect('?m=netinfo&id=' . $netlist[0]['id']);
    } else {
        $listdata['order'] = $netlist['order'];
        $listdata['direction'] = $netlist['direction'];
        $listdata['online'] = $netlist['online'];
        $listdata['assigned'] = $netlist['assigned'];
        $listdata['size'] = $netlist['size'];

        unset($netlist['order'], $netlist['direction'], $netlist['online'], $netlist['assigned'], $netlist['size']);

        $pagination = LMSPaginationFactory::getPagination(
            $page,
            $count,
            $netsearch['limit'],
            ConfigHelper::checkConfig('phpui.short_pagescroller')
        );

        $SMARTY->assign('netlist', $netlist);
        $SMARTY->assign('listdata', $listdata);
        $SMARTY->assign('pagination', $pagination);
        $SMARTY->assign('search', true);
        $SMARTY->display('net/netlist.html');
    }
} else {
    $layout['pagetitle'] = trans('IP Network Search');

    $SESSION->remove('ndlsp');
    $SMARTY->assign('autosuggest_placement', ConfigHelper::getConfig('phpui.default_autosuggest_placement'));
    //$SMARTY->assign('k', $k);
    $SMARTY->display('net/netsearch.html');
}
