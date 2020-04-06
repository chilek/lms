<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

define('CCONSENT_DATE', 1);
define('CCONSENT_INVOICENOTICE', 2);
define('CCONSENT_MAILINGNOTICE', 3);
define('CCONSENT_EINVOICE', 4);

$this->BeginTrans();

$this->Execute("
    CREATE TABLE customerconsents (
        customerid integer NOT NULL
            CONSTRAINT customerconsents_customerid_fkey REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE,
        cdate integer DEFAULT 0 NOT NULL,
        type smallint NOT NULL,    
        CONSTRAINT customerconsents_ukey UNIQUE (customerid, type)
    );
    CREATE INDEX customerconsents_cdate_idx ON customerconsents (cdate);
    CREATE INDEX customerconsents_type_idx ON customerconsents (type);
");

$consents = $this->GetAll('SELECT id AS customerid, consentdate, einvoice, mailingnotice, invoicenotice FROM customers');
if (!empty($consents)) {
    $records = array();
    foreach ($consents as $consent) {
        foreach (array('consentdate', 'einvoice', 'mailingnotce', 'invoicenotice') as $type) {
            if (!empty($consent[$type])) {
                $records[] = '(' . $consent['customerid'] . ',' . ($type == 'consentdate' ? $consent['consentdate'] : 0) . ',' . CCONSENT_DATE . ')';
            }
        }
    }
    if (!empty($records)) {
        $this->Execute("INSERT INTO customerconsents (customerid, cdate, type) VALUES " . implode(',', $records));
    }
}

$this->Execute("
    DROP VIEW customeraddressview;
    DROP VIEW contractorview;
    DROP VIEW customerview
");

$this->Execute("
    ALTER TABLE customers DROP COLUMN consentdate;
    ALTER TABLE customers DROP COLUMN einvoice;
    ALTER TABLE customers DROP COLUMN mailingnotice;
    ALTER TABLE customers DROP COLUMN invoicenotice;    
");

$this->Execute("
    CREATE VIEW customerview AS
        SELECT c.*,
            (CASE WHEN cc1.type IS NULL THEN 0 ELSE cc1.cdate END) AS consentdate, 
            (CASE WHEN cc2.type IS NULL THEN 0 ELSE 1 END) AS invoicenotice, 
            (CASE WHEN cc3.type IS NULL THEN 0 ELSE 1 END) AS mailingnotice, 
            (CASE WHEN cc4.type IS NULL THEN 0 ELSE 1 END) AS einvoice, 
            a1.country_id as countryid, a1.zip as zip, a1.city as city,
            a1.street as street,a1.house as building, a1.flat as apartment,
            a2.country_id as post_countryid, a2.zip as post_zip,
            a2.city as post_city, a2.street as post_street, a2.name as post_name,
            a2.house as post_building, a2.flat as post_apartment,
            a1.address as address, a1.location AS full_address,
            a1.postoffice AS postoffice,
            a2.address as post_address, a2.location AS post_full_address,
            a2.postoffice AS post_postoffice
        FROM customers c
            JOIN customer_addresses ca1 ON c.id = ca1.customer_id AND ca1.type = 1
            LEFT JOIN vaddresses a1 ON ca1.address_id = a1.id
            LEFT JOIN customer_addresses ca2 ON c.id = ca2.customer_id AND ca2.type = 0
            LEFT JOIN vaddresses a2 ON ca2.address_id = a2.id
            LEFT JOIN customerconsents cc1 ON cc1.customerid = c.id AND cc1.type = 1
            LEFT JOIN customerconsents cc2 ON cc2.customerid = c.id AND cc2.type = 2
            LEFT JOIN customerconsents cc3 ON cc3.customerid = c.id AND cc3.type = 3
            LEFT JOIN customerconsents cc4 ON cc4.customerid = c.id AND cc4.type = 4
        WHERE NOT EXISTS (
            SELECT 1 FROM customerassignments a
            JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
            WHERE e.userid = lms_current_user() AND a.customerid = c.id)
            AND c.type < 2;
    
    CREATE VIEW contractorview AS
        SELECT c.*,
            (CASE WHEN cc1.type IS NULL THEN 0 ELSE cc1.cdate END) AS consentdate, 
            (CASE WHEN cc2.type IS NULL THEN 0 ELSE 1 END) AS invoicenotice, 
            (CASE WHEN cc3.type IS NULL THEN 0 ELSE 1 END) AS mailingnotice, 
            (CASE WHEN cc4.type IS NULL THEN 0 ELSE 1 END) AS einvoice, 
            a1.country_id as countryid, a1.zip as zip, a1.city as city, a1.street as street,
            a1.house as building, a1.flat as apartment, a2.country_id as post_countryid,
            a2.zip as post_zip, a2.city as post_city, a2.street as post_street,
            a2.house as post_building, a2.flat as post_apartment, a2.name as post_name,
            a1.address as address, a1.location AS full_address,
            a1.postoffice AS postoffice,
            a2.address as post_address, a2.location AS post_full_address,
            a2.postoffice AS post_postoffice
        FROM customers c
            JOIN customer_addresses ca1 ON c.id = ca1.customer_id AND ca1.type = 1
            LEFT JOIN vaddresses a1 ON ca1.address_id = a1.id
            LEFT JOIN customer_addresses ca2 ON c.id = ca2.customer_id AND ca2.type = 0
            LEFT JOIN vaddresses a2 ON ca2.address_id = a2.id
            LEFT JOIN customerconsents cc1 ON cc1.customerid = c.id AND cc1.type = 1
            LEFT JOIN customerconsents cc2 ON cc2.customerid = c.id AND cc2.type = 2
            LEFT JOIN customerconsents cc3 ON cc3.customerid = c.id AND cc3.type = 3
            LEFT JOIN customerconsents cc4 ON cc4.customerid = c.id AND cc4.type = 4
        WHERE c.type = 2;
    
    CREATE VIEW customeraddressview AS
        SELECT c.*,
            (CASE WHEN cc1.type IS NULL THEN 0 ELSE cc1.cdate END) AS consentdate, 
            (CASE WHEN cc2.type IS NULL THEN 0 ELSE 1 END) AS invoicenotice, 
            (CASE WHEN cc3.type IS NULL THEN 0 ELSE 1 END) AS mailingnotice, 
            (CASE WHEN cc4.type IS NULL THEN 0 ELSE 1 END) AS einvoice, 
            a1.country_id as countryid, a1.zip as zip, a1.city as city, a1.street as street,
            a1.house as building, a1.flat as apartment, a2.country_id as post_countryid,
            a2.zip as post_zip, a2.city as post_city, a2.street as post_street,
            a2.house as post_building, a2.flat as post_apartment, a2.name as post_name,
            a1.address as address, a1.location AS full_address,
            a1.postoffice AS postoffice,
            a2.address as post_address, a2.location AS post_full_address,
            a2.postoffice AS post_postoffice
        FROM customers c
            JOIN customer_addresses ca1 ON c.id = ca1.customer_id AND ca1.type = 1
            LEFT JOIN vaddresses a1 ON ca1.address_id = a1.id
            LEFT JOIN customer_addresses ca2 ON c.id = ca2.customer_id AND ca2.type = 0
            LEFT JOIN vaddresses a2 ON ca2.address_id = a2.id
            LEFT JOIN customerconsents cc1 ON cc1.customerid = c.id AND cc1.type = 1
            LEFT JOIN customerconsents cc2 ON cc2.customerid = c.id AND cc2.type = 2
            LEFT JOIN customerconsents cc3 ON cc3.customerid = c.id AND cc3.type = 3
            LEFT JOIN customerconsents cc4 ON cc4.customerid = c.id AND cc4.type = 4
        WHERE c.type < 2;
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2020040600', 'dbversion'));

$this->CommitTrans();
