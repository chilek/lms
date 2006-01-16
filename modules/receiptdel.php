<?php

/*
 * LMS version 1.9-cvs
 *
 *  (C) Copyright 2001-2006 LMS Developers
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

if($id && $_GET['is_sure']=='1')
{
	$regid = $DB->GetOne('SELECT DISTINCT regid FROM receiptcontents WHERE docid=?', array($id));
	if($DB->GetOne('SELECT rights FROM cashrights WHERE userid=? AND regid=?', array($AUTH->id, $regid)) < 3)
	{
	        $SMARTY->display('noaccess.html');
	        $SESSION->close();
	        die;
	}

	if($DB->Execute('DELETE FROM documents WHERE id = ?', array($id)))
	{	
		if($DB->Execute('DELETE FROM receiptcontents WHERE docid = ?', array($id)))
		{
			$LMS->SetTS('receiptcontents');
		}
		if($DB->Execute('DELETE FROM cash WHERE docid = ?', array($id)))
		{
			$LMS->SetTS('cash');
		}
		$LMS->SetTS('documents');
	}
}

header('Location: ?m=receiptlist');

?>
