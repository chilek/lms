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

if (isset($_GET['action'])) {
    if ($_GET['action'] == 'enable') {
        $DB->Execute("Update cashsources set deleted = 0 where id = ? ", array($_GET['id']));
    } elseif ($_GET['action'] == 'disable') {
        $DB->Execute("Update cashsources set deleted = 1 where id = ?", array($_GET['id']));
    }
    $SESSION->redirect('?m=cashsourcelist');
}


$layout['pagetitle'] = trans('Cash Import Source List');

$SESSION->add_history_entry();

$SMARTY->assign('sourcelist', $LMS->getCashSources());
$SMARTY->display('cash/cashsourcelist.html');
