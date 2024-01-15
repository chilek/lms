<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

define('POSTAL_ADDRESS_2022091900', 0);
define('BILLING_ADDRESS_2022091900', 1);

define('CTYPES_CONTRACTOR_2022091900', 2);

$this->BeginTrans();

if (!$this->ResourceExists('serviceproviders', LMSDB::RESOURCE_TYPE_TABLE)) {
    $this->Execute("CREATE SEQUENCE serviceproviders_id_seq");
    $this->Execute(
        "CREATE TABLE serviceproviders (
            id integer DEFAULT nextval('serviceproviders_id_seq') NOT NULL,
            name varchar(64) NOT NULL,
            PRIMARY KEY (id)
        )"
    );
}

if (!$this->ResourceExists('voipaccounts.extid', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("ALTER TABLE voipaccounts ADD COLUMN serviceproviderid integer DEFAULT NULL");
    $this->Execute(
        "ALTER TABLE voipaccounts ADD CONSTRAINT voipaccounts_serviceproviderid_fkey
            FOREIGN KEY (serviceproviderid) REFERENCES serviceproviders (id) ON DELETE CASCADE ON UPDATE CASCADE"
    );
    $this->Execute("ALTER TABLE voipaccounts ADD COLUMN extid varchar(64) DEFAULT NULL");
}

if (!$this->ResourceExists('customerextids', LMSDB::RESOURCE_TYPE_TABLE)) {
    $this->Execute(
        "CREATE TABLE customerextids (
            customerid integer NOT NULL
                CONSTRAINT customerextids_customerid_fkey REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE,
            extid varchar(64) NOT NULL,
            serviceproviderid integer DEFAULT NULL
                CONSTRAINT customerextids_serviceproviderid_fkey REFERENCES serviceproviders (id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT customerextids_customerid_extid_serviceproviderid_ukey UNIQUE (customerid, extid, serviceproviderid)
        )"
    );
}

if ($this->ResourceExists('customers.extid', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $customers = $this->GetAll("SELECT id, extid FROM customers WHERE extid <> ''");
    if (!empty($customers)) {
        $i = 0;
        $records = array();
        $customer_count = count($customers);
        foreach ($customers as $customer) {
            $records[] = '(' . $customer['id'] . ',' . $this->Escape($customer['extid']) . ')';
            $i++;
            $customer_count--;
            if ($i < 500 && $customer_count) {
                continue;
            }
            $this->Execute(
                "INSERT INTO customerextids (customerid, extid) VALUES " . implode(',', $records)
            );
            $i = 0;
            $records = array();
        }
    }

    $this->Execute("DROP VIEW customeraddressview");
    $this->Execute("DROP VIEW contractorview");
    $this->Execute("DROP VIEW customerview");

    $this->Execute("ALTER TABLE customers DROP COLUMN extid");

    $this->Execute(
        "CREATE VIEW customerview AS
            SELECT c.*,
                cc.consentdate AS consentdate,
                cc.invoicenotice AS invoicenotice,
                cc.mailingnotice AS mailingnotice,
                cc.smsnotice AS smsnotice,
                cc.einvoice AS einvoice,
                a1.country_id as countryid, a1.ccode,
                a1.zip as zip, a1.city as city,
                a1.street as street,a1.house as building, a1.flat as apartment,
                a2.country_id as post_countryid, a2.ccode AS post_ccode,
                a2.zip as post_zip,
                a2.city as post_city, a2.street as post_street, a2.name as post_name,
                a2.house as post_building, a2.flat as post_apartment,
                a1.address as address, a1.location AS full_address,
                a1.postoffice AS postoffice,
                a2.address as post_address, a2.location AS post_full_address,
                a2.postoffice AS post_postoffice,
                ce.extid AS extid
            FROM customers c
                JOIN customer_addresses ca1 ON c.id = ca1.customer_id AND ca1.type = ?
                LEFT JOIN vaddresses a1 ON ca1.address_id = a1.id
                LEFT JOIN customer_addresses ca2 ON c.id = ca2.customer_id AND ca2.type = ?
                LEFT JOIN vaddresses a2 ON ca2.address_id = a2.id
                LEFT JOIN customerconsentview cc ON cc.customerid = c.id
                LEFT JOIN customerextids ce ON ce.customerid = c.id AND ce.serviceproviderid IS NULL
            WHERE NOT EXISTS (
                SELECT 1 FROM vcustomerassignments a
                JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
                WHERE e.userid = lms_current_user() AND a.customerid = c.id)
                AND (lms_current_user() = 0 OR c.divisionid IN (
                SELECT ud.divisionid
                    FROM userdivisions ud
                    WHERE ud.userid = lms_current_user()))
                AND c.type < ?
        ",
        array(
            BILLING_ADDRESS_2022091900,
            POSTAL_ADDRESS_2022091900,
            CTYPES_CONTRACTOR_2022091900,
        )
    );

    $this->Execute(
        "CREATE VIEW contractorview AS
            SELECT c.*,
                cc.consentdate AS consentdate,
                cc.invoicenotice AS invoicenotice,
                cc.mailingnotice AS mailingnotice,
                cc.smsnotice AS smsnotice,
                cc.einvoice AS einvoice,
                a1.country_id as countryid, a1.ccode,
                a1.zip as zip, a1.city as city, a1.street as street,
                a1.house as building, a1.flat as apartment,
                a2.country_id as post_countryid, a2.ccode AS post_ccode,
                a2.zip as post_zip, a2.city as post_city, a2.street as post_street,
                a2.house as post_building, a2.flat as post_apartment, a2.name as post_name,
                a1.address as address, a1.location AS full_address,
                a1.postoffice AS postoffice,
                a2.address as post_address, a2.location AS post_full_address,
                a2.postoffice AS post_postoffice,
                ce.extid AS extid
            FROM customers c
                JOIN customer_addresses ca1 ON c.id = ca1.customer_id AND ca1.type = ?
                LEFT JOIN vaddresses a1 ON ca1.address_id = a1.id
                LEFT JOIN customer_addresses ca2 ON c.id = ca2.customer_id AND ca2.type = ?
                LEFT JOIN vaddresses a2 ON ca2.address_id = a2.id
                LEFT JOIN customerconsentview cc ON cc.customerid = c.id
                LEFT JOIN customerextids ce ON ce.customerid = c.id AND ce.serviceproviderid IS NULL
            WHERE c.type = ?",
        array(
            BILLING_ADDRESS_2022091900,
            POSTAL_ADDRESS_2022091900,
            CTYPES_CONTRACTOR_2022091900,
        )
    );

    $this->Execute(
        "CREATE VIEW customeraddressview AS
            SELECT c.*,
                cc.consentdate AS consentdate,
                cc.invoicenotice AS invoicenotice,
                cc.mailingnotice AS mailingnotice,
                cc.smsnotice AS smsnotice,
                cc.einvoice AS einvoice,
                a1.country_id as countryid, a1.ccode,
                a1.zip as zip, a1.city as city, a1.street as street,
                a1.house as building, a1.flat as apartment,
                a2.country_id as post_countryid, a2.ccode AS post_ccode,
                a2.zip as post_zip, a2.city as post_city, a2.street as post_street,
                a2.house as post_building, a2.flat as post_apartment, a2.name as post_name,
                a1.address as address, a1.location AS full_address,
                a1.postoffice AS postoffice,
                a2.address as post_address, a2.location AS post_full_address,
                a2.postoffice AS post_postoffice,
                ce.extid AS extid
            FROM customers c
                JOIN customer_addresses ca1 ON c.id = ca1.customer_id AND ca1.type = ?
                LEFT JOIN vaddresses a1 ON ca1.address_id = a1.id
                LEFT JOIN customer_addresses ca2 ON c.id = ca2.customer_id AND ca2.type = ?
                LEFT JOIN vaddresses a2 ON ca2.address_id = a2.id
                LEFT JOIN customerconsentview cc ON cc.customerid = c.id
                LEFT JOIN customerextids ce ON ce.customerid = c.id AND ce.serviceproviderid IS NULL
            WHERE c.type < ?",
        array(
            BILLING_ADDRESS_2022091900,
            POSTAL_ADDRESS_2022091900,
            CTYPES_CONTRACTOR_2022091900,
        )
    );
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2022091900', 'dbversion'));

$this->CommitTrans();
