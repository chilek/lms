<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

/*!
 * \brief Function to explode address string to single fields.
 *
 * \param   string $address
 * \returns array  array with address fields
 * \returns null   can't explode string
 */
function parse_address($address, $address_contains_city = true) {
	$address = trim($address);

	$parts = array();

	$address_parts = preg_split('/,\s*/', $address);
	// if we have 3-part address then we have to search for postall address
	if (count($address_parts) == 3) {
		if (preg_match('/^[0-9]{2}-[0-9]{3}/', $address_parts[0])) {
			$postal_address = array_shift($address_parts);
		} elseif (preg_match('/^[0-9]{2}-[0-9]{3}/', $address_parts[count($address_parts) - 1])) {
			$postal_address = array_pop($address_parts);
		}
		if (preg_match('/^(?<zip>[0-9]{2}-[0-9]{3})\s+(?<postalcity>.+)$/', $postal_address, $m)) {
			$parts['zip'] = $m['zip'];
			$parts['postalcity'] = $m['postalcity'];
		} else
			$parts['postalcity'] = $postal_address;
	}

	// if we have (or still) 2-part address then we have to search for city
	if (count($address_parts) == 2) {
		$parts['city'] = array_shift($address_parts);
	}

	// search for street, house and flat parts
	if (!($res = preg_match('/^(?<street>.+)\s+(?<house>[0-9][0-9a-z]*(?:\/[0-9][0-9a-z]*)?)(?:\s+|\s*(?:\/|m\.?|lok\.?)\s*)(?<flat>[0-9a-z]+)$/i', $address_parts[0], $m)))
		if (!($res = preg_match('/^(?<street>.+)\s+(?<house>[0-9][0-9a-z]*)$/i', $address_parts[0], $m))) {
			$res = preg_match('/^(?<street>.+)$/i', $address_parts[0], $m);
			if (!$res)
				return null;
		}

	$m = array_filter($m, 'strlen');
	$m = array_filter($m, 'is_string');

	foreach ($m as $k => $v) {
		if (is_numeric($k)) {
			unset($m[$k]);
		}
	}

	$parts = array_merge($parts, $m);
	if ($address_contains_city && !isset($parts['city'])) {
		$parts['city'] = $parts['street'];
		unset($parts['street']);
	}
	return $parts;
}

function moveTableLocation( $DB, $table ) {
    $DB->Execute('ALTER TABLE ' . $table . ' ADD COLUMN address_id int(11), ADD FOREIGN KEY ' . $table . '_address_id_fk (address_id) REFERENCES addresses (id) ON DELETE SET NULL ON UPDATE CASCADE');

    $locations = $DB->GetAll('SELECT id, location_city, location_street, location_house, location_flat
                              FROM ' . $table . '
                              WHERE location_city is not null OR location_street is not null OR
                                 location_house is not null OR location_flat is not null');

    if ( $locations ) {
        foreach ($locations as $v) {
            $city   = ($v['location_city'])   ? $v['location_city']          : null;
            $street = ($v['location_street']) ? $v['location_street']        : null;
            $house  = ($v['location_house'])  ? $v['location_house'] : null;
            $flat   = ($v['location_flat'])   ? $v['location_flat']  : null;

            $DB->Execute('INSERT INTO addresses (city_id, street_id, house, flat) VALUES (?, ?, ?, ?)', array($city,$street,$house,$flat));
            $DB->Execute('UPDATE ' . $table . ' SET address_id = ? WHERE id = ?', array( $DB->GetLastInsertID('addresses'), $v['id']));
        }
    }
}

// Address types
define('POSTAL_ADDRESS'          , 0);
define('BILLING_ADDRESS'         , 1);
define('LOCATION_ADDRESS'        , 2);
define('DEFAULT_LOCATION_ADDRESS', 3);

$this->BeginTrans();

$this->Execute("CREATE TABLE addresses (
                    id         int(11) NOT NULL auto_increment,
                    name       text NULL,
                    state      varchar(64) NULL,
                    state_id   int(11) NULL,
                    city       varchar(100) NULL,
                    city_id    int(11) NULL,
                    street     varchar(255) NULL,
                    street_id  int(11) NULL,
                    zip        varchar(10) NULL,
                    country_id int(11) NULL,
                    house      varchar(20) NULL,
                    flat       varchar(20) NULL,
                    PRIMARY KEY (id),
                    CONSTRAINT addresses_state_id_fk   FOREIGN KEY (state_id)   REFERENCES location_states  (id) ON DELETE SET NULL ON UPDATE CASCADE,
                    CONSTRAINT addresses_city_id_fk    FOREIGN KEY (city_id)    REFERENCES location_cities  (id) ON DELETE SET NULL ON UPDATE CASCADE,
                    CONSTRAINT addresses_street_id_fk  FOREIGN KEY (street_id)  REFERENCES location_streets (id) ON DELETE SET NULL ON UPDATE CASCADE,
                    CONSTRAINT addresses_country_id_fk FOREIGN KEY (country_id) REFERENCES countries        (id) ON DELETE SET NULL ON UPDATE CASCADE
                ) ENGINE=InnoDB;");

$this->Execute("CREATE TABLE customer_addresses (
                    id          int(11) NOT NULL auto_increment,
                    customer_id int(11),
                    address_id  int(11),
                    type        smallint NULL,
                    PRIMARY KEY (id),
                    CONSTRAINT customer_addresses_customer_id_fk FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT customer_addresses_address_id_fk  FOREIGN KEY (address_id)  REFERENCES addresses (id) ON DELETE CASCADE ON UPDATE CASCADE,
                    UNIQUE(customer_id, address_id)
                ) ENGINE=InnoDB;");


/* --------------------------------
    NODES
 -------------------------------- */
$this->Execute('ALTER TABLE nodes ADD COLUMN address_id integer NULL');
$this->Execute('ALTER TABLE nodes ADD CONSTRAINT nodes_address_id_fkey FOREIGN KEY (address_id) REFERENCES addresses (id) ON DELETE SET NULL ON UPDATE CASCADE');

$locations = $this->GetAll('SELECT id, location, location_city, location_street, location_house, location_flat, ownerid
                            FROM nodes
                            WHERE (location is not null OR char_length(location) > 0) OR
                                location_city   is not null OR
                                location_street is not null OR
                                location_house  is not null OR
                                location_flat   is not null');

$customer_nodes = array();

if ( $locations ) {
    foreach ($locations as $v) {
        $city   = ($v['location_city'])   ? $v['location_city']          : null;
        $street = ($v['location_street']) ? $v['location_street']        : null;
        $house  = ($v['location_house'])  ? $v['location_house'] : null;
        $flat   = ($v['location_flat'])   ? $v['location_flat']  : null;
        $loc    = parse_address( $v['location'] );

        if ( $city == null && $street == null && $house == null && $flat == null && !$v['location'] ) {
            continue;
        }

        $tmp = strtolower( ((!empty($loc['city'])) ? $loc['city'] : '') . "|$city|" . ((!empty($loc['street'])) ? $loc['street'] : $v['location']) . "|$street|$house|$flat" );

        if ( $v['ownerid'] != 0 && isset($ADDRESSES[$v['ownerid']][$tmp]) ) {
            $addr_id = $ADDRESSES[$v['ownerid']][$tmp];
        } else {
			if (isset($loc['zip']))
				$args = array(
					'zip' => $loc['zip'],
				);
			else
				$args = array();
			$args = array_merge($args, array(
				'city' => isset($loc['city']) ? $loc['city'] : '',
				'city_id'=> $city == null ? null : $city,
				'street' => isset($loc['street']) ? $loc['street'] : (isset($loc['city']) ? '' : $v['location']),
				'street_id' => $street == null ? null : $street,
				'house' => $house,
				'flat' => $flat,
			));
			$this->Execute('INSERT INTO addresses (' . implode(', ', array_keys($args)) . ') VALUES
				(' . implode(', ', array_fill(0, count(array_keys($args)), '?')) . ')', array_values($args));

            $addr_id = $this->GetLastInsertID('addresses');

            if ( $v['ownerid'] != 0 ) {
                $ADDRESSES[$v['ownerid']][$tmp] = $addr_id;

                if ( isset($customer_nodes[ $v['ownerid'] ]) ) {
                    $type = LOCATION_ADDRESS;
                } else {
                    $customer_nodes[ $v['ownerid'] ] = 1;
                    $type = DEFAULT_LOCATION_ADDRESS;
                }

                $this->Execute('INSERT INTO customer_addresses (customer_id, address_id, type) VALUES (?,?,?)', array($v['ownerid'], $addr_id, $type));
            }
        }

        $this->Execute('UPDATE nodes SET address_id = ? WHERE id = ?', array( $addr_id, $v['id']));
    }
}

/* --------------------------------
    NETNODES
 -------------------------------- */
$this->Execute('ALTER TABLE netnodes ADD COLUMN address_id integer NULL');
$this->Execute('ALTER TABLE netnodes ADD CONSTRAINT netnodes_address_id_fkey FOREIGN KEY (address_id) REFERENCES addresses (id) ON DELETE SET NULL ON UPDATE CASCADE');

$locations = $this->GetAll('SELECT id, location, location_city, location_street, location_house, location_flat
                            FROM netnodes
                            WHERE (location is not null AND char_length(location) > 0) OR
                                  location_city   is not null OR
                                  location_street is not null OR
                                  location_house  is not null OR
                                  location_flat   is not null');

if ( $locations ) {
    foreach ($locations as $v) {
        $city   = ($v['location_city'])   ? $v['location_city']          : null;
        $street = ($v['location_street']) ? $v['location_street']        : null;
        $house  = ($v['location_house'])  ? $v['location_house'] : null;
        $flat   = ($v['location_flat'])   ? $v['location_flat']  : null;
        $loc    = parse_address( $v['location'] );

		if (isset($loc['zip']))
			$args = array(
				'zip' => $loc['zip'],
			);
		else
			$args = array();
		$args = array_merge($args, array(
			'city' => isset($loc['city']) ? $loc['city'] : '',
			'city_id'=> $city,
			'street' => isset($loc['street']) ? $loc['street'] : (isset($loc['city']) ? '' : $v['location']),
			'street_id' => $street,
			'house' => $house,
			'flat' => $flat,
		));
		$this->Execute('INSERT INTO addresses (' . implode(', ', array_keys($args)) . ') VALUES
			(' . implode(', ', array_fill(0, count(array_keys($args)), '?')) . ')', array_values($args));

        $this->Execute('UPDATE netnodes SET address_id = ? WHERE id = ?', array( $this->GetLastInsertID('addresses'), $v['id']));
    }
}

/* --------------------------------
    NETDEVICES
 -------------------------------- */
$this->Execute('ALTER TABLE netdevices ADD COLUMN address_id integer NULL');
$this->Execute('ALTER TABLE netdevices ADD CONSTRAINT netdevices_address_id_fk FOREIGN KEY (address_id) REFERENCES addresses (id) ON DELETE SET NULL ON UPDATE CASCADE');

$locations = $this->GetAll('SELECT id, location, location_city, location_street, location_house, location_flat, ownerid
                            FROM netdevices
                            WHERE (location is not null AND char_length(location) > 0) OR
                                  location_city   is not null OR
                                  location_street is not null OR
                                  location_house  is not null OR
                                  location_flat   is not null');

if ( $locations ) {
    foreach ($locations as $v) {
        $city   = ($v['location_city'])   ? $v['location_city']          : null;
        $street = ($v['location_street']) ? $v['location_street']        : null;
        $house  = ($v['location_house'])  ? $v['location_house'] : null;
        $flat   = ($v['location_flat'])   ? $v['location_flat']  : null;
        $loc    = parse_address( $v['location'] );

        $tmp = strtolower( ((!empty($loc['city'])) ? $loc['city'] : '') . "|$city|" . ((!empty($loc['street'])) ? $loc['street'] : $v['location']) . "|$street|$house|$flat" );

        if ( $v['ownerid'] != 0 && isset($ADDRESSES[$v['ownerid']][$tmp]) ) {
            $addr_id = $ADDRESSES[$v['ownerid']][$tmp];
        } else {
			if (isset($loc['zip']))
				$args = array(
					'zip' => $loc['zip'],
				);
			else
				$args = array();
			$args = array_merge($args, array(
				'city' => isset($loc['city']) ? $loc['city'] : '',
				'city_id'=> $city,
				'street' => isset($loc['street']) ? $loc['street'] : (isset($loc['city']) ? '' : $v['location']),
				'street_id' => $street,
				'house' => $house,
				'flat' => $flat,
			));
			$this->Execute('INSERT INTO addresses (' . implode(', ', array_keys($args)) . ') VALUES
				(' . implode(', ', array_fill(0, count(array_keys($args)), '?')) . ')', array_values($args));

            $addr_id = $this->GetLastInsertID('addresses');

            if ( $v['ownerid'] != 0 ) {
                $ADDRESSES[$v['ownerid']][$tmp] = $addr_id;

                if ( isset($customer_nodes[ $v['ownerid'] ]) ) {
                    $type = LOCATION_ADDRESS;
                } else {
                    $customer_nodes[ $v['ownerid'] ] = 1;
                    $type = DEFAULT_LOCATION_ADDRESS;
                }

                $this->Execute('INSERT INTO customer_addresses (customer_id, address_id, type) VALUES (?,?,?)', array($v['ownerid'], $addr_id, $type));
            }
        }

        $this->Execute('UPDATE netdevices SET address_id = ? WHERE id = ?', array( $addr_id, $v['id']));
    }
}

/* --------------------------------
    DIVISIONS
 -------------------------------- */
$this->Execute('ALTER TABLE divisions ADD COLUMN address_id integer NULL');
$this->Execute('ALTER TABLE divisions ADD CONSTRAINT divisions_address_id_fk FOREIGN KEY (address_id) REFERENCES addresses (id) ON DELETE SET NULL ON UPDATE CASCADE');

$locations = $this->GetAll('SELECT id, address, city, zip, countryid
                            FROM divisions');

if ( $locations ) {
    foreach ($locations as $v) {
        $city      = ($v['city'])      ? $v['city'] : null;
        $zip       = ($v['zip'])       ? $v['zip']  : null;
        $countryid = ($v['countryid']) ? $v['countryid']    : null;

        $loc    = parse_address( $v['address'], false );
        $street = (!empty($loc['street'])) ? $loc['street'] : $v['address'];
        $house  = (!empty($loc['house']))  ? $loc['house']  : $v['house'];
        $flat   = (!empty($loc['flat']))   ? $loc['flat']   : null;

		if (isset($loc['zip']))
			$args = array(
				'zip' => $loc['zip'],
			);
		else
			$args = array();
		$args = array_merge($args, array(
			'city' => $city,
			'street' => $street,
			'house' => $house,
			'flat' => $flat,
			'country_id' => $countryid,
		));
		$this->Execute('INSERT INTO addresses (' . implode(', ', array_keys($args)) . ') VALUES
			(' . implode(', ', array_fill(0, count(array_keys($args)), '?')) . ')', array_values($args));

        $this->Execute('UPDATE divisions SET address_id = ? WHERE id = ?', array( $this->GetLastInsertID('addresses'), $v['id']));
    }
}

/* --------------------------------
    VOIPACCOUNTS
 -------------------------------- */
$this->Execute('ALTER TABLE voipaccounts ADD COLUMN address_id integer NULL');
$this->Execute('ALTER TABLE voipaccounts ADD CONSTRAINT voipaccounts_address_id_fk FOREIGN KEY (address_id) REFERENCES addresses (id) ON DELETE SET NULL ON UPDATE CASCADE');

$locations = $this->GetAll('SELECT id, location, location_city, location_street, location_house, location_flat, ownerid
                            FROM voipaccounts
                            WHERE (location is not null AND char_length(location) > 0) OR
                                  location_city   is not null OR
                                  location_street is not null OR
                                  location_house  is not null OR
                                  location_flat   is not null');

if ( $locations ) {
    foreach ($locations as $v) {
        $city   = ($v['location_city'])   ? $v['location_city']          : null;
        $street = ($v['location_street']) ? $v['location_street']        : null;
        $house  = ($v['location_house'])  ? $v['location_house'] : null;
        $flat   = ($v['location_flat'])   ? $v['location_flat']  : null;
        $loc    = parse_address( $v['location'] );

        $tmp = strtolower( ((!empty($loc['city'])) ? $loc['city'] : '') . "|$city|" . ((!empty($loc['street'])) ? $loc['street'] : $v['location']) . "|$street|$house|$flat" );

        if ( $v['ownerid'] != 0 && isset($ADDRESSES[$v['ownerid']][$tmp]) ) {
            $addr_id = $ADDRESSES[$v['ownerid']][$tmp];
        } else {
			if (isset($loc['zip']))
				$args = array(
					'zip' => $loc['zip'],
				);
			else
				$args = array();
			$args = array_merge($args, array(
				'city' => isset($loc['city']) ? $loc['city'] : '',
				'city_id'=> $city,
				'street' => isset($loc['street']) ? $loc['street'] : (isset($loc['city']) ? '' : $v['location']),
				'street_id' => $street,
				'house' => $house,
				'flat' => $flat,
			));
			$this->Execute('INSERT INTO addresses (' . implode(', ', array_keys($args)) . ') VALUES
				(' . implode(', ', array_fill(0, count(array_keys($args)), '?')) . ')', array_values($args));

            $addr_id = $this->GetLastInsertID('addresses');

            if ( $v['ownerid'] != 0 ) {
                $ADDRESSES[$v['ownerid']][$tmp] = $addr_id;

                if ( isset($customer_nodes[ $v['ownerid'] ]) ) {
                    $type = LOCATION_ADDRESS;
                } else {
                    $customer_nodes[ $v['ownerid'] ] = 1;
                    $type = DEFAULT_LOCATION_ADDRESS;
                }

                $this->Execute('INSERT INTO customer_addresses (customer_id, address_id, type) VALUES (?,?,?)', array($v['ownerid'], $addr_id, $type));
            }
        }

        $this->Execute('UPDATE voipaccounts SET address_id = ? WHERE id = ?', array( $addr_id, $v['id']));
    }
}



/* --------------------------------
    CUSTOMERS
 -------------------------------- */


$customers_loc = $this->GetAll('SELECT id, zip, city, building, street, apartment, countryid,
                                   post_name, post_street, post_building, post_apartment, post_zip,
                                   post_city, post_countryid
                                FROM customers');

if ( $customers_loc ) {
    foreach ($customers_loc as $v) {
        /* --- POSTAL ADDRESS --- */
        $any_to_up = false;

        if ( $v['post_name'] ) {
            $post_name = "'".$v['post_name']."'";
            $any_to_up = true;
        } else {
            $post_name = 'null';
        }

        if ( $v['post_street'] ) {
            $post_street = "'".$v['post_street']."'";
            $any_to_up = true;
        } else {
            $post_street = 'null';
        }

        if ( $v['post_building'] ) {
            $post_building = "'".$v['post_building']."'";
            $any_to_up = true;
        } else {
            $post_building = 'null';
        }

        if ( $v['post_apartment'] ) {
            $post_apartment = "'".$v['post_apartment']."'";
            $any_to_up = true;
        } else {
            $post_apartment = 'null';
        }

        if ( $v['post_zip'] ) {
            $post_zip = "'".$v['post_zip']."'";
            $any_to_up = true;
        } else {
            $post_zip = 'null';
        }

        if ( $v['post_city'] ) {
            $post_city = "'".$v['post_city']."'";
            $any_to_up = true;
        } else {
            $post_city = 'null';
        }

        if ( $v['post_countryid'] ) {
            $post_countryid = $v['post_countryid'];
            $any_to_up = true;
        } else {
            $post_countryid = 'null';
        }

        if ( $any_to_up === true ) {
            $this->Execute('INSERT INTO addresses (name, city, street, zip, country_id, house, flat)
                            VALUES (' . "$post_name,$post_city,$post_street,$post_zip,$post_countryid,$post_building,$post_apartment" . ')');

            $this->Execute('INSERT INTO customer_addresses (customer_id,address_id, type) VALUES (?,?,?)', array($v['id'], $this->GetLastInsertID('addresses'), POSTAL_ADDRESS));
        }

        /* --- BILLING ADDRESS --- */
        $any_to_up = false;

        if ( $v['street'] ) {
            $street = "'".$v['street']."'";
            $any_to_up = true;
        } else {
            $street = 'null';
        }

        if ( $v['building'] ) {
            $building = "'".$v['building']."'";
            $any_to_up = true;
        } else {
            $building = 'null';
        }

        if ( $v['apartment'] ) {
            $apartment = "'".$v['apartment']."'";
            $any_to_up = true;
        } else {
            $apartment = 'null';
        }

        if ( $v['zip'] ) {
            $zip = "'".$v['zip']."'";
            $any_to_up = true;
        } else {
            $zip = 'null';
        }

        if ( $v['city'] ) {
            $city = "'".$v['city']."'";
            $any_to_up = true;
        } else {
            $city = 'null';
        }

        if ( $v['countryid'] ) {
            $countryid = $v['countryid'];
            $any_to_up = true;
        } else {
            $countryid = 'null';
        }

        if ( $any_to_up === true ) {
            $this->Execute('INSERT INTO addresses (city, street, zip, country_id, house, flat)
                            VALUES (' . "$city,$street,$zip,$countryid,$building,$apartment" . ')');

            $address_id = $this->GetLastInsertID('addresses');

            $this->Execute('INSERT INTO customer_addresses (customer_id,address_id,type) VALUES (?,?,?)', array($v['id'], $address_id, BILLING_ADDRESS));
        }
    }
}

unset( $customer_loc );

/* --------------------------------
    REWRITE VIEWS AND TABLES WHO USING OLD LOCATION FIELDS
 -------------------------------- */
$this->Execute("ALTER TABLE nodes DROP FOREIGN KEY nodes_ibfk_1;");
$this->Execute("ALTER TABLE nodes DROP FOREIGN KEY nodes_ibfk_2;");
$this->Execute("ALTER TABLE nodes DROP FOREIGN KEY nodes_ibfk_3;");
$this->Execute("ALTER TABLE nodes DROP FOREIGN KEY nodes_ibfk_4;");
$this->Execute("ALTER TABLE netdevices DROP FOREIGN KEY netdevices_ibfk_1;");
$this->Execute("ALTER TABLE netdevices DROP FOREIGN KEY netdevices_ibfk_2;");
$this->Execute("ALTER TABLE netdevices DROP FOREIGN KEY netdevices_ibfk_3;");
$this->Execute("ALTER TABLE netdevices DROP FOREIGN KEY netdevices_ibfk_4;");
$this->Execute("ALTER TABLE netdevices DROP FOREIGN KEY netdevices_ibfk_5;");
$this->Execute("ALTER TABLE netnodes DROP FOREIGN KEY netnodes_ibfk_2;");
$this->Execute("ALTER TABLE netnodes DROP FOREIGN KEY netnodes_ibfk_3;");
$this->Execute("ALTER TABLE voipaccounts DROP FOREIGN KEY voipaccounts_ibfk_1;");
$this->Execute("ALTER TABLE voipaccounts DROP FOREIGN KEY voipaccounts_ibfk_2;");
$this->Execute("ALTER TABLE divisions DROP FOREIGN KEY divisions_ibfk_1;");
$this->Execute("ALTER TABLE divisions DROP FOREIGN KEY divisions_ibfk_2;");

$this->Execute("DROP INDEX location_city   ON nodes;");
$this->Execute("DROP INDEX location_street ON nodes;");
$this->Execute("DROP INDEX location_street ON netdevices;");
$this->Execute("DROP INDEX location_city   ON netdevices;");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2017013100', 'dbversion'));

$this->CommitTrans();

?>
