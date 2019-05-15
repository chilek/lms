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

$nodeid = intval($_GET['id']);

if (!$LMS->NodeExists($nodeid)) {
    header('Location: ?'.$SESSION->get('backto'));
} else if ($_GET['is_sure']!=1) {
    $nodename = $LMS->GetNodeName($nodeid);

    $layout['pagetitle'] = trans('Delete Node $a', $nodename);

    $body = '<P>'.trans('Are you sure, you want to delete node $a?', $nodename).'</P>';
    $body .= '<P><A HREF="?m=nodedel&id='.$nodeid.'&is_sure=1">'.trans('Yes, I am sure.').'</A></P>';

    $SMARTY->assign('body', $body);
    $SMARTY->display('dialog.html');
} else {
    $owner = $LMS->GetNodeOwner($nodeid);

    $plugin_data = array(
        'id'        => $nodeid,
        'ownerid'   => $owner,
    );
    $LMS->ExecHook('node_del_before', $plugin_data);

    $LMS->executeHook('nodedel_before_submit', $plugin_data);

    $LMS->DeleteNode($nodeid);
    $LMS->CleanupProjects();

    $LMS->ExecHook('node_del_after', $plugin_data);

    $LMS->executeHook('nodedel_after_submit', $plugin_data);

    if ($SESSION->is_set('backto')) {
        header('Location: ?'.$SESSION->get('backto'));
    } else {
        header('Location: ?m=customerinfo&id='.$owner);
    }
}
