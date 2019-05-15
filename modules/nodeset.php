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

$ownerid = isset($_GET['ownerid']) ? $_GET['ownerid'] : 0;
$access  = isset($_GET['access']) ? 1 : 0;
$id      = isset($_GET['id']) ? $_GET['id'] : 0;

// All customer's nodes
if ($ownerid && $LMS->CustomerExists($ownerid)) {
    $res = $LMS->NodeSetU($ownerid, $access);

    if ($res) {
        $data = array('ownerid' => $ownerid, 'access' => $access);
        $LMS->ExecHook('node_set_after', $data);

        $LMS->executeHook('nodeset_after_submit', $data);
    }

    $backid = $ownerid;
    $redir = $SESSION->get('backto');
    if ($SESSION->get('lastmodule')=='customersearch') {
        $redir .= '&search=1';
    }

    $SESSION->redirect('?'.$redir.'#'.$backid);
}

// One node
if ($id && $LMS->NodeExists($id)) {
    $res = $LMS->NodeSet($id);
    $backid = $id;

    if ($res) {
        $data = array('nodeid' => $id);
        $LMS->ExecHook('node_set_after', $data);

        $LMS->executeHook('nodeset_after_submit', $data);
    }
} else if (!empty($_POST['marks'])) {
// Selected nodes
    $nodes = array();
    foreach ($_POST['marks'] as $id) {
        if ($LMS->NodeSet($id, $access)) {
            $nodes[] = $id;
        }
    }
    if (!empty($nodes)) {
        $data = array('nodes' => $nodes);
        $LMS->ExecHook('node_set_after', $data);

        $LMS->executeHook('nodeset_after_submit', $data);
    }
}

if (!empty($_GET['shortlist'])) {
    header('Location: ?m=nodelistshort&id='.$LMS->GetNodeOwner($id));
} else {
    header('Location: ?'.$SESSION->get('backto').(isset($backid) ? '#'.$backid : ''));
}
