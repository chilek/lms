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
 * LMSCustomerGroupManager
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class LMSCustomerGroupManager extends LMSManager
{
    /**
     * Returns number of customer assignments to given group
     * 
     * @param int $id Group id
     * @return int Assignments number
     */
    public function CustomergroupWithCustomerGet($id)
    {
        return $this->db->GetOne(
            'SELECT COUNT(*) 
            FROM customerassignments
            WHERE customergroupid = ?', 
            array($id)
        );
    }
    
    /**
     * Adds customer group
     * 
     * @global array $SYSLOG_RESOURCE_KEYS
     * @param array $customergroupdata Customer group data
     * @return boolean|int Customer group id on success, false on failure
     */
    public function CustomergroupAdd($customergroupdata)
    {
        global $SYSLOG_RESOURCE_KEYS;
        if ($this->db->Execute('INSERT INTO customergroups (name, description) VALUES (?, ?)', array($customergroupdata['name'], $customergroupdata['description']))) {
            $id = $this->db->GetLastInsertID('customergroups');
            if ($this->syslog) {
                $args = array(
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTGROUP] => $id,
                    'name' => $customergroupdata['name'],
                    'description' => $customergroupdata['description']
                );
                $this->syslog->AddMessage(SYSLOG_RES_CUSTGROUP, SYSLOG_OPER_ADD, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTGROUP]));
            }
            return $id;
        } else {
            return FALSE;
        }
    }
    
    /**
     * Updates customer group
     * 
     * @global array $SYSLOG_RESOURCE_KEYS
     * @param array $customergroupdata Customer group data
     * @return type
     */
    public function CustomergroupUpdate($customergroupdata)
    {
        global $SYSLOG_RESOURCE_KEYS;
        $args = array(
            'name' => $customergroupdata['name'],
            'description' => $customergroupdata['description'],
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTGROUP] => $customergroupdata['id']
        );
        if ($this->syslog)
            $this->syslog->AddMessage(SYSLOG_RES_CUSTGROUP, SYSLOG_OPER_UPDATE, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTGROUP]));
        return $this->db->Execute('UPDATE customergroups SET name=?, description=? 
				WHERE id=?', array_values($args));
    }

    /**
     * Deletes customer group
     * 
     * @global array $SYSLOG_RESOURCE_KEYS
     * @param type $id
     * @return boolean
     */
    public function CustomergroupDelete($id)
    {
        global $SYSLOG_RESOURCE_KEYS;
        if (!$this->CustomergroupWithCustomerGet($id)) {
            if ($this->syslog) {
                $custassigns = $this->db->Execute('SELECT id, customerid, customergroupid FROM customerassignments
					WHERE customergroupid = ?', array($id));
                if (!empty($custassigns))
                    foreach ($custassigns as $custassign) {
                        $args = array(
                            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTASSIGN] => $custassign['id'],
                            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $custassign['customerid'],
                            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTGROUP] => $custassign['customergroupid']
                        );
                        $this->syslog->AddMessage(SYSLOG_RES_CUSTASSIGN, SYSLOG_OPER_DELETE, $args, array_keys($args));
                    }
                $this->syslog->AddMessage(SYSLOG_RES_CUSTGROUP, SYSLOG_OPER_DELETE, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTGROUP] => $id), array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTGROUP]));
            }
            $this->db->Execute('DELETE FROM customergroups WHERE id=?', array($id));
            return TRUE;
        } else
            return FALSE;
    }

    /**
     * Checks if customer group exists
     * 
     * @param int $id Customer group id
     * @return boolean True on success, false otherwise 
     */
    public function CustomergroupExists($id)
    {
        return ($this->DB->GetOne('SELECT id FROM customergroups WHERE id=?', array($id)) ? TRUE : FALSE);
    }

}
