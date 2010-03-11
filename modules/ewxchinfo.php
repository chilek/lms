<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2010 LMS Developers
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

if(!($channel = $DB->GetRow(' SELECT * FROM ewx_channels WHERE id = ?', array($_GET['id']))))
{
	$SESSION->redirect('?m=ewxchlist');
}

$layout['pagetitle'] = trans('Info Channel: $0', $channel['name']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$channel['devices'] = $DB->GetAll('SELECT id, name, location
	FROM netdevices
	WHERE channelid = ? ORDER BY name', array($channel['id']));

$channel['freedevices'] = $DB->GetAll('SELECT id, name, location, producer
	FROM netdevices
	WHERE channelid IS NULL ORDER BY name');

$SMARTY->assign('channel', $channel);
$SMARTY->display('ewxchinfo.html');

?>
