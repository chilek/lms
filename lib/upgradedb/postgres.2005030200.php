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

$this->BeginTrans();
$this->Execute("
	CREATE INDEX nodes_netdev_idx ON nodes (netdev);
	CREATE INDEX rttickets_queueid_idx ON rttickets (queueid);
	CREATE INDEX cash_time_idx ON cash (time);
	CREATE INDEX cashimport_hash_idx ON cashimport (hash);
	CREATE INDEX invoices_cdate_idx ON invoices (cdate);
	CREATE INDEX invoicecontents_invoiceid_idx ON invoicecontents (invoiceid);
	UPDATE dbinfo SET keyvalue = '2005030200' WHERE keytype = 'dbversion'
");
$this->CommitTrans();
