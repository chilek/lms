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

if (sizeof($_POST['marks']))
{
	foreach($_POST['marks'] as $markid => $junk)
		if ($junk)
			$ids[] = $markid;

	foreach($ids as $idx => $invoiceid)
		$DB->Execute('UPDATE documents SET closed = 
				    (CASE closed WHEN 0 THEN 1 ELSE 0 END)
				WHERE id = ?', array($invoiceid));
}

header('Location: ?'.$SESSION->get('backto'));

?>
