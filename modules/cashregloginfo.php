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

$reglog = $DB->GetRow(
    'SELECT l.*, vusers.name AS username
			FROM cashreglog l
			LEFT JOIN vusers ON (l.userid = vusers.id)
			WHERE l.id = ?',
    array(intval($_GET['id']))
);

if (!$reglog) {
        $SESSION->redirect('?m=cashreglist');
}

if (!$DB->GetOne('SELECT rights FROM cashrights WHERE userid=? AND regid=?', array(Auth::GetCurrentUser(), $reglog['regid']))) {
        $SMARTY->display('noaccess.html');
        $SESSION->close();
        die;
}

$reglog['time'] = strftime('%Y/%m/%d %H:%M', $reglog['time']);

$layout['pagetitle'] = trans('Cash History Entry Info');

$SMARTY->assign('reglog', $reglog);
$SMARTY->display('cash/cashregloginfo.html');
