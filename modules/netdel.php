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

if (!$LMS->NetworkExists($_GET['id'])) {
    $SESSION->redirect('?m=netlist');
}

$network = $LMS->GetNetworkRecord($_GET['id']);

if ($network['assigned']) {
    $error['delete'] = true;
}

if (!$error) {
    if ($_GET['is_sure']) {
        $LMS->NetworkDelete($network['id']);
        $SESSION->redirect('?m='.$SESSION->get('lastmodule').'&id='.$_GET['id']);
    } else {
        $layout['pagetitle'] = trans('Removing network $a', strtoupper($network['name']));
        $SMARTY->display('header.html');
        echo '<H1>'.$layout['pagetitle'].'</H1>';
        echo '<P>'.trans('Are you sure, you want to delete that network?').'</P>';
        echo '<A href="?m=netdel&id='.$network['id'].'&is_sure=1">'.trans('Yes, I am sure.').'</A>';
        $SMARTY->display('footer.html');
    }
} else {
    $layout['pagetitle'] = trans('Info Network: $a', $network['name']);
    $SMARTY->assign('network', $network);
    $SMARTY->assign('networks', $LMS->GetNetworks());
    $SMARTY->assign('error', $error);
    $SMARTY->display('net/netinfo.html');
}
