<?php

/*
 * LMS version 1.3-cvs
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

$invoiceid = $_GET['id'];
if ($invoicecontent = $LMS->GetInvoiceContent($invoiceid))
{
	$invoice = $LMS->DB->GetRow('SELECT customerid FROM invoices WHERE id=?', array($invoiceid));
	foreach($invoicecontent['content'] as $idx => $row)
	{
		$addbalance['type'] = 3;
		$addbalance['value'] = $row['value'] * $row['count'];
		$addbalance['taxvalue'] = $row['taxvalue'];
		$addbalance['userid'] = $invoice['customerid'];
		$addbalance['comment'] = $row['description'];
		$addbalance['invoiceid'] = $invoiceid;
		$LMS->AddBalance($addbalance);
	}
}

header("Location: ?".$_SESSION['backto']);

?>
