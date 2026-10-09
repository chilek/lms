<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2026 LMS Developers
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
 */

$this->BeginTrans();

$this->Execute("CREATE INDEX up_customers_customerid_idx ON up_customers (customerid)");
$this->Execute("CREATE INDEX customercontacts_contact_type_idx ON customercontacts (contact, type)");
$this->Execute("CREATE INDEX customerextids_extid_serviceproviderid_idx ON customerextids (extid, serviceproviderid)");
$this->Execute("CREATE INDEX up_customers_failedlogindate_idx ON up_customers (failedlogindate)");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026100600', 'dbversion'));

$this->CommitTrans();
