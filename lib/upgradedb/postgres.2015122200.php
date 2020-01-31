<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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

function parse_address_2015122200($address)
{
    $address = trim($address);
    if (!($res = preg_match('/^(?<street>.+)\s+(?<building>[0-9][0-9a-z-]*(?:\/[0-9][0-9a-z]*)?)(?:\s+|\s*(?:\/|m\.?|lok\.?)\s*)(?<apartment>[0-9a-z]+)$/i', $address, $m))) {
        if (!($res = preg_match('/^(?<street>.+)\s+(?<building>[0-9][0-9a-z-]*)$/i', $address, $m))) {
            $res = preg_match('/^(?<street>.+)$/i', $address, $m);
            if (!$res) {
                return null;
            }
        }
    }
    $res = array();
    foreach ($m as $key => $value) {
        if (is_string($key)) {
            $res[$key] = $value;
        }
    }
    return $res;
}

$this->BeginTrans();

$this->Execute("
	DROP VIEW customersview;
	DROP VIEW contractorview;
	ALTER TABLE customers ADD COLUMN street varchar(255) DEFAULT '' NOT NULL;
	ALTER TABLE customers ADD COLUMN building varchar(20) DEFAULT NULL;
	ALTER TABLE customers ADD COLUMN apartment varchar(20) DEFAULT NULL;
	ALTER TABLE customers ADD COLUMN post_street varchar(255) DEFAULT NULL;
	ALTER TABLE customers ADD COLUMN post_building varchar(20) DEFAULT NULL;
	ALTER TABLE customers ADD COLUMN post_apartment varchar(20) DEFAULT NULL;
");

$customers = $this->GetAll("SELECT id, address, post_address FROM customers");
if (!empty($customers)) {
    foreach ($customers as $customer) {
        $args = array();
        if (!empty($customer['address'])) {
            $address = parse_address_2015122200($customer['address']);
            if (!empty($address)) {
                foreach (array('street', 'building', 'apartment') as $idx) {
                    if (array_key_exists($idx, $address)) {
                        $args[$idx] = $address[$idx];
                    }
                }
            } else {
                $args['street'] = $customer['address'];
            }
        }
        if (!empty($customer['post_address'])) {
            $address = parse_address_2015122200($customer['post_address']);
            if (!empty($address)) {
                foreach (array('street', 'building', 'apartment') as $idx) {
                    if (array_key_exists($idx, $address)) {
                        $args['post_' . $idx] = $address[$idx];
                    }
                }
            } else {
                $args['post_street'] = $customer['post_address'];
            }
        }
        if (!empty($args)) {
            $this->Execute(
                "UPDATE customers SET " . implode(' = ?, ', array_keys($args)) . " = ? WHERE id = ?",
                array_merge($args, array('id' => $customer['id']))
            );
        }
    }
}

$this->Execute("
	ALTER TABLE customers DROP COLUMN address;
	ALTER TABLE customers DROP COLUMN post_address;
	CREATE VIEW customerview AS
		SELECT c.*,
			(CASE WHEN building IS NULL THEN street ELSE (CASE WHEN apartment IS NULL THEN " . $this->Concat('street', "' '", 'building') . "
				ELSE " . $this->Concat('street', "' '", 'building', "'/'", 'apartment') . " END) END) AS address,
			(CASE WHEN post_building IS NULL THEN post_street ELSE (CASE WHEN post_apartment IS NULL THEN " . $this->Concat('post_street', "' '", 'post_building') . "
				ELSE " . $this->Concat('post_street', "' '", 'post_building', "'/'", 'post_apartment') . " END) END) AS post_address
		FROM customers c
		WHERE NOT EXISTS (
				SELECT 1 FROM customerassignments a
				JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
				WHERE e.userid = lms_current_user() AND a.customerid = c.id)
			AND c.type < 2;
	CREATE VIEW contractorview AS
		SELECT c.*,
			(CASE WHEN building IS NULL THEN street ELSE (CASE WHEN apartment IS NULL THEN " . $this->Concat('street', "' '", 'building') . "
				ELSE " . $this->Concat('street', "' '", 'building', "'/'", 'apartment') . " END) END) AS address,
			(CASE WHEN post_building IS NULL THEN post_street ELSE (CASE WHEN post_apartment IS NULL THEN " . $this->Concat('post_street', "' '", 'post_building') . "
				ELSE " . $this->Concat('post_street', "' '", 'post_building', "'/'", 'post_apartment') . " END) END) AS post_address
		FROM customers c
		WHERE c.type = 2;
	CREATE VIEW customeraddressview AS
		SELECT c.*,
			(CASE WHEN building IS NULL THEN street ELSE (CASE WHEN apartment IS NULL THEN " . $this->Concat('street', "' '", 'building') . "
				ELSE " . $this->Concat('street', "' '", 'building', "'/'", 'apartment') . " END) END) AS address,
			(CASE WHEN post_building IS NULL THEN post_street ELSE (CASE WHEN post_apartment IS NULL THEN " . $this->Concat('post_street', "' '", 'post_building') . "
				ELSE " . $this->Concat('post_street', "' '", 'post_building', "'/'", 'post_apartment') . " END) END) AS post_address
		FROM customers c
		WHERE c.type < 2;
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2015122200', 'dbversion'));

$this->CommitTrans();
