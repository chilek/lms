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

$ownerid = isset($_GET['ownerid']) ? $_GET['ownerid'] : 0;
$id = isset($_GET['id']) ? $_GET['id'] : 0;

if($ownerid && $LMS->CustomerExists($ownerid))
{
	$LMS->NodeSetU($ownerid, isset($_GET['access']) ? 1 : 0);

	$backid = $ownerid;
	$redir = $SESSION->get('backto');
	if($SESSION->get('lastmodule')=='customersearch')
		$redir .= '&search=1';

	$SESSION->redirect('?'.$redir.'#'.$backid);
}

if($id && $LMS->NodeExists($id))
{
	$LMS->NodeSet($id);
	$backid = $id;
}

if(!empty($_POST['marks']))
	foreach($_POST['marks'] as $id)
		$LMS->NodeSet($id, isset($_GET['access']) ? 1 : 0);

if(!empty($_GET['shortlist']))
{
	header('Location: ?m=nodelistshort&id='.$LMS->GetNodeOwner($id));
}
else
	header('Location: ?'.$SESSION->get('backto').(isset($backid) ? '#'.$backid : ''));

?>
