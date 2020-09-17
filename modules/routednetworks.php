<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

switch ($_GET['action']) {
    case 'add':
        $LMS->addNodeRoutedNetworks(array(
            'nodeid' => $_POST['nodeid'],
            'networks' => $_POST['routednetworkid'],
            'comment' => $_POST['routednetworkcomment'],
        ));
        break;

    case 'delete':
        if (isset($_GET['netid'])) {
            $networks = array($_GET['netid']);
        } elseif (isset($_POST['routednetwork']) && is_array($_POST['routednetwork'])) {
            $networks = $_POST['routednetwork'];
        }
        $LMS->deleteNodeRoutedNetworks(array(
            'nodeid' => $_POST['nodeid'],
            'networks' => $networks
        ));
        break;
}

$SESSION->redirect('?' . $SESSION->get('backto'));
