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

if (!$LMS->NetworkExists($_GET['id'])||!$LMS->NetworkExists($_GET['mapto'])) {
    $SESSION->redirect('?m=netlist');
}

$network['source'] = $LMS->GetNetworkRecord($_GET['id'], $SESSION->get('ntlp'.$_GET['id'], 1024));
$network['dest'] = $LMS->GetNetworkRecord($_GET['mapto']);

if ($network['source']['assigned'] > $network['dest']['free']) {
    $error['remap'] = true;
}

if (!$error) {
    if ($_GET['is_sure']) {
        //$LMS->NetworkRemap($network['source']['id'], $network['dest']['id']);
        $result = $LMS->MoveHostsBetweenNetworks($network['source']['id'], $network['dest']['id']);
        if (is_array($result)) {
            $SMARTY->display('header.html');
            foreach ($result as $error) {
                echo $error . '<br>';
            }
            $SMARTY->display('footer.html');
            die;
        }
        $SESSION->redirect('?m=netinfo&id='.$network['dest']['id']);
    } else {
        $layout['pagetitle'] = trans('Readdressing Network $a', strtoupper($network['source']['name']));
        $SMARTY->display('header.html');
        echo '<H1>'.$layout['pagetitle'].'</H1>';
        echo '<P>'.trans('Are you sure, you want to readdress network $a to network $b ?', strtoupper($network['source']['name']).' ('.$network['source']['address'].'/'.$network['source']['prefix'].')', strtoupper($network['dest']['name']).' ('.$network['dest']['address'].'/'.$network['dest']['prefix'].')').'</P>';
        echo '<A href="?m=netremap&id='.$_GET['id'].'&mapto='.$_GET['mapto'].'&is_sure=1">'.trans('Yes, I am sure.').'</A>';
        $SMARTY->display('footer.html');
    }
} else {
    $networks = $LMS->GetNetworks();
    $SMARTY->assign('network', $network['source']);
    $SMARTY->assign('networks', $networks);
    $SMARTY->assign('error', $error);
    $SMARTY->display('net/netinfo.html');
}
