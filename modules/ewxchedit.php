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

if (!empty($_GET['action'])) {
    if ($_GET['action'] == 'remove') {
        $DB->Execute(
            'UPDATE netdevices SET channelid = NULL WHERE id = ? AND channelid = ?',
            array($_GET['devid'], $_GET['id'])
        );
    } else if ($_GET['action'] == 'add' && !empty($_POST['devid'])) {
        $DB->Execute(
            'UPDATE netdevices SET channelid = ? WHERE id = ?',
            array($_GET['id'], $_POST['devid'])
        );
    }

        $SESSION->redirect('?'.$SESSION->get('backto'));
}

if (!($channel = $DB->GetRow('SELECT * FROM ewx_channels WHERE id = ?', array($_GET['id'])))) {
        $SESSION->redirect('?m=ewxchlist');
}

$layout['pagetitle'] = trans('Channel Edit: $a', $channel['name']);

if (isset($_POST['channel'])) {
    $channel = $_POST['channel'];

    foreach ($channel as $key => $value) {
        $channel[$key] = trim($value);
    }

    $channel['id'] = $_GET['id'];

    if ($channel['name'] == '') {
        $error['name'] = trans('Channel name is required!');
    } else if (mb_strlen($channel['name']) > 32) {
        $error['name'] = trans('Channel name too long!');
    }

    if ($channel['upceil'] == '') {
            $channel['upceil'] = trans('This field must contain number greater than 8!');
    } else if (!preg_match('/^[0-9]+$/', $channel['upceil'])) {
            $error['upceil'] = trans('Integer value expected!');
    } else if ($channel['upceil'] < 8) {
            $error['upceil'] = trans('This field must contain number greater than 8!');
    }

    if ($channel['downceil'] == '') {
            $channel['downceil'] = trans('This field must contain number greater than 8!');
    } else if (!preg_match('/^[0-9]+$/', $channel['downceil'])) {
            $error['downceil'] = trans('Integer value expected!');
    } else if ($channel['downceil'] < 8) {
            $error['downceil'] = trans('This field must contain number greater than 8!');
    }

    if ($channel['upceil_n']) {
        if (!preg_match('/^[0-9]+$/', $channel['upceil_n'])) {
            $error['upceil_n'] = trans('Integer value expected!');
        } else if ($channel['upceil_n'] < 8) {
            $error['upceil_n'] = trans('This field must contain number greater than 8!');
        }
    }

    if ($channel['downceil_n']) {
        if (!preg_match('/^[0-9]+$/', $channel['downceil_n'])) {
                $error['downceil_n'] = trans('Integer value expected!');
        } else if ($channel['downceil_n'] < 8) {
                $error['downceil_n'] = trans('This field must contain number greater than 8!');
        }
    }

    if (!$error) {
        $DB->Execute(
            'UPDATE ewx_channels SET name=?, upceil=?, downceil=?,
			upceil_n=?, downceil_n=?, halfduplex=? WHERE id=?',
            array($channel['name'],
                    $channel['upceil'],
                    $channel['downceil'],
                    !empty($channel['upceil_n']) ? $channel['upceil_n'] : null,
                    !empty($channel['downceil_n']) ? $channel['downceil_n'] : null,
                    !empty($channel['halfduplex']) ? 1 : null,
                $channel['id'],
            )
        );

        $SESSION->redirect('?m=ewxchinfo&id='.$channel['id']);
    }

    $SMARTY->assign('error', $error);
}

$SMARTY->assign('channel', $channel);
$SMARTY->display('ewxch/ewxchedit.html');
