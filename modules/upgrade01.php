<?php

/*
 * LMS version 1.2-cvs
 *
 *  (C) Copyright 2001-2004 LMS Developers
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

$SMARTY->display('header.html');
echo '<PRE><B>'._('Database upgrade: finances format change:').'</B>';

$users = $DB->GetAll('SELECT * FROM users');
$tariffs = $DB->GetAllByKey('SELECT * FROM tariffs','id');
foreach($users as $idx => $row)
{
	echo $row['lastname']." ".$row['name'].": ";
	echo _("tariff: '").$tariffs[$row['tariff']]['name']."'... ";
	$LMS->AddAssignment(array('tariffid' => $row['tariff'], 'at' => $row['payday'], 'userid' => $row['id'], 'period' => 1, 'invoice' => 0));
	echo "ok.\n";
}

?>