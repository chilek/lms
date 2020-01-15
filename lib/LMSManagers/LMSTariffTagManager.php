<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2016 LMS Developers
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

class LMSTariffTagManager extends LMSManager implements LMSTariffTagManagerInterface
{

    public function TarifftagGetId($name)
    {
        return $this->db->GetOne('SELECT id FROM tarifftags WHERE name=?', array($name));
    }

    public function TarifftagAdd($tarifftagdata)
    {
        if ($this->db->Execute('INSERT INTO tarifftags (name, description) VALUES (?, ?)', array($tarifftagdata['name'], $tarifftagdata['description']))) {
            $id = $this->db->GetLastInsertID('tarifftags');
            if ($this->syslog) {
                $args = array(
                    SYSLOG::RES_TARIFFTAG => $id,
                    'name' => $tarifftagdata['name'],
                    'description' => $tarifftagdata['description']
                );
                $this->syslog->AddMessage(SYSLOG::RES_TARIFFTAG, SYSLOG::OPER_ADD, $args);
            }
            return $id;
        } else {
            return false;
        }
    }

    public function TarifftagGetList()
    {
        if ($tarifftaglist = $this->db->GetAll('SELECT id, name, description,
				(SELECT COUNT(*)
					FROM tariffassignments
					WHERE tarifftagid = tarifftags.id
				) AS tariffscount
				FROM tarifftags ORDER BY name ASC')) {
            $totalcount = 0;

            foreach ($tarifftaglist as $idx => $row) {
                $totalcount += $row['tariffscount'];
            }

            $tarifftaglist['total'] = count($tarifftaglist);
            $tarifftaglist['totalcount'] = $totalcount;
        }

        return $tarifftaglist;
    }

    public function TarifftagGet($id)
    {
        $result = $this->db->GetRow('SELECT id, name, description FROM tarifftags WHERE id=?', array($id));
        $result['tariffs'] = $this->db->GetAll('SELECT t.id AS id, t.name AS tariffname FROM tariffassignments, tariffs t '
                . 'WHERE t.id = tariffid AND tarifftagid = ? '
                . ' GROUP BY t.id, t.name ORDER BY t.name', array($id));

        $result['tariffscount'] = empty($result['tariffs']) ? 0 : count($result['tariffs']);
        $result['count'] = $result['tariffscount'];
        return $result;
    }

    public function TarifftagExists($id)
    {
        return ($this->db->GetOne('SELECT id FROM tarifftags WHERE id=?', array($id)) ? true : false);
    }

    public function GetTariffWithoutTagNames($tagid)
    {
        return $this->db->GetAll('SELECT t.id AS id, t.name AS tariffname FROM tariffs t WHERE t.disabled = 0
	    AND t.id NOT IN (
		SELECT tariffid FROM tariffassignments WHERE tarifftagid = ?)
	    GROUP BY t.id, t.name
	    ORDER BY t.name', array($tagid));
    }

    public function TariffassignmentDelete($tariffassignmentdata)
    {
        if ($this->syslog) {
            $assign = $this->db->GetRow('SELECT tariffid FROM tariffassignments WHERE tarifftagid = ? AND tariffid = ?', array($tariffassignmentdata['tarifftagid'], $tariffassignmentdata['tariffid']));
            if ($assign) {
                $args = array(
                    SYSLOG::RES_TARIFFASSIGN => $assign['id'],
                    SYSLOG::RES_TARIFF => $assign['tariffid'],
                    SYSLOG::RES_TARIFFTAG => $tariffassignmentdata['tarifftagid']
                );
                $this->syslog->AddMessage(SYSLOG::RES_TARIFFASSIGN, SYSLOG::OPER_DELETE, $args);
            }
        }
        return $this->db->Execute('DELETE FROM tariffassignments WHERE tarifftagid=? AND tariffid=?', array($tariffassignmentdata['tarifftagid'], $tariffassignmentdata['tariffid']));
    }

    public function TariffassignmentExist($tagid, $tariffid)
    {
        return $this->db->GetOne('SELECT 1 FROM tariffassignments WHERE tarifftagid=? AND tariffid=?', array($tagid, $tariffid));
    }

    public function TariffassignmentAdd($tariffassignmentdata)
    {
        $res = $this->db->Execute('INSERT INTO tariffassignments (tarifftagid, tariffid) VALUES (?, ?)', array($tariffassignmentdata['tarifftagid'], $tariffassignmentdata['tariffid']));
        if ($this->syslog && $res) {
            $id = $this->db->GetLastInsertID('tariffassignments');
            $args = array(
                SYSLOG::RES_TARIFFASSIGN => $id,
                SYSLOG::RES_TARIFF => $tariffassignmentdata['tariffid'],
                SYSLOG::RES_TARIFFTAG => $tariffassignmentdata['tarifftagid']
            );
            $this->syslog->AddMessage(SYSLOG::RES_TARIFFASSIGN, SYSLOG::OPER_ADD, $args);
        }
        return $res;
    }

    public function TarifftagDelete($id)
    {
        if (!$this->TarifftagWithTariffGet($id)) {
            if ($this->syslog) {
                $tariffassigns = $this->db->GetAll('SELECT tariffid, tarifftagid FROM tariffassignments WHERE tarifftagid = ?', array($id));
                if (!empty($tariffassigns)) {
                    foreach ($tariffassigns as $tariffassign) {
                        $args = array(
                        SYSLOG::RES_TARIFFASSIGN => $tariffassign['tariffid'],
                        SYSLOG::RES_TARIFF => $tariffassign['tariffid'],
                        SYSLOG::RES_TARIFFTAG => $tariffassign['tarifftagid']
                        );
                        $this->syslog->AddMessage(SYSLOG::RES_TARIFFASSIGN, SYSLOG::OPER_DELETE, $args);
                    }
                }
                $this->syslog->AddMessage(
                    SYSLOG::RES_TARIFFTAG,
                    SYSLOG::OPER_DELETE,
                    array(SYSLOG::RES_TARIFFTAG => $id)
                );
            }
            $this->db->Execute('DELETE FROM tarifftags WHERE id=?', array($id));
            return true;
        } else {
            return false;
        }
    }

    public function TarifftagWithTariffGet($id)
    {
        return $this->db->GetOne('SELECT COUNT(*) FROM tariffassignments WHERE tarifftagid = ?', array($id));
    }

    public function TarifftagUpdate($tarifftagdata)
    {
        $args = array(
            'name' => $tarifftagdata['name'],
            'description' => $tarifftagdata['description'],
            SYSLOG::RES_TARIFFTAG => $tarifftagdata['id']
        );
        if ($this->syslog) {
            $this->syslog->AddMessage(SYSLOG::RES_TARIFFTAG, SYSLOG::OPER_UPDATE, $args);
        }
        return $this->db->Execute('UPDATE tarifftags SET name=?, description=? WHERE id=?', array_values($args));
    }

    public function TarifftagGetAll()
    {
        return $this->db->GetAll('SELECT g.id, g.name, g.description FROM tarifftags g ORDER BY g.name ASC');
    }

    public function getTariffTagsForTariff($tariffid)
    {
        return $this->db->GetAllByKey(
            'SELECT t.id, t.name FROM tariffassignments a
            JOIN tarifftags t ON t.id = a.tarifftagid
            WHERE a.tariffid = ?
            ORDER BY t.name',
            'id',
            array($tariffid)
        );
    }

    public function updateTariffTagsForTariff($tariffid, $tags)
    {
        $current_tags = $this->db->GetCol(
            'SELECT tarifftagid FROM tariffassignments WHERE tariffid = ?',
            array($tariffid)
        );
        if (empty($current_tags)) {
            $current_tags = array();
        }
        if (empty($tags)) {
            $tags = array();
        }

        $to_remove = array_diff($current_tags, $tags);
        if (!empty($to_remove)) {
            foreach ($to_remove as $id) {
                $this->TariffassignmentDelete(array(
                    'tarifftagid' => $id,
                    'tariffid' => $tariffid,
                ));
            }
        }
        $to_add = array_diff($tags, $current_tags);
        if (!empty($to_add)) {
            foreach ($to_add as $id) {
                $this->TariffassignmentAdd(array(
                    'tarifftagid' => $id,
                    'tariffid' => $tariffid,
                ));
            }
        }
    }
}
