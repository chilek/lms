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

if (isset($_POST['tarifftagadd'])) {
    $tarifftagadd = $_POST['tarifftagadd'];

    foreach ($tarifftagadd as $key => $value) {
        $tarifftagadd[$key] = trim($value);
    }

    if ($tarifftagadd['name'] == '' && $tarifftagadd['description'] == '') {
        $SESSION->redirect('?m=tarifftaglist');
    }

    if ($tarifftagadd['name'] == '') {
        $error['name'] = trans('Tag name required!');
    } elseif (strlen($tarifftagadd['tag']) > 255) {
        $error['name'] = trans('Tag name is too long!');
    } elseif (!preg_match('/^[._a-z0-9-]+$/i', $tarifftagadd['name'])) {
        $error['name'] = trans('Invalid chars in tag name!');
    } elseif ($LMS->TarifftagGetId($tarifftagadd['tag'])) {
        $error['name'] = trans('Tag with name $a already exists!', $tarifftagadd['name']);
    }

    if (!$error) {
        $SESSION->redirect('?m=tarifftaglist&id=' . $LMS->TarifftagAdd($tarifftagadd));
    }

    $SMARTY->assign('error', $error);
    $SMARTY->assign('tarifftagadd', $tarifftagadd);
}

$layout['pagetitle'] = trans('New tag');
$SMARTY->display('tariff/tarifftagadd.html');
