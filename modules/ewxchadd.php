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

if (isset($_POST['channel'])) {
    $channel = $_POST['channel'];
    
    foreach ($channel as $key => $value) {
        $channel[$key] = trim($value);
    }

    if ($channel['name'] == '' &&
        $channel['upceil'] == '' && $channel['upceil_n'] == '' &&
        $channel['downceil'] == '' && $channel['downceil_n'] == '') {
        header('Location: ?m=ewxchlist');
    }

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
            'INSERT INTO ewx_channels (name, upceil, downceil, upceil_n, downceil_n, halfduplex)
			VALUES (?, ?, ?, ?, ?, ?)',
            array($channel['name'],
                $channel['upceil'],
                $channel['downceil'],
                !empty($channel['upceil_n']) ? $channel['upceil_n'] : null,
                !empty($channel['downceil_n']) ? $channel['downceil_n'] : null,
                !empty($channel['halfduplex']) ? 1 : null,
            )
        );

        $id = $DB->GetLastInsertId('ewx_channels');

        $SESSION->redirect('?m=ewxchinfo&id='.$id);
    }

    $SMARTY->assign('error', $error);
    $SMARTY->assign('channel', $channel);
}

$layout['pagetitle'] = trans('New Channel');

$SMARTY->display('ewxch/ewxchadd.html');
