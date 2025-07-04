<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2020 LMS Developers
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
 * LMSDivisionManager
 *
 */
class LMSDivisionManager extends LMSManager implements LMSDivisionManagerInterface
{
    private static $currentDivision = null;

    public static function setCurrentDivision($division)
    {
        self::$currentDivision = $division;
    }

    public static function getCurrentDivision()
    {
        return self::$currentDivision;
    }

    public function GetDivision($id)
    {
        return $this->db->GetRow(
            'SELECT *
            FROM vdivisions
            WHERE id = ?',
            array($id)
        );
    }

    public function GetDivisionByName($name)
    {
        return $this->db->GetRow('SELECT * FROM vdivisions WHERE shortname = ?', array($name));
    }

    public function getDivisionIdByShortName($shortname)
    {
        return $this->db->GetOne('SELECT id FROM divisions WHERE UPPER(shortname) = UPPER(?)', array($shortname));
    }

    public function GetDivisions($params = array())
    {
        extract($params);

        if (empty($order)) {
            $order = 'shortname,asc';
        }

        [$order, $direction] = sscanf($order, '%[^,],%s');

        ($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

        switch ($order) {
            case 'name':
                $sqlord = ' ORDER BY name';
                break;
            default:
                $sqlord = ' ORDER BY (CASE WHEN vd.label IS NULL THEN vd.shortname ELSE vd.label END)';
                break;
        }

        return $this->db->GetAllByKey(
            'SELECT vd.*,  (CASE WHEN vd.label IS NULL THEN vd.shortname ELSE vd.label END) AS label'
            . (isset($userid) ? ', vd.id as divisionid' : '') . ' FROM vdivisions vd'
            . (isset($userid) ? ' JOIN userdivisions ud ON vd.id = ud.divisionid' : '') .
            ' WHERE 1=1'
            . (isset($status) ? ' AND vd.status = ' . intval($status) : '')
            . (isset($userid) ? ' AND ud.userid = ' . intval($userid) : '')
            . (isset($divisionid) ? ' AND vd.id = ' . intval($divisionid) : '')
            . ($sqlord != '' ? $sqlord . ' ' . $direction : ''),
            'id'
        );
    }

    public function getDivisionList($params = array())
    {
        extract($params);

        if (!isset($offset)) {
            $offset = null;
        }
        if (!isset($limit)) {
            $limit = null;
        }

        $user_divisions = implode(',', array_keys($this->GetDivisions(array('userid' => Auth::GetCurrentUser()))));

        return $this->db->GetAll(
            'SELECT d.id, d.name, d.shortname, (CASE WHEN d.label IS NULL THEN d.shortname ELSE d.label END) AS label,
                d.status, (SELECT COUNT(*) FROM customers WHERE divisionid = d.id) AS cnt,
                d.firstname, d.lastname, d.birthdate, d.naturalperson,
                kd.token AS kseftoken
            FROM vdivisions d
            LEFT JOIN ksefdivisions kd ON kd.divisionid = d.id
            WHERE 1 = 1'
            . ((isset($superuser) && empty($superuser)) || !isset($superuser) ? ' AND id IN (' . $user_divisions . ')' : '')
            . (!empty($exludedDivisions) ? ' AND id NOT IN (' . $exludedDivisions . ')' : '') .
            ' ORDER BY (CASE WHEN d.label IS NULL THEN d.shortname ELSE d.label END)'
            . (isset($limit) ? ' LIMIT ' . $limit : '')
            . (isset($offset) ? ' OFFSET ' . $offset : '')
        );
    }

    public function AddDivision($division)
    {
        $lm = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);
        $address_id = $lm->InsertAddress($division);
        $office_address_id = $lm->InsertAddress($division['office_address']);

        $args = array(
            'name'            => $division['name'],
            'shortname'       => $division['shortname'],
            'label'           => empty($division['label']) ? null : $division['label'],
            'firstname'       => empty($division['firstname']) || empty($division['lastname']) || empty($division['birthdate']) ? null : $division['firstname'],
            'lastname'        => empty($division['firstname']) || empty($division['lastname']) || empty($division['birthdate']) ? null : $division['lastname'],
            'birthdate'       => empty($division['firstname']) || empty($division['lastname']) || empty($division['birthdate']) ? null : $division['birthdate'],
            'ten'             => $division['ten'],
            'regon'           => $division['regon'],
            'rbe'             => $division['rbe'],
            'rbename'         => $division['rbename'] ?: '',
            'telecomnumber'   => $division['telecomnumber'] ?: '',
            'bank'            => empty($division['bank']) ? null : $division['bank'],
            'account'         => $division['account'],
            'inv_header'      => $division['inv_header'],
            'inv_footer'      => $division['inv_footer'],
            'inv_author'      => $division['inv_author'],
            'inv_cplace'      => $division['inv_cplace'],
            'inv_paytime'     => $division['inv_paytime'],
            'inv_paytype'     => $division['inv_paytype'] ?: null,
            'email'           => empty($division['email']) ? null : $division['email'],
            'phone'           => empty($division['phone']) ? null : $division['phone'],
            'servicephone'    => empty($division['servicephone']) ? null : $division['servicephone'],
            'description'     => $division['description'],
            'tax_office_code' => $division['tax_office_code'],
            'url'             => isset($division['url']) && strlen($division['url']) ? $division['url'] : null,
            'userpanel_url'   => isset($division['userpanel_url']) && strlen($division['userpanel_url']) ? $division['userpanel_url'] : null,
            'address_id'      => $address_id > 0 ? $address_id : null,
            'office_address_id' => $office_address_id > 0 ? $office_address_id : null,
        );

        $this->db->Execute('INSERT INTO divisions (name, shortname, label, firstname, lastname, birthdate,
			ten, regon, rbe, rbename, telecomnumber, bank, account, inv_header, inv_footer, inv_author,
			inv_cplace, inv_paytime, inv_paytype, email, phone, servicephone, description, tax_office_code, url, userpanel_url, address_id, office_address_id)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));

        $divisionid = $this->db->GetLastInsertID('divisions');

        if ($divisionid) {
            if (isset($division['users'])) {
                foreach ($division['users'] as $userid) {
                    $this->db->Execute(
                        'INSERT INTO userdivisions
                        (userid, divisionid)
                        VALUES (?, ?)',
                        array(
                            $userid,
                            $divisionid,
                        )
                    );
                }
            }

            if (!empty($division['kseftoken'])) {
                $this->db->Execute(
                    'INSERT INTO ksefdivisions
                    (token, divisionid)
                    VALUES (?, ?)',
                    array(
                        strtoupper($division['kseftoken']),
                        $divisionid,
                    )
                );
            }
        }

        if ($this->syslog) {
            $args[SYSLOG::RES_DIV] = $divisionid;
            $args['added_users'] = (isset($division['users']) ? implode(',', $division['users']) : null);
            $this->syslog->AddMessage(SYSLOG::RES_DIV, SYSLOG::OPER_ADD, $args);
        }

        return $divisionid;
    }

    public function DeleteDivision($id)
    {
        if ($this->db->GetOne('SELECT COUNT(*) FROM divisions', array($id)) != 1) {
            if ($this->syslog) {
                $countryid = $this->db->GetOne('SELECT country_id FROM vdivisions
				WHERE id = ?', array($id));
                $args = array(
                    SYSLOG::RES_DIV => $id,
                    SYSLOG::RES_COUNTRY => $countryid
                );
                $this->syslog->AddMessage(SYSLOG::RES_DIV, SYSLOG::OPER_DELETE, $args);
                $assigns = $this->db->GetAll('SELECT * FROM numberplanassignments WHERE divisionid = ?', array($id));
                if (!empty($assigns)) {
                    foreach ($assigns as $assign) {
                        $args = array(
                        SYSLOG::RES_NUMPLANASSIGN => $assign['id'],
                        SYSLOG::RES_NUMPLAN => $assign['planid'],
                        SYSLOG::RES_DIV => $assign['divisionid'],
                        );
                        $this->syslog->AddMessage(SYSLOG::RES_NUMPLANASSIGN, SYSLOG::OPER_DELETE, $args);
                    }
                }
            }

            $location_manager = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);

            $address_ids = $this->db->GetRow(
                'SELECT d.address_id, d.office_address_id
                FROM divisions d
                WHERE d.id = ?',
                array($id)
            );
            foreach ($address_ids as $address_id) {
                if (!empty($address_id)) {
                    $location_manager->DeleteAddress($address_id);
                }
            }

            $this->db->Execute('DELETE FROM divisions WHERE id=?', array($id));
        }
    }

    public function UpdateDivision($division)
    {
        $lm = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);

        $lm->UpdateAddress($division);

        $office_address_id = null;
        if (!strlen($division['office_address']['location_city_name'])) {
            if (!empty($division['office_address_id'])) {
                $lm->DeleteAddress($division['office_address_id']);
            }
        } elseif (empty($division['office_address_id'])) {
            $office_address_id = $lm->InsertAddress($division['office_address']);
            if ($office_address_id <= 0) {
                $office_address_id = null;
            }
        } else {
            $lm->UpdateAddress($division['office_address']);
            $office_address_id = $division['office_address_id'];
        }

        $args = array(
            'name'        => $division['name'],
            'shortname'   => $division['shortname'],
            'label'       => empty($division['label']) ? null : $division['label'],
            'firstname'   => empty($division['firstname']) || empty($division['lastname']) || empty($division['birthdate']) ? null : $division['firstname'],
            'lastname'    => empty($division['firstname']) || empty($division['lastname']) || empty($division['birthdate']) ? null : $division['lastname'],
            'birthdate'   => empty($division['firstname']) || empty($division['lastname']) || empty($division['birthdate']) ? null : $division['birthdate'],
            'ten'         => $division['ten'],
            'regon'       => $division['regon'],
            'rbe'         => $division['rbe'] ?: '',
            'rbename'     => $division['rbename'] ?: '',
            'telecomnumber'     => $division['telecomnumber'] ?: '',
            'bank'            => empty($division['bank']) ? null : $division['bank'],
            'account'     => $division['account'],
            'inv_header'  => $division['inv_header'],
            'inv_footer'  => $division['inv_footer'],
            'inv_author'  => $division['inv_author'],
            'inv_cplace'  => $division['inv_cplace'],
            'inv_paytime' => $division['inv_paytime'],
            'inv_paytype' => $division['inv_paytype'] ?: null,
            'email'           => empty($division['email']) ? null : $division['email'],
            'phone'           => empty($division['phone']) ? null : $division['phone'],
            'servicephone'    => empty($division['servicephone']) ? null : $division['servicephone'],
            'description' => $division['description'],
            'status'      => !empty($division['status']) ? 1 : 0,
            'tax_office_code' => $division['tax_office_code'],
            'url'             => isset($division['url']) && strlen($division['url']) ? $division['url'] : null,
            'userpanel_url'   => isset($division['userpanel_url']) && strlen($division['userpanel_url']) ? $division['userpanel_url'] : null,
            'office_address_id' => $office_address_id,
            SYSLOG::RES_DIV   => $division['id']
        );

        $this->db->Execute(
            'UPDATE divisions SET name=?, shortname=?, label = ?,
                firstname = ?, lastname = ?, birthdate = ?,
                ten=?, regon=?, rbe=?, rbename=?, telecomnumber=?, bank=?, account=?, inv_header=?,
                inv_footer=?, inv_author=?, inv_cplace=?, inv_paytime=?,
                inv_paytype=?, email=?, phone = ?, servicephone = ?, description=?, status=?, tax_office_code = ?,
                url = ?, userpanel_url = ?, office_address_id = ?
            WHERE id=?',
            array_values($args)
        );

        if (!empty($division['diff_users_del'])) {
            foreach ($division['diff_users_del'] as $userdelid) {
                $this->db->Execute('DELETE FROM userdivisions WHERE userid = ? AND divisionid = ?', array($userdelid, $division['id']));
            }
        }

        if (!empty($division['diff_users_add'])) {
            foreach ($division['diff_users_add'] as $useraddid) {
                $this->db->Execute('INSERT INTO userdivisions (userid, divisionid) VALUES(?, ?)', array($useraddid, $division['id']));
            }
        }

        if ($this->db->GetOne('SELECT 1 FROM ksefdivisions WHERE divisionid = ?', array($division['id']))) {
            if (empty($division['kseftoken'])) {
                $this->db->Execute('DELETE FROM ksefdivisions WHERE divisionid = ?', array($division['id']));
            } else {
                $this->db->Execute(
                    'UPDATE ksefdivisions
                SET token  = ?
                WHERE divisionid = ?',
                    array(
                        strtoupper($division['kseftoken']),
                        $division['id'],
                    )
                );
            }
        } elseif (!empty($division['kseftoken'])) {
            $this->db->Execute(
                'INSERT INTO ksefdivisions
                (token, divisionid)
                VALUES (?, ?)',
                array(
                    strtoupper($division['kseftoken']),
                    $division['id'],
                )
            );
        }

        if ($this->syslog) {
            $args['added_users'] = implode(',', $division['diff_users_add']);
            $args['removed_users'] = implode(',', $division['diff_users_del']);
            $this->syslog->AddMessage(SYSLOG::RES_DIV, SYSLOG::OPER_UPDATE, $args);
        }
    }

    public function checkDivisionsAccess($params = array())
    {
        extract($params);
        $user_id = ($userid ?? Auth::GetCurrentUser());
        $user_divisions = $this->GetDivisions(array('userid' => $user_id));

        if (isset($divisions)) {
            if (is_array($divisions)) {
                foreach ($divisions as $division) {
                    if (!isset($user_divisions[$division])) {
                        return false;
                    }
                }
            } else {
                if (!isset($user_divisions[$divisions])) {
                    return false;
                }
            }
            return true;
        } else {
            return false;
        }
    }
}
