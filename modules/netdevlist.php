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

if ($api) {
    $search = array();
    $order = 'id,asc';
} else {
    $layout['pagetitle'] = trans('Network Devices');

    if (!isset($_GET['o'])) {
        $SESSION->restore('ndlo', $o);
    } else {
        $o = $_GET['o'];
    }
    $SESSION->save('ndlo', $o);

    if (!isset($_GET['s'])) {
        if ($SESSION->is_set('ndfs')) {
            $SESSION->restore('ndfs', $s);
        } else {
            $s = -1;
        }
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

    if (!isset($_GET['n'])) {
        $SESSION->restore('ndfn', $n);
    } else {
        $n = $_GET['n'];
    }
    $SESSION->save('ndfn', $n);

    if (!isset($_GET['type'])) {
        $SESSION->restore('ndftype', $type);
    } else {
        $type = $_GET['type'];
    }
    $SESSION->save('ndftype', $type);

    if (!isset($_GET['producer'])) {
        $SESSION->restore('ndfproducer', $producer);
    } else {
        $producer = $_GET['producer'];
    }
    $SESSION->save('ndfproducer', $producer);

    if (!isset($_GET['model'])) {
        $SESSION->restore('ndfmodel', $model);
    } else {
        $model = $_GET['model'];
    }
    $SESSION->save('ndfmodel', $model);

    if (empty($model)) {
        $model = -1;
    }
    if (empty($producer)) {
        $producer = -1;
    }

    $producers = $DB->GetCol("SELECT DISTINCT UPPER(TRIM(producer)) AS producer FROM netdevices WHERE producer <> '' ORDER BY producer");
    $models = $DB->GetCol("SELECT DISTINCT UPPER(TRIM(model)) AS model FROM netdevices WHERE model <> ''"
        . ($producer != '-1' ? " AND UPPER(TRIM(producer)) = " . $DB->Escape($producer == '-2' ? '' : $producer) : '') . " ORDER BY model");
    if (!preg_match('/^-[0-9]+$/', $model) && !in_array($model, $models)) {
        $SESSION->save('ndfmodel', '-1');
        $SESSION->redirect('?' . preg_replace('/&model=[^&]+/', '', $_SERVER['QUERY_STRING']));
    }
    if (!preg_match('/^-[0-9]+$/', $producer) && !in_array($producer, $producers)) {
        $SESSION->save('ndfproducer', '-1');
        $SESSION->redirect('?' . preg_replace('/&producer=[^&]+/', '', $_SERVER['QUERY_STRING']));
    }

    $search = array(
        'status' => $s,
        'project' => $p,
        'netnode' => $n,
        'type' => $type,
        'producer' => $producer,
        'model' => $model,
        'count' => true,
    );

    $total = intval($LMS->GetNetDevList($o, $search));

    $limit = intval(ConfigHelper::getConfig('phpui.nodelist_pagelimit', $total));
    if ($SESSION->is_set('ndlp') && !isset($_GET['page'])) {
        $SESSION->restore('ndlp', $_GET['page']);
    }
    $page = !isset($_GET['page']) ? 1 : intval($_GET['page']);
    $offset = ($page - 1) * $limit;

    $search['count'] = false;
    $search['offset'] = $offset;
    $search['limit'] = $limit;
}

$netdevlist = $LMS->GetNetDevList($o, $search);

if (!$api) {
    $pagination = LMSPaginationFactory::getPagination($page, $total, $limit, ConfigHelper::checkConfig('phpui.short_pagescroller'));

    $listdata['total'] = $total;
    $listdata['order'] = $netdevlist['order'];
    $listdata['direction'] = $netdevlist['direction'];
    $listdata['status'] = $s;
    $listdata['invprojectid'] = $p;
    $listdata['netnode'] = $n;
    $listdata['type'] = $type;
    $listdata['producer'] = $producer;
    $listdata['model'] = $model;
}

unset($netdevlist['total']);
unset($netdevlist['order']);
unset($netdevlist['direction']);

if ($api) {
    header('Content-Type: application/json');
    echo json_encode(array_values($netdevlist));
    die;
}

$SESSION->save('ndlp', $page);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$netnodes = $LMS->GetNetNodeList(array(), 'name,ASC');
unset($netnodes['total'], $netnodes['order'], $netnodes['direction']);
$SMARTY->assign('netnodes', $netnodes);

$hook_data = $LMS->executeHook(
    'netdevlist_before_display',
    array(
        'netdevlist' => $netdevlist,
        'smarty' => $SMARTY,
    )
);
$netdevlist = $hook_data['netdevlist'];

$SMARTY->assign('netdevlist', $netdevlist);
$SMARTY->assign('pagination', $pagination);
$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('NNprojects', $LMS->GetProjects());
$SMARTY->assign('producers', $producers);
$SMARTY->assign('models', $models);
$SMARTY->display('netdev/netdevlist.html');
