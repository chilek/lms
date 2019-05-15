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


if ($api) {
    $search = array();
    $order = null;
} else {
    $layout['pagetitle'] = trans('Network Device Nodes');

    if (!isset($_GET['o'])) {
        $SESSION->restore('ndlo', $o);
    } else {
        $o = $_GET['o'];
    }
    $SESSION->save('ndlo', $o);

    if (!isset($_GET['t'])) {
        $SESSION->restore('ndft', $t);
    } else {
        $t = $_GET['t'];
    }
    $SESSION->save('ndft', $t);

    if (!isset($_GET['s'])) {
        $SESSION->restore('ndfs', $s);
    } else {
        $s = $_GET['s'];
    }
    $SESSION->save('ndfs', $s);

    if (!isset($_GET['p'])) {
        $SESSION->restore('ndfp', $p);
    } else {
        $p = $_GET['p'];
    }
    $SESSION->save('ndfp', $p);

    if (!isset($_GET['w'])) {
        $SESSION->restore('ndfw', $w);
    } else {
        $w = $_GET['w'];
    }
    $SESSION->save('ndfw', $w);

    if (!isset($_GET['d'])) {
        $SESSION->restore('ndfd', $d);
    } else {
        $d = $_GET['d'];
    }
    $SESSION->save('ndfd', $d);

    $search = array(
        'status' => $s,
        'type' => $t,
        'invprojectid' => $p,
        'ownership' => $w,
        'divisionid' => $d,
    );
}
$nlist = $LMS->GetNetNodeList($search, $o);

if (!$api) {
    $listdata = $search;

    $listdata['total'] = $nlist['total'];
    $listdata['order'] = $nlist['order'];
    $listdata['direction'] = $nlist['direction'];
}

unset($nlist['total']);
unset($nlist['order']);
unset($nlist['direction']);

if ($api) {
    header('Content-Type: application/json');
    echo json_encode(array_values($nlist));
} else {
    if (!isset($_GET['page'])) {
        $SESSION->restore('ndlp', $_GET['page']);
    }

    $page = (!$_GET['page'] ? 1 : $_GET['page']);
    $pagelimit = ConfigHelper::getConfig('phpui.nodelist_pagelimit', $listdata['total']);
    $start = ($page - 1) * $pagelimit;

    $SESSION->save('ndlp', $page);

    $SESSION->save('backto', $_SERVER['QUERY_STRING']);

    $SMARTY->assign('page', $page);
    $SMARTY->assign('pagelimit', $pagelimit);
    $SMARTY->assign('start', $start);
    $SMARTY->assign('nlist', $nlist);
    $SMARTY->assign('listdata', $listdata);
    $SMARTY->assign('divisions', $LMS->GetDivisions());
    $SMARTY->assign('NNprojects', $LMS->GetProjects());

    $SMARTY->display('netnode/netnodelist.html');
}
