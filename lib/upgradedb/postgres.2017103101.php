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
if (!defined('DOC_INVOICE')) {
    define('DOC_INVOICE', 1);
}
if (!defined('DOC_CNOTE')) {
    define('DOC_CNOTE', 3);
}
if (!defined('DOC_DNOTE')) {
    define('DOC_DNOTE', 5);
}
if (!defined('DOC_INVOICE_PRO')) {
    define('DOC_INVOICE_PRO', 6);
}

if (!defined('POSTAL_ADDRESS')) {
    define('POSTAL_ADDRESS', 0);
}
if (!defined('BILLING_ADDRESS')) {
    define('BILLING_ADDRESS', 1);
}
if (!defined('LOCATION_ADDRESS')) {
    define('LOCATION_ADDRESS', 2);
}
if (!defined('DEFAULT_LOCATION_ADDRESS')) {
    define('DEFAULT_LOCATION_ADDRESS', 3);
}
if (!defined('RECIPIENT_ADDRESS')) {
    define('RECIPIENT_ADDRESS', 4);
}


$this->Execute("ALTER TABLE documents ADD COLUMN post_address_id integer");
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
