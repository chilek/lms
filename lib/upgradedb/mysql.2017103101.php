<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

// Document types
define('DOC_INVOICE', 1);
define('DOC_CNOTE', 3);
define('DOC_DNOTE', 5);
define('DOC_INVOICE_PRO', 6);

define('POSTAL_ADDRESS', 0);

$this->BeginTrans();

$this->Execute("ALTER TABLE documents ADD COLUMN post_address_id int(11)");
$this->Execute("ALTER TABLE documents ADD CONSTRAINT documents_post_address_id_fkey
	FOREIGN KEY (post_address_id) REFERENCES addresses (id) ON DELETE SET NULL ON UPDATE CASCADE");

$post_addresses = $this->GetAllByKey("SELECT address_id, customer_id FROM customer_addresses
	WHERE type = ?", 'customer_id', array(POSTAL_ADDRESS));
$documents = $this->GetAllByKey("SELECT id, customerid FROM documents
	WHERE type IN (?, ?, ?, ?)", 'id', array(DOC_INVOICE, DOC_CNOTE, DOC_DNOTE, DOC_INVOICE_PRO));

if (!empty($post_addresses) && !empty($documents)) {
    $location_manager = new LMSLocationManager($this);
    foreach ($documents as $docid => $document) {
        if (isset($post_addresses[$document['customerid']])) {
            $post_address_id = $location_manager->CopyAddress($post_addresses[$document['customerid']]['address_id']);
            if (!empty($post_address_id)) {
                $this->Execute(
                    "UPDATE documents SET post_address_id = ? WHERE id = ?",
                    array($post_address_id, $docid)
                );
            }
        }
    }
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2017103101', 'dbversion'));

$this->CommitTrans();
