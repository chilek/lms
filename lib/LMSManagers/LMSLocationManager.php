<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2013 LMS Developers
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
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class LMSLocationManager extends LMSManager implements LMSLocationManagerInterface
{

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
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_STATE] => $stateid,
            'zip' => $zip
        );
        if ($cstate === null) {
            $this->db->Execute(
                'INSERT INTO zipcodes (stateid, zip) VALUES (?, ?)', 
                array_values($args)
            );
            if ($this->syslog) {
                $args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ZIP]] = $this->db->GetLastInsertID('zipcodes');
                $this->syslog->AddMessage(
                    SYSLOG_RES_ZIP, 
                    SYSLOG_OPER_ADD, 
                    $args, 
                    array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_STATE],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ZIP]
                    )
                );
            }
        } else if ($cstate != $stateid) {
            $this->db->Execute(
                'UPDATE zipcodes SET stateid = ? WHERE zip = ?', 
                array_values($args)
            );
            if ($this->syslog) {
                $args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ZIP]] = $this->db->GetOne('SELECT id FROM zipcodes WHERE zip = ?', array($zip));
                $this->syslog->AddMessage(
                    SYSLOG_RES_ZIP, 
                    SYSLOG_OPER_UPDATE, 
                    $args, 
                    array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_STATE],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ZIP]
                    )
                );
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

}
