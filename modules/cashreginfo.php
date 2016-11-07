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


$id = intval($_GET['id']);

$registry = $DB->GetRow('SELECT reg.id AS id, reg.name AS name, reg.description AS description,
			i.template AS in_template, o.template AS out_template, disabled
			FROM cashregs reg
			LEFT JOIN numberplans i ON (in_numberplanid = i.id)
			LEFT JOIN numberplans o ON (out_numberplanid = o.id)
			WHERE reg.id=?', array($id));

if( !$registry )
{
	$SESSION->redirect('?m=cashreglist');
}

$users = $DB->GetAll('SELECT id, name FROM vusers WHERE deleted=0');
foreach($users as $user)
{
        $user['rights'] = $DB->GetOne('SELECT rights FROM cashrights WHERE userid=? AND regid=?', array($user['id'], $id));
        $registry['rights'][] = $user;
}

$layout['pagetitle'] = trans('Cash Registry Info: $a', $registry['name']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('registry', $registry);
$SMARTY->display('cash/cashreginfo.html');

?>
