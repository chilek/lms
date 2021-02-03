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

if (!$_GET['id']) {
    $SESSION->redirect('?m=eventlist');
}

$event = $LMS->GetEvent($_GET['id']);
$userlist = $DB->GetAllByKey('SELECT id, rname FROM vusers
	WHERE deleted = 0 AND access = 1 ORDER BY lastname ASC', 'id');

$layout['pagetitle'] = trans('Event Info');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('event', $event);
$SMARTY->assign('userlist', $userlist);

$SMARTY->display('event/eventinfo.html');
