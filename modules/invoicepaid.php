<?php

/*
 * LMS version 1.4-cvs
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

$invoicepaydate = $_POST['invoicepaydate'];
if($invoicepaydate)
{
        // date format 'yyyy/mm/dd hh:mm'
        list($date,$time) = split(' ',$invoicepaydate);
        $date = explode('/',$date);
        $time = explode(':',$time);
        if(checkdate($date[1],$date[2],$date[0])) //je¶li z³a data, zapisujemy pod dzisiejsz±
                $invoicepaydate = mktime($time[0],$time[1],0,$date[1],$date[2],$date[0]);
        else
                unset($invoicepaydate);
}

$invoiceid = $_GET['id'];
if ($invoiceid == 'multi')
{
	if (sizeof($_POST['marks']))
	{
		foreach($_POST['marks'] as $markid => $junk)
			if ($junk)
				$ids[] = $markid;
		foreach($ids as $idx => $invoiceid)
			if (!$LMS->IsInvoicePaid($invoiceid) && $invoicecontent = $LMS->GetInvoiceContent($invoiceid))
			{
				$invoice = $LMS->DB->GetRow('SELECT customerid FROM invoices WHERE id=?', array($invoiceid));
				foreach($invoicecontent['content'] as $idx2 => $row)
				{
					$addbalance['time'] = $invoicepaydate;
					$addbalance['type'] = 3;
					$addbalance['value'] = $row['value'] * $row['count'];
					$addbalance['taxvalue'] = $row['taxvalue'];
					$addbalance['userid'] = $invoice['customerid'];
					$addbalance['comment'] = $row['description'];
					$addbalance['invoiceid'] = $invoiceid;
					$LMS->AddBalance($addbalance);
				}
			}
	}
}
elseif (!$LMS->IsInvoicePaid($invoiceid) && $invoicecontent = $LMS->GetInvoiceContent($invoiceid))
{
	foreach($invoicecontent['content'] as $idx => $row)
	{
		$addbalance['time'] = $invoicepaydate;
		$addbalance['type'] = 3;
		$addbalance['value'] = $row['value'] * $row['count'];
		$addbalance['taxvalue'] = $row['taxvalue'];
		$addbalance['userid'] = $invoicecontent['customerid'];
		$addbalance['comment'] = $row['description'];
		$addbalance['invoiceid'] = $invoiceid;
		$LMS->AddBalance($addbalance);
	}
}

header("Location: ?".$_SESSION['backto']);

?>
