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

$indexes = array(
    'cash_importid_idx',
    'ksefinvoices_ksef_number_idx',
    'cash_docid_idx',
    'invoicecontents_docid_idx',
    'cashimport_sourceid_idx',
    'customercontacts_contact_idx',
    'netdevicemacs_netdevid_idx',
    'userdivisions_userid_idx',
    'voip_cdr_direction_idx',
);

foreach ($indexes as $index) {
    if ($this->ResourceExists($index, LMSDB::RESOURCE_TYPE_INDEX)) {
        $this->Execute("DROP INDEX " . $index);
    }
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026112702', 'dbversion'));

$this->CommitTrans();
