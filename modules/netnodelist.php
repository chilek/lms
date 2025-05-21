<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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


if ($api) {
    $search = array();
    $order = null;
} else {
    $layout['pagetitle'] = trans('Network Device Nodes');

    if (!isset($_GET['o'])) {
        $SESSION->restore('nnlo', $o);
    } else {
        $o = $_GET['o'];
    }
    $SESSION->save('nnlo', $o);

    if (!isset($_GET['t'])) {
        $SESSION->restore('nnft', $t);
    } else {
        $t = $_GET['t'];
    }
    $SESSION->save('nnft', $t);

    if (!isset($_GET['s'])) {
        if ($SESSION->is_set('nnfs')) {
            $SESSION->restore('nnfs', $s);
        } else {
            $s = -1;
        }
    } else {
        $s = $_GET['s'];
    }
    $SESSION->save('nnfs', $s);

    if (!isset($_GET['p'])) {
        $SESSION->restore('nnfp', $p);
    } else {
        $p = $_GET['p'];
    }
    $SESSION->save('nnfp', $p);

    if (!isset($_GET['w'])) {
        if ($SESSION->is_set('nnfw')) {
            $SESSION->restore('nnfw', $w);
        } else {
            $w = -1;
        }
    } else {
        $w = $_GET['w'];
    }
    $SESSION->save('nnfw', $w);

    if (!isset($_GET['d'])) {
        $SESSION->restore('nnfd', $d);
    } else {
        $d = $_GET['d'];
    }
    $SESSION->save('nnfd', $d);

    if (!isset($_GET['flags'])) {
        $SESSION->restore('nnfflags', $flags);
    } else {
        $flags = $_GET['flags'];
    }
    $SESSION->save('nnfflags', $flags);

    if (!isset($_GET['services'])) {
        $SESSION->restore('nnfservices', $services);
    } else {
        $services = $_GET['services'];
    }
    $SESSION->save('nnfservices', $services);

    $search = array(
        'status' => $s,
        'type' => $t,
        'invprojectid' => $p,
        'ownership' => $w,
        'divisionid' => $d,
        'flags' => $flags,
        'services' => $services,
    );

    $search['count'] = true;
    $total = intval($LMS->GetNetNodeList($search, $o));

    $limit = intval(ConfigHelper::getConfig('phpui.netnodelist_pagelimit', $total));
    if ($SESSION->is_set('nnlp') && !isset($_GET['page'])) {
        $SESSION->restore('nnlp', $_GET['page']);
    }
    $page = !isset($_GET['page']) ? 1 : intval($_GET['page']);
    $offset = ($page - 1) * $limit;
    $search['offset'] = $offset;
    $search['limit'] = $limit;
}
$search['count'] = false;
$nlist = $LMS->GetNetNodeList($search, $o);

if (!$api) {
    $listdata = $search;

    $listdata['total'] = $nlist['total'];
    $listdata['order'] = $nlist['order'];
    $listdata['direction'] = $nlist['direction'];
}

if ($api) {
    header('Content-Type: application/json');
    echo json_encode(array_values($nlist));
} else {
    $pagination = LMSPaginationFactory::getPagination($page, $total, $limit, ConfigHelper::checkConfig('phpui.short_pagescroller'));

    $listdata['total'] = $total;
    $listdata['order'] = $nlist['order'];
    $listdata['direction'] = $nlist['direction'];

    unset($nlist['total'], $nlist['order'], $nlist['direction']);

    $total = $listdata['total'];

    $SESSION->save('nnlp', $page);

    $SESSION->add_history_entry();

    $SMARTY->assign('nlist', $nlist);
    $SMARTY->assign('pagination', $pagination);
    $SMARTY->assign('listdata', $listdata);
    $SMARTY->assign('divisions', $LMS->GetDivisions());
    $SMARTY->assign('NNprojects', $LMS->GetProjects());

    $SMARTY->display('netnode/netnodelist.html');
}
