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

$layout['pagetitle'] = trans('Messages List');

if (isset($_POST['search'])) {
    $s = $_POST['search'];
} else {
    $SESSION->restore('mls', $s);
}
$SESSION->save('mls', $s);

if (isset($_POST['cat'])) {
    $c = $_POST['cat'];
} else {
    $SESSION->restore('mlc', $c);
}
$SESSION->save('mlc', $c);

if (isset($_GET['o'])) {
    $o = $_GET['o'];
} else {
    $SESSION->restore('mlo', $o);
}
$SESSION->save('mlo', $o);

if (isset($_POST['type'])) {
    $t = $_POST['type'];
} else {
    $SESSION->restore('mlt', $t);
}
$SESSION->save('mlt', $t);

if (isset($_POST['status'])) {
    $status = $_POST['status'];
} else {
    $SESSION->restore('mlst', $status);
}
$SESSION->save('mlst', $status);

if (isset($_POST['datefrom'])) {
    $datefrom = date_to_timestamp($_POST['datefrom']);
} else {
    $SESSION->restore('mldatefrom', $datefrom);
}
$SESSION->save('mldatefrom', $datefrom);

if (isset($_POST['dateto'])) {
    $dateto = date_to_timestamp($_POST['dateto']);
} else {
    $SESSION->restore('mldateto', $dateto);
}
$SESSION->save('mldateto', $dateto);

if (!empty($_GET['cid'])) {
    $s = $_GET['cid'];
    $c = 'customerid';
    $o = $t = $status = null;
}

$args = array(
    'order' => $o,
    'search' => $s,
    'cat' => $c,
    'type' => $t,
    'status' => $status,
    'datefrom' => $datefrom,
    'dateto' => empty($dateto) ? $dateto : strtotime('tomorrow', $dateto) - 1,
    'count' => true
);

if ($c == 'date') {
    $args['datefrom'] = strtotime($s);
    if ($args['datefrom'] === false) {
        $args['datefrom'] = strtotime('today');
    }
    $args['dateto'] = strtotime('tomorrow', $args['datefrom']) - 1;
}

$total = intval($LMS->GetMessageList($args));

$limit = intval(ConfigHelper::getConfig('phpui.messagelist_pagelimit', $total));
if ($SESSION->is_set('mlp') && !isset($_GET['page']) && !isset($_POST['page'])) {
    $SESSION->restore('mlp', $_GET['page']);
}
$page = intval($_GET['page'] ?? ($_POST['page'] ?? 1));
$offset = ($page - 1) * $limit;

$args['count'] = false;
$args['offset'] = $offset;
$args['limit'] = $limit;
$messagelist = $LMS->GetMessageList($args);

$pagination = LMSPaginationFactory::getPagination($page, $total, $limit, ConfigHelper::checkConfig('phpui.short_pagescroller'));

$listdata['type'] = $messagelist['type'];
$listdata['status'] = $messagelist['status'];
$listdata['order'] = $messagelist['order'];
$listdata['direction'] = $messagelist['direction'];
$listdata['cat'] = $c;
$listdata['search'] = $s;
$listdata['datefrom'] = $datefrom;
$listdata['dateto'] = $dateto;

unset($messagelist['type']);
unset($messagelist['status']);
unset($messagelist['order']);
unset($messagelist['direction']);

$listdata['total'] = $total;

$SESSION->save('mlp', $page);

$SESSION->add_history_entry();

$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('pagination', $pagination);
$SMARTY->assign('messagelist', $messagelist);
$SMARTY->display('message/messagelist.html');
