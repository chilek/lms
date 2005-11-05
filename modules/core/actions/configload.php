<?php

/*
 * LMS version 1.7-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

$DB->BeginTrans();

foreach($CONFIG['phpui'] as $key => $val)
{
	$DB->Execute('INSERT INTO uiconfig(section, var, value) VALUES(?,?,?)',
			array('phpui', $key, $val)
			);
}

if($CONFIG['userpanel']) foreach($CONFIG['userpanel'] as $key => $val)
{
	$DB->Execute('INSERT INTO uiconfig(section, var, value) VALUES(?,?,?)',
			array('userpanel', $key, $val)
			);
}

/*
foreach($CONFIG['directories'] as $key => $val)
{
	$DB->Execute('INSERT INTO uiconfig(section, var, value) VALUES(?,?,?)',
			array('directories', $key, $val)
			);
}
*/

foreach($CONFIG['invoices'] as $key => $val)
{
	$DB->Execute('INSERT INTO uiconfig(section, var, value) VALUES(?,?,?)',
			array('invoices', $key, $val)
			);
}

foreach($CONFIG['receipts'] as $key => $val)
{
	$DB->Execute('INSERT INTO uiconfig(section, var, value) VALUES(?,?,?)',
			array('receipts', $key, $val)
			);
}

foreach($CONFIG['finances'] as $key => $val)
{
	$DB->Execute('INSERT INTO uiconfig(section, var, value) VALUES(?,?,?)',
			array('finances', $key, $val)
			);
}

$DB->CommitTrans();

header('Location: ?m=configlist');

?>
