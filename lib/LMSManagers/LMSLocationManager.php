<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2018 LMS Developers
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

/**
 * LMSLocationManager
 *
 */
class LMSLocationManager extends LMSManager implements LMSLocationManagerInterface
{
    private static $cities_with_sections = null;

    /**
     * Inserts or updates country state
     *
     * @param string $zip Zip
     * @param int $stateid State id
     * @return null
     */
    public function UpdateCountryState($zip, $stateid)
    {
        if (empty($zip) || empty($stateid)) {
            return;
        }

        $cstate = $this->db->GetOne('SELECT stateid FROM zipcodes WHERE zip = ?', array($zip));

        $args = array(
            SYSLOG::RES_STATE => $stateid,
            'zip' => $zip
        );
        if ($cstate === null) {
            $this->db->Execute(
                'INSERT INTO zipcodes (stateid, zip) VALUES (?, ?)',
                array_values($args)
            );
            if ($this->syslog) {
                $args[SYSLOG::RES_ZIP] = $this->db->GetLastInsertID('zipcodes');
                $this->syslog->AddMessage(SYSLOG::RES_ZIP, SYSLOG::OPER_ADD, $args);
            }
        } else if ($cstate != $stateid) {
            $this->db->Execute(
                'UPDATE zipcodes SET stateid = ? WHERE zip = ?',
                array_values($args)
            );
            if ($this->syslog) {
                $args[SYSLOG::RES_ZIP] = $this->db->GetOne('SELECT id FROM zipcodes WHERE zip = ?', array($zip));
                $this->syslog->AddMessage(SYSLOG::RES_ZIP, SYSLOG::OPER_UPDATE, $args);
            }
        }
    }

    public function GetCountryStates()
    {
        return $this->db->GetAllByKey('SELECT id, name FROM states ORDER BY name', 'id');
    }

    public function GetCountries()
    {
        return $this->db->GetAllByKey('SELECT id, name FROM countries ORDER BY name', 'id');
    }

    public function GetCountryName($id)
    {
        return $this->db->GetOne('SELECT name FROM countries WHERE id = ?', array($id));
    }

    /*!
     * \brief Method delete address.
     *
     * \param int address id
     */
    public function DeleteAddress($address_id)
    {
        $this->db->Execute('DELETE FROM addresses WHERE id = ?', array($address_id));
    }

    /*!
     * \brief Method insert new address into table.
     *
     * \param  array $args associative array with parameters
     * \return int   -1    incorrect arguments
     * \return int   -2    incorrect $args values or $args fields are empty
     * \return int         new inserted address id
     */
    public function InsertAddress($args)
    {
        // if args is not array or its empty
        if (!is_array($args) || !$args) {
            return -1;
        }

        if (!empty($args['location_country_id']) && $args['location_country_id'] < 1) {
            $args['location_country_id'] = null;
        }

        if (empty($args['teryt'])) {
            $args['location_state']  = null;
            $args['location_city']   = null;
            $args['location_street'] = null;
        }

        // if any value is non empty then do insert
        if ($this->ValidAddress($args)) {
            $this->db->Execute(
                'INSERT INTO addresses
                                  (name,state,state_id,city,city_id,street,
                                  street_id,house,flat,zip,postoffice,country_id)
                                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                array(
                                    $args['location_name']        ? $args['location_name']        : null,
                                    $args['location_state_name']  ? $args['location_state_name']  : null,
                                    $args['location_state']       ? $args['location_state']       : null,
                                    $args['location_city_name']   ? $args['location_city_name']   : null,
                                    $args['location_city']        ? $args['location_city']        : null,
                                    $args['location_street_name'] ? $args['location_street_name'] : null,
                                    $args['location_street']      ? $args['location_street']      : null,
                                    $args['location_house']       ? $args['location_house']       : null,
                                    $args['location_flat']        ? $args['location_flat']        : null,
                                    $args['location_zip']         ? $args['location_zip']         : null,
                                    $args['location_postoffice']  ? $args['location_postoffice']  : null,
                $args['location_country_id']  ? $args['location_country_id']  : null)
            );

            return $this->db->GetLastInsertID('addresses');
        } else {
            return -2;
        }
    }

    /*!
     * \brief Method insert new address into table and assign it to customer.
     *
     * \param  int     $customer_id  customer id
     * \param  int     $address_type address type
     * \param  array   $args         associative array with parameters
     * \return int     -1            wrong customer_id or he's deleted
     * \return int     -2            wrong parameters
     * \return boolean true          success
     */
    public function InsertCustomerAddress($customer_id, $args)
    {
        global $LMS;

        // check if customer exists
        if ($LMS->customerExists($customer_id) !== true) {
            return -1;
        }

        $addr_id = $this->InsertAddress($args);

        // check if address params i
        if (!is_numeric($addr_id) || $addr_id < 0) {
            return -2;
        }

        // if address is LOCATION_ADDRESS and location_def_address checkbox
        // is checked then set this address as DEFAULT_LOCATION_ADDRESS
        if ($args['location_address_type'] == LOCATION_ADDRESS && isset($args['location_def_address'])) {
            $args['location_address_type'] = DEFAULT_LOCATION_ADDRESS;
        }

        // if address is DEFAULT_LOCATION_ADDRESS and location_def_address
        // checkbox isn't checked then set this address as LOCATION_ADDRESS
        if ($args['location_address_type'] == DEFAULT_LOCATION_ADDRESS && !isset($args['location_def_address'])) {
            $args['location_address_type'] = LOCATION_ADDRESS;
        }

        $this->db->Execute(
            'INSERT INTO customer_addresses (customer_id, address_id, type) VALUES (?,?,?)',
            array($customer_id, $addr_id, $args['location_address_type'])
        );

        return true;
    }

    /*!
     * \brief Method update address fields.
     *
     * \param  array   $args arguments
     * \return int     -1    incorrect arguments
     * \return int     -2    address id to update not found
     * \retrun int     -3    address passed to update was delete because contains only empty values
     * \return int     -4    empty args array
     * \return boolean true  success
     */
    public function UpdateAddress($args)
    {
        // if args is not array or its empty then exit
        if (!is_array($args) || !$args) {
            return -1;
        }

        // if address id to update isn't set then exit
        if (!isset($args['address_id'])) {
            return -2;
        }

        if (!empty($args['location_country_id']) && $args['location_country_id'] < 1) {
            $args['location_country_id'] = null;
        }

        if (empty($args['teryt'])) {
            $args['location_state']  = null;
            $args['location_city']   = null;
            $args['location_street'] = null;
        }

        // if any value is non empty then do insert
        if ($this->ValidAddress($args)) {
            $this->db->Execute(
                'UPDATE addresses SET name = ?, state = ?,
                                   state_id = ?, city = ?, city_id = ?,
                                   street = ?, street_id = ?, house = ?,
                                   flat = ?, zip = ?, postoffice = ?, country_id = ?
                                WHERE id = ?',
                array(
                                   $args['location_name']        ? $args['location_name']        : null,
                                   $args['location_state_name']  ? $args['location_state_name']  : null,
                                   $args['location_state']       ? $args['location_state']       : null,
                                   $args['location_city_name']   ? $args['location_city_name']   : null,
                                   $args['location_city']        ? $args['location_city']        : null,
                                   $args['location_street_name'] ? $args['location_street_name'] : null,
                                   $args['location_street']      ? $args['location_street']      : null,
                                   $args['location_house']       ? $args['location_house']       : null,
                                   $args['location_flat']        ? $args['location_flat']        : null,
                                   $args['location_zip']         ? $args['location_zip']         : null,
                                   $args['location_postoffice']  ? $args['location_postoffice']  : null,
                                   $args['location_country_id']  ? $args['location_country_id']  : null,
                $args['address_id'])
            );
            return true;
        } else if (isset($args['address_id'])) {
            $this->DeleteAddress($args['address_id']);
            return -3;
        } else {
            return -4;
        }
    }

    /*!
     * \brief Method update customer address into table.
     *
     * \param  int     $customer_id customer id
     * \param  array   $args        arguments
     * \return int     -1           customer not found or he's deleted
     * \return int     -2           can't update address
     * \return boolean true         success
     */
    public function UpdateCustomerAddress($customer_id, $args)
    {
        $cm = new LMSCustomerManager($this->db, $this->auth, $this->cache, $this->syslog);

        // check if customer exists
        if ($cm->customerExists($customer_id) !== true) {
            return -1;
        }

        // try update address
        if ($this->UpdateAddress($args) !== true) {
            return -2;
        }

        // if address type is LOCATION_ADDRESS and location_def_address checkbox
        // is checked then mark this address as DEFAULT_LOCATION_ADDRESS
        if ($args['location_address_type'] == LOCATION_ADDRESS && isset($args['location_def_address'])) {
            $args['location_address_type'] = DEFAULT_LOCATION_ADDRESS;
        }

        // if address type is DEFAULT_LOCATION_ADDRESS and location_def_address
        // checkbox isn't checked then mark this address as LOCATION_ADDRESS
        if ($args['location_address_type'] == DEFAULT_LOCATION_ADDRESS && !isset($args['location_def_address'])) {
            $args['location_address_type'] = LOCATION_ADDRESS;
        }

        $this->db->Execute(
            'UPDATE customer_addresses SET type = ? WHERE customer_id = ? AND address_id = ?',
            array($args['location_address_type'], $customer_id, $args['address_id'])
        );

        return true;
    }

    /*!
     * \brief Method check if address is correct.
     *
     * \param  array with address
     * \return boolean
     */
    public function ValidAddress($args)
    {
        if (!empty($args['location_country']) && $args['location_country'] < 1) {
            $args['location_country'] = null;
        }

        $tmp = array($args['location_name']     , $args['location_state_name'], $args['location_state'],
                     $args['location_city_name'], $args['location_city']      , $args['location_street_name'],
                     $args['location_street']   , $args['location_house']     , $args['location_flat'],
                     $args['location_zip']      , $args['location_postoffice'], $args['location_country_id']);

        if (array_filter($tmp)) {
            return true;
        } else {
            return false;
        }
    }

    /*!
     * \brief Method create copy of address indicated by id.
     *
     * \param  int   $id address id
     * \return int       new address id
     * \return false     address id not found
     */
    public function CopyAddress($address_id)
    {
        $addr = $this->db->GetRow('SELECT * FROM addresses WHERE id = ?', array($address_id));

        if ($addr) {
            unset($addr['id']);

            $copy_address_query = "INSERT INTO addresses (" . implode(",", array_keys($addr)) . ") VALUES (" . implode(",", array_fill(0, count($addr), '?'))  . ")";
            $this->db->Execute($copy_address_query, $addr);

            return $this->db->GetLastInsertID('addresses');
        } else {
            return false;
        }
    }

    public function GetAddress($address_id)
    {
        return $this->db->GetRow('SELECT a.*, ls.name AS state_name,
				ld.name AS district_name, lb.name AS borough_name,
				lc.name AS city_name FROM addresses a
			LEFT JOIN location_cities lc ON lc.id = a.city_id
			LEFT JOIN location_boroughs lb ON lb.id = lc.boroughid
			LEFT JOIN location_districts ld ON ld.id = lb.districtid
			LEFT JOIN location_states ls ON ls.id = ld.stateid
			WHERE a.id = ?', array($address_id));
    }

    public function GetCustomerAddress($customer_id, $type = BILLING_ADDRESS)
    {
        return $this->db->GetOne('SELECT address_id FROM customer_addresses
			WHERE customer_id = ? AND type = ?', array($customer_id, $type));
    }

    public function TerytToLocation($terc, $simc, $ulic)
    {
        $woj = substr($terc, 0, 2);
        $pow = substr($terc, 2, 2);
        $gmi = substr($terc, 4, 2);
        $rodz_gmi = $terc[6];
        $result = $this->db->GetRow(
            'SELECT ts.cityid AS location_city, ts.nazwa AS location_city_name,
				lb.id AS location_borough, lb.name AS location_borough_name,
				ld.id AS location_district, ld.name AS location_district_name,
				ls.id AS location_state, ls.name AS location_state_name
			FROM teryt_simc ts
			JOIN location_cities lc ON lc.id = ts.cityid
			JOIN location_boroughs lb ON lb.id = lc.boroughid
			JOIN location_districts ld ON ld.id = lb.districtid
			JOIN location_states ls ON ls.id = ld.stateid
			WHERE woj = ? AND pow = ? AND gmi = ? AND rodz_gmi = ? AND sym = ?',
            array($woj, $pow, $gmi, $rodz_gmi, $simc)
        );
        if (empty($result)) {
            return null;
        }
        if (empty($ulic)) {
            return compact('city');
        }
        $street = $this->db->GetRow(
            'SELECT id AS location_street, cecha, nazwa_1, nazwa_2
			FROM teryt_ulic
			WHERE woj = ? AND pow = ? AND gmi = ? AND rodz_gmi = ? AND sym = ? AND sym_ul = ?',
            array($woj, $pow, $gmi, $rodz_gmi, $simc, $ulic)
        );
        if (empty($street)) {
            return compact('result');
        } else {
            $street_parts = array_splice($street, 1, 3);
            if (empty($street_parts['nazwa_2'])) {
                unset($street_parts['nazwa_2']);
            }
            $street['location_street_name'] = implode(' ', $street_parts);
        }
        return array_merge($result, $street);
    }

    public function GetZipCode(array $params)
    {
        extract($params);

        static $street_suffixes = array(
            '/ul./', '/rondo/', '/park/', '/al./', '/pl./', '/bulw./', '/szosa/', '/inne/', '/skwer/', '/os./', '/rynek/',
            '/droga/', '/ogród/', '/wyb./', '/wyspa/', '/ul./',
        );

        $cities_with_sections = $this->GetCitiesWithSections();

        preg_match('/^(?<number>[0-9]+)(?<letter>[a-z]*)$/', strtolower($house), $m);
        $number = intval($m['number']);
        $letter = $m['letter'];
        $parity = (intval($number) & 1) ? 1 : 2;

        $from = '(fromnumber IS NULL OR (fromnumber < ' . $number . ')
			OR (fromnumber = ' . $number . ' AND (fromletter IS NULL' . (empty($letter) ? '' : ' OR fromletter <= \'' . $letter . '\'') . ')))';
        $to = '(tonumber IS NULL OR (tonumber > ' . $number . ')
			OR (tonumber = ' . $number . ' AND (toletter IS NULL' . (empty($letter) ? '' : ' OR toletter >= \'' . $letter . '\'') . ')))';

        if (isset($cityid)) {
            // teryt compatible address
            return $this->db->GetOne('SELECT zip FROM pna
				WHERE cityid = ? AND parity & ? > 0' . (isset($streetid) ? ' AND (streetid = ' . intval($streetid) . ' OR streetid IS NULL)' : '') . '
					AND ' . $from . ' AND ' . $to . '
					ORDER BY streetid ASC, fromnumber DESC, tonumber DESC LIMIT 1', array($cityid, $parity));
        } elseif (isset($city)) {
            // non-teryt address

            if (isset($cities_with_sections[mb_strtolower($city)])) {
                $boroughs = $cities_with_sections[mb_strtolower($city)]['boroughs'];
            }

            $street = trim(preg_replace($street_suffixes, array(), $street));
            $escaped_street = $this->db->Escape($street);
            $escaped_city = $this->db->Escape($city);
            return $this->db->GetOne('SELECT zip FROM pna p
				JOIN location_cities lc ON lc.id = p.cityid
				LEFT JOIN location_cities lc2 ON lc2.id = lc.cityid
				LEFT JOIN location_streets lst ON lst.id = p.streetid
				WHERE ' . (isset($boroughs) ? 'lc.boroughid IN (' . $boroughs . ')'
                        : '((p.cityid IS NOT NULL AND (CASE WHEN lc2.id IS NULL THEN lc.name ELSE '
                            . $this->db->Concat('lc.name', "' '", 'lc2.name') . ' END) = ' . $escaped_city . ')
							OR LOWER(p.cityname) = LOWER(' . $escaped_city . '))') . '
					AND parity & ? > 0' . (!empty($street) ? ' AND (lst.name = ' . $escaped_street . '
						OR (CASE WHEN lst.name2 IS NULL THEN \'\'
							ELSE ' . $this->db->Concat('lst.name', "' '", 'lst.name2') . ' END) = ' . $escaped_street . '
						OR (CASE WHEN lst.name2 IS NULL THEN \'\'
							ELSE ' . $this->db->Concat('lst.name2', "' '", 'lst.name') . ' END) = ' . $escaped_street
                        . ' OR (p.streetname IS NOT NULL AND LOWER(p.streetname) = LOWER(' . $escaped_street . ')))' : '') . '
					AND ' . $from . ' AND ' . $to . '
					ORDER BY fromnumber DESC, tonumber DESC LIMIT 1', array($parity));
        }
    }

    public function GetCitiesWithSections()
    {
        if (!is_null(self::$cities_with_sections)) {
            return self::$cities_with_sections;
        }

        self::$cities_with_sections = $this->db->GetAllByKey("SELECT lb2.cityid, LOWER(lb2.cityname) AS cityname,
				(" . $this->db->GroupConcat('lc.id', ',', true) . ") AS cities,
				(" . $this->db->GroupConcat('lc.boroughid', ',', true) . ") AS boroughs
			FROM location_boroughs lb
			JOIN location_cities lc ON lc.boroughid = lb.id
			JOIN (SELECT lb.id, lb.districtid, lc.id AS cityid, lc.name AS cityname
				FROM location_boroughs lb
				JOIN location_cities lc ON lc.boroughid = lb.id
				WHERE lb.type = 1
			) lb2 ON lb2.districtid = lb.districtid
			WHERE lb.type = 8 OR lb.type = 9
			GROUP BY lb2.cityid, LOWER(lb2.cityname)", 'cityname');

        if (empty(self::$cities_with_sections)) {
            self::$cities_with_sections = array();
        }
        return self::$cities_with_sections;
    }
}
