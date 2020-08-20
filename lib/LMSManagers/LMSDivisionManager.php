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
    public function GetDivision($id)
    {
        return $this->db->GetRow('SELECT * FROM vdivisions WHERE id = ?', array($id));
    }

    public function GetDivisionByName($name)
    {
        return $this->db->GetRow('SELECT * FROM vdivisions WHERE shortname = ?', array($name));
    }

    public function GetDivisions($params = array())
    {
        extract($params);

        if (isset($order) && is_null($order)) {
            $order = 'shortname,asc';
        }

        list($order, $direction) = sscanf($order, '%[^,],%s');

        ($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

        switch ($order) {
            case 'name':
                $sqlord = ' ORDER BY name';
                break;
            default:
                $sqlord = ' ORDER BY shortname';
                break;
        }

        return $this->db->GetAllByKey(
            'SELECT vd.*' . (isset($userid) ? ', vd.id as divisionid ' : '') . 'FROM vdivisions vd'
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
        if (isset($params['offset'])) {
            $offset = $params['offset'];
        } else {
            $offset = null;
        }
        if (isset($params['limit'])) {
            $limit = $params['limit'];
        } else {
            $limit = null;
        }

        $user_divisions = implode(',', array_keys($this->GetDivisions(array('userid' => Auth::GetCurrentUser()))));

        return $this->db->GetAll('
            SELECT d.id, d.name, d.shortname, d.status, (SELECT COUNT(*) FROM customers WHERE divisionid = d.id) AS cnt 
            FROM divisions d'
            . (isset($params['superuser']) && empty($params['superuser']) ? ' WHERE id IN (' . $user_divisions . ')' : '') .
            ' ORDER BY d.shortname'
            . (isset($limit) ? ' LIMIT ' . $limit : '')
            . (isset($offset) ? ' OFFSET ' . $offset : '')
        );
    }

    public function AddDivision($division)
    {
        $lm = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);
        $address_id = $lm->InsertAddress($division);

        $args = array(
            'name'            => $division['name'],
            'shortname'       => $division['shortname'],
            'ten'             => $division['ten'],
            'regon'           => $division['regon'],
            'rbe'             => $division['rbe'],
            'rbename'         => $division['rbename'] ? $division['rbename'] : '',
            'telecomnumber'   => $division['telecomnumber'] ? $division['telecomnumber'] : '',
            'bank'            => empty($division['bank']) ? null : $division['bank'],
            'account'         => $division['account'],
            'inv_header'      => $division['inv_header'],
            'inv_footer'      => $division['inv_footer'],
            'inv_author'      => $division['inv_author'],
            'inv_cplace'      => $division['inv_cplace'],
            'inv_paytime'     => $division['inv_paytime'],
            'inv_paytype'     => $division['inv_paytype'] ? $division['inv_paytype'] : null,
            'email'           => empty($division['email']) ? null : $division['email'],
            'description'     => $division['description'],
            'tax_office_code' => $division['tax_office_code'],
            'address_id'      => ($address_id >= 0 ? $address_id : null)
        );

        $this->db->Execute('INSERT INTO divisions (name, shortname,
			ten, regon, rbe, rbename, telecomnumber, bank, account, inv_header, inv_footer, inv_author,
			inv_cplace, inv_paytime, inv_paytype, email, description, tax_office_code, address_id)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));

        $divisionid = $this->db->GetLastInsertID('divisions');

        if ($divisionid && isset($division['users'])) {
            foreach ($division['users'] as $userid) {
                $this->db->Execute('INSERT INTO userdivisions (userid, divisionid) VALUES(?, ?)', array($userid, $divisionid));
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

            $this->db->Execute('DELETE FROM addresses a
				WHERE a.id = (SELECT address_id FROM divisions d WHERE d.id = ?)', array($id));
            $this->db->Execute('DELETE FROM divisions WHERE id=?', array($id));
        }
    }

    public function UpdateDivision($division)
    {
        $args = array(
            'name'        => $division['name'],
            'shortname'   => $division['shortname'],
            'ten'         => $division['ten'],
            'regon'       => $division['regon'],
            'rbe'         => $division['rbe'] ? $division['rbe'] : '',
            'rbename'     => $division['rbename'] ? $division['rbename'] : '',
            'telecomnumber'     => $division['telecomnumber'] ? $division['telecomnumber'] : '',
            'bank'            => empty($division['bank']) ? null : $division['bank'],
            'account'     => $division['account'],
            'inv_header'  => $division['inv_header'],
            'inv_footer'  => $division['inv_footer'],
            'inv_author'  => $division['inv_author'],
            'inv_cplace'  => $division['inv_cplace'],
            'inv_paytime' => $division['inv_paytime'],
            'inv_paytype' => $division['inv_paytype'] ? $division['inv_paytype'] : null,
            'email'           => empty($division['email']) ? null : $division['email'],
            'description' => $division['description'],
            'status'      => !empty($division['status']) ? 1 : 0,
            'tax_office_code' => $division['tax_office_code'],
            SYSLOG::RES_DIV   => $division['id']
        );

        $this->db->Execute('UPDATE divisions SET name=?, shortname=?,
			ten=?, regon=?, rbe=?, rbename=?, telecomnumber=?, bank=?, account=?, inv_header=?,
			inv_footer=?, inv_author=?, inv_cplace=?, inv_paytime=?,
			inv_paytype=?, email=?, description=?, status=?, tax_office_code = ?
			WHERE id=?', array_values($args));

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

        $lm = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);
        $lm->UpdateAddress($division);

        if ($this->syslog) {
            $args['added_users'] = implode(',', $division['diff_users_add']);
            $args['removed_users'] = implode(',', $division['diff_users_del']);
            $this->syslog->AddMessage(SYSLOG::RES_DIV, SYSLOG::OPER_UPDATE, $args);
        }
    }

    public function CheckDivisionsAccess($divisions)
    {
        $user_divisions = $this->GetDivisions(array('userid' => Auth::GetCurrentUser()));

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
    }
}
