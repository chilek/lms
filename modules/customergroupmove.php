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

$from = !empty($_GET['from']) ? intval($_GET['from']) : 0;
$to = !empty($_GET['to']) ? intval($_GET['to']) : 0;

if($LMS->CustomergroupExists($from) && $LMS->CustomergroupExists($to) && $_GET['is_sure'] == 1)
{
	$DB->BeginTrans();
	
	$DB->Execute('INSERT INTO customerassignments (customergroupid, customerid)
			SELECT ?, customerid 
			FROM customerassignments a, customersview c
	                WHERE a.customerid = c.id AND a.customergroupid = ?
			AND NOT EXISTS (SELECT 1 FROM customerassignments ca
				WHERE ca.customerid = a.customerid AND ca.customergroupid = ?)',
			array($to, $from, $to));

	$DB->Execute('DELETE FROM customerassignments WHERE customergroupid = ?', array($from));
	
	$DB->CommitTrans();

	$SESSION->redirect('?m=customergroupinfo&id='.$to);
}
else
	header('Location: ?'.$SESSION->get('backto'));

?>
