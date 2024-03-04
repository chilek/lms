<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2022 LMS Developers
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
 * LMSUserManager
 *
 */
class LMSUserManager extends LMSManager implements LMSUserManagerInterface
{

    /**
     * Sets user password
     *
     * @param int $id User id
     * @param string $passwd Password
     */
    public function setUserPassword($id, $passwd, $net = false)
    {
        if ($net) {
            $args = array(
                'netpasswd' => empty($passwd) ? null : $passwd,
                SYSLOG::RES_USER => $id
            );
            $result = $this->db->Execute(
                'UPDATE users SET netpasswd = ?
                WHERE id = ?',
                array_values($args)
            );
        } else {
            $args = array(
                'passwd' => password_hash($passwd, PASSWORD_DEFAULT),
                'passwdforcechange' => 0,
                SYSLOG::RES_USER => $id
            );
            $result = $this->db->Execute(
                'UPDATE users SET passwd = ?, passwdlastchange = ?NOW?, passwdforcechange = ?
                WHERE id = ?',
                array_values($args)
            );
            $this->db->Execute('INSERT INTO passwdhistory (userid, hash) VALUES (?, ?)', array($id, password_hash($passwd, PASSWORD_DEFAULT)));
        }
        if ($result && $this->syslog) {
            unset($args['passwd']);
            unset($args['netpasswd']);
            $this->syslog->AddMessage(SYSLOG::RES_USER, SYSLOG::OPER_USERPASSWDCHANGE, $args);
        }
    }

    public function forcePasswordChange($id)
    {
        $args = array(
            'passwdforcechange' => 1,
            SYSLOG::RES_USER => $id
        );
        $this->db->Execute('UPDATE users SET passwdforcechange = ? WHERE id = ?', array_values($args));
        if ($this->syslog) {
            $this->syslog->AddMessage(SYSLOG::RES_USER, SYSLOG::OPER_UPDATE, $args);
        }
    }

    public function SetUserAuthentication($id, $twofactorauth, $twofactorauthsecretkey)
    {
        $args = array(
            'twofactorauth' => $twofactorauth,
            'twofactorauthsecretkey' => $twofactorauthsecretkey,
            SYSLOG::RES_USER => $id,
        );
        $this->db->Execute('UPDATE users SET twofactorauth = ?, twofactorauthsecretkey = ?
            WHERE id = ?', array_values($args));
        if ($this->syslog) {
            $this->syslog->AddMessage(SYSLOG::RES_USER, SYSLOG::OPER_USERAUTCHANGE, $args);
        }
    }

    /**
     * Returns user name
     *
     * @param int $id User id
     * @return string User name
     */
    public function getUserName($id = null)
    {
        if ($id === null) {
            $id = Auth::GetCurrentUser();
        } else if (!$id) {
            return '';
        }

        if (!($name = $this->cache->getCache('users', $id, 'name'))) {
            if ($this->auth && Auth::GetCurrentUser() == $id) {
                $name = $this->auth->logname;
            } else {
                $name = $this->db->GetOne('SELECT name FROM vusers WHERE id=?', array($id));
            }
            $this->cache->setCache('users', $id, 'name', $name);
        }
        return $name;
    }

    /**
     * Returns active users names
     *
     * @param array $params Parameters
     * @return array Users names
     */
    public function getUserNames($params = array())
    {
        extract($params);

        return $this->db->GetAll(
            'SELECT id, login, name, rname, deleted,
            (CASE WHEN access = 1 AND accessfrom <= ?NOW? AND (accessto >=?NOW? OR accessto = 0) THEN 1 ELSE 0 END) AS access
            FROM vusers
            WHERE deleted = 0'
            . (isset($withDeleted) ? ' OR deleted = 1' : '' )
            . ' ORDER BY rname ASC'
        );
    }

    public function getUserNamesIndexedById()
    {
        return $this->db->GetAllByKey('SELECT id, name, rname, login,
				(CASE WHEN access = 1 AND accessfrom <= ?NOW? AND (accessto >=?NOW? OR accessto = 0) THEN 1 ELSE 0 END) AS access
			FROM vusers WHERE deleted=0 ORDER BY rname ASC', 'id');
    }

    /**
     * Returns users
     *
     * @return array Users data
     */
    public function getUsers($params = array())
    {
        extract($params);

        if (isset($order)) {
            [$column, $asc] = explode(',', $order);
            $sqlord = $column . ' ' . ($asc == 'desc' ? 'DESC' : 'ASC');
        } else {
            $sqlord = 'login ASC';
        }

        if (isset($superuser)) {
            $userlist = $this->db->GetAllByKey(
                'SELECT id, login, name, phone, lastlogindate, lastloginip, passwdexpiration, passwdlastchange, access,
                accessfrom, accessto, rname, twofactorauth
            FROM vallusers
            WHERE deleted = 0'
                . (!empty($divisions) ? ' AND id IN (SELECT userid
                    FROM userdivisions
                    WHERE divisionid IN (' . $divisions . ')
                    )' : '')
                . (!empty($excludedUsers) ? ' AND id NOT IN (' . $excludedUsers . ')' : '') .
                ' ORDER BY ' . $sqlord,
                'id'
            );
        } else {
            $userlist = $this->db->GetAllByKey(
                'SELECT id, login, name, phone, lastlogindate, lastloginip, passwdexpiration, passwdlastchange, access,
                    accessfrom, accessto, rname, twofactorauth
                FROM vusers
                WHERE deleted = 0'
                . (!empty($divisions) ? ' AND id IN (SELECT userid
                        FROM userdivisions
                        WHERE divisionid IN (' . $divisions . ')
                        )' : '')
                . (!empty($excludedUsers) ? ' AND id NOT IN (' . $excludedUsers . ')' : '') .
                ' ORDER BY ' . $sqlord,
                'id'
            );
        }

        return $userlist;
    }
        /**
     * Returns users
     *
     * @return array Users data
     */
    public function getUserList($params = array())
    {
        extract($params);

        $userid = Auth::GetCurrentUser();
        $deletedFilter = !empty($hideDeleted) || !isset($hideDeleted) ? ' AND deleted = 0' : '';
        $disabledFilter = empty($userAccess) ? '' : ' AND (CASE WHEN access = 1 AND accessfrom <= ?NOW? AND (accessto >=?NOW? OR accessto = 0) THEN 1 ELSE 0 END) = 1';

        $userlist = $this->db->GetAllByKey(
            'SELECT id, login, name, phone, lastlogindate, lastloginip, passwdexpiration, passwdlastchange, access, deleted,
            (CASE WHEN access = 1 AND accessfrom <= ?NOW? AND (accessto >=?NOW? OR accessto = 0) THEN 1 ELSE 0 END) AS accessinfo,
            accessfrom, accessto, rname, twofactorauth'
            . ' FROM ' . (isset($superuser) ? 'vallusers' : 'vusers')
            . ' WHERE 1=1'
            . $deletedFilter
            . $disabledFilter
            . (!empty($divisions) ? ' AND id IN 
                (SELECT userid
                FROM userdivisions
                WHERE divisionid IN (' . $divisions . ')
                )' : '')
            . ' ORDER BY login ASC',
            'id'
        );

        if ($userlist) {
            foreach ($userlist as &$row) {
                if ($row['id'] == $userid) {
                    $row['lastlogindate'] = $this->auth->last;
                    $row['lastloginip'] = $this->auth->lastip;
                }

                $row['accessfrom'] = ($row['accessfrom'] ? date('Y/m/d', $row['accessfrom']) : '-');
                $row['accessto'] = ($row['accessto'] ? date('Y/m/d', $row['accessto']) : '-');
                $row['lastlogin'] = ($row['lastlogindate'] ? date('Y/m/d H:i', $row['lastlogindate']) : '-');
                $row['lastloginip'] = check_ip($row['lastloginip']) ? $row['lastloginip'] : '-';
                $row['passwdlastchange'] = ($row['passwdlastchange'] ? date('Y/m/d H:i', $row['passwdlastchange']) : '-');
                $row['lastloginhost'] = '-';
            }
            unset($row);
        }
        if (empty($short)) {
            $userlist['total'] = empty($userlist) ? 0 : count($userlist);
        }
        return $userlist;
    }

    /**
     * Returns user id for given login
     *
     * @param string $login User login
     * @return int User id
     */
    public function getUserIDByLogin($login)
    {
        return $this->db->GetOne('SELECT id FROM users WHERE login=?', array($login));
    }

    /**
     * Adds user
     *
     * @param array $user User data
     * @return int|false User id on success, false otherwise
     */
    public function userAdd($user)
    {
        $args = array(
            'login' => $user['login'],
            'firstname' => Utils::removeInsecureHtml($user['firstname']),
            'lastname' => Utils::removeInsecureHtml($user['lastname']),
            'issuer' => Utils::removeInsecureHtml($user['issuer']),
            'email' => $user['email'],
            'passwd' => password_hash($user['password'], PASSWORD_DEFAULT),
            'netpasswd' => empty($user['netpassword']) ? null : $user['netpassword'],
            'rights' => $user['rights'],
            'hosts' => $user['hosts'],
            'trustedhosts' => $user['trustedhosts'],
            'position' => $user['position'],
            'ntype' => !empty($user['ntype']) ? $user['ntype'] : null,
            'phone' => !empty($user['phone']) ? $user['phone'] : null,
            'passwdforcechange' => isset($user['passwdforcechange']) ? 1 : 0,
            'passwdexpiration' => !empty($user['passwdexpiration']) ? $user['passwdexpiration'] : 0,
            'access' => !empty($user['access']) ? 1 : 0,
            'accessfrom' => !empty($user['accessfrom']) ? $user['accessfrom'] : 0,
            'accessto' => !empty($user['accessto']) ? $user['accessto'] : 0,
            'twofactorauth' => $user['twofactorauth'],
            'twofactorauthsecretkey' => $user['twofactorauthsecretkey'],
        );
        $user_inserted = $this->db->Execute(
            'INSERT INTO users (login, firstname, lastname, issuer, email, passwd, netpasswd, rights,
                hosts, trustedhosts, position, ntype, phone,
                passwdforcechange, passwdexpiration, access, accessfrom, accessto, twofactorauth, twofactorauthsecretkey)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            array_values($args)
        );
        if ($user_inserted) {
            $id = $this->db->GetLastInsertID('users');

            $customergroup_manager = new LMSCustomerGroupManager($this->db, $this->auth, $this->cache, $this->syslog);
            $customergroups = $customergroup_manager->getAllCustomerGroups();
            if (empty($customergroups)) {
                $customergroups = array();
            }
            $customergroups = array_keys($customergroups);

            if (empty($user['customergroups'])) {
                $user['customergroups'] = array();
            }
            $excludedgroups_to_add = array_diff($customergroups, $user['customergroups']);

            foreach ($excludedgroups_to_add as $group) {
                if ($this->db->Execute(
                    'INSERT INTO excludedgroups (userid, customergroupid) VALUES (?, ?)',
                    array($id, $group)
                ) && $this->syslog) {
                    $args = array(
                        SYSLOG::RES_EXCLGROUP => $this->db->GetLastInsertID('excludedgroups'),
                        SYSLOG::RES_CUSTGROUP => $group,
                        SYSLOG::RES_USER => $id,
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_EXCLGROUP, SYSLOG::OPER_ADD, $args);
                }
            }

            foreach ($user['divisions'] as $divisionid) {
                $this->db->Execute('INSERT INTO userdivisions (userid, divisionid) VALUES(?, ?)', array($id, $divisionid));
            }

            if ($this->syslog) {
                unset($args['passwd']);
                $args[SYSLOG::RES_USER] = $id;
                $args['added_divisions'] = implode(',', $user['divisions']);
                $this->syslog->AddMessage(SYSLOG::RES_USER, SYSLOG::OPER_ADD, $args);
            }
            return $id;
        } else {
            return false;
        }
    }

    /**
     * Deletes user
     *
     * @param int $id User id
     * @return boolean True on success
     */
    public function userDelete($id)
    {
        if ($this->db->Execute('UPDATE users SET deleted=1, access=0 WHERE id=?', array($id))) {
            if ($this->syslog) {
                $args = array(
                    SYSLOG::RES_USER => $id,
                    'deleted' => 1,
                    'access' => 0,
                );
                $this->syslog->AddMessage(SYSLOG::RES_USER, SYSLOG::OPER_UPDATE, $args);
            }
            $this->cache->setCache('users', $id, 'deleted', 1);
            return true;
        }
    }

    /**
     * Checks if user exists
     *
     * @param int $id User id
     * @return boolean|int True if exists, false if not exists, -1 if exists but deleted
     */
    public function userExists($id)
    {
        switch ($this->db->GetOne('SELECT deleted FROM users WHERE id=?', array($id))) {
            case '0':
                return true;
            case '1':
                return -1;
            case '':
            default:
                return false;
        }
    }

    /**
     * Sets user access
     *
     * @param int $id User id
     * @param int $access Access
     */
    public function userAccess($id, $access)
    {
        $this->db->Execute('UPDATE users SET access = ? WHERE id = ?', array($access, $id));
    }

    /**
     * Check user access
     *
     * @param int $id User id
     * @return int
     */
    public function checkUserAccess($id)
    {
        return $this->db->Execute(
            'SELECT 1
            FROM users 
            WHERE id = ?
            AND deleted = 0
            AND access = 1
            AND accessfrom <= ?NOW?
            AND (accessto >= ?NOW? OR accessto = 0)',
            array($id)
        );
    }

    /**
     * Returns user data
     *
     * @param int $id User id
     * @return array
     */
    public function getUserInfo($id)
    {
        $userinfo = $this->db->GetRow('SELECT * FROM vusers WHERE id = ?', array($id));
        if ($userinfo) {
            $userinfo['trusteddevices'] = $this->db->GetAll(
                'SELECT id, useragent, useragent, INET_NTOA(ipaddr) AS ip, expires
                    FROM twofactorauthtrusteddevices
                    WHERE userid = ?
                    ORDER BY expires',
                array($id)
            );
            if (empty($userinfo['trusteddevices'])) {
                $userinfo['trusteddevices'] = array();
            }

            $usergroup_manager = new LMSUserGroupManager($this->db, $this->auth, $this->cache, $this->syslog);
            $userinfo['usergroups'] = $usergroup_manager->getUserAssignments($id);

            $this->cache->setCache('users', $id, null, $userinfo);

            if ($userinfo['id'] == Auth::GetCurrentUser()) {
                $userinfo['lastlogindate'] = $this->auth->last;
                $userinfo['lastloginip'] = $this->auth->lastip;
            }

            if ($userinfo['accessfrom']) {
                $userinfo['accessfrom'] = date('Y/m/d', $userinfo['accessfrom']);
            } else {
                $userinfo['accessfrom'] = '';
            }

            if ($userinfo['accessto']) {
                $userinfo['accessto'] = date('Y/m/d', $userinfo['accessto']);
            } else {
                $userinfo['accessot'] = '';
            }

            if ($userinfo['lastlogindate']) {
                $userinfo['lastlogin'] = date('Y/m/d H:i', $userinfo['lastlogindate']);
            } else {
                $userinfo['lastlogin'] = '-';
            }

            if ($userinfo['failedlogindate']) {
                $userinfo['faillogin'] = date('Y/m/d H:i', $userinfo['failedlogindate']);
            } else {
                $userinfo['faillogin'] = '-';
            }

            if ($userinfo['passwdlastchange']) {
                $userinfo['passwdlastchange'] = date('Y/m/d H:i', $userinfo['passwdlastchange']);
            } else {
                $userinfo['passwdlastchange'] = '-';
            }

            if (check_ip($userinfo['lastloginip'])) {
                $userinfo['lastloginhost'] = gethostbyaddr($userinfo['lastloginip']);
            } else {
                $userinfo['lastloginhost'] = '-';
                $userinfo['lastloginip'] = '-';
            }

            if (check_ip($userinfo['failedloginip'])) {
                $userinfo['failedloginhost'] = gethostbyaddr($userinfo['failedloginip']);
            } else {
                $userinfo['failedloginhost'] = '-';
                $userinfo['failedloginip'] = '-';
            }
        }
        return $userinfo;
    }

    /**
     * Updates user data
     *
     * @param array $user New user data
     * @return int|false Affected rows
     */
    public function userUpdate($user)
    {
        $args = array(
            'login' => $user['login'],
            'firstname' => Utils::removeInsecureHtml($user['firstname']),
            'lastname' => Utils::removeInsecureHtml($user['lastname']),
            'issuer' => Utils::removeInsecureHtml($user['issuer']),
            'email' => $user['email'],
            'rights' => $user['rights'],
            'hosts' => $user['hosts'],
            'trustedhosts' => $user['trustedhosts'],
            'position' => $user['position'],
            'ntype' => !empty($user['ntype']) ? $user['ntype'] : null,
            'phone' => !empty($user['phone']) ? $user['phone'] : null,
            'passwdforcechange' => isset($user['passwdforcechange']) ? 1 : 0,
            'passwdexpiration' => !empty($user['passwdexpiration']) ? $user['passwdexpiration'] : 0,
            'access' => !empty($user['access']) ? 1 : 0,
            'accessfrom' => !empty($user['accessfrom']) ? $user['accessfrom'] : 0,
            'accessto' => !empty($user['accessto']) ? $user['accessto'] : 0,
            'twofactorauth' => empty($user['twofactorauth']) ? 0 : 1,
            'twofactorauthsecretkey' => $user['twofactorauthsecretkey'],
            SYSLOG::RES_USER => $user['id']
        );
        $res = $this->db->Execute(
            'UPDATE users SET login = ?, firstname = ?, lastname = ?, issuer = ?, email = ?, rights = ?,
            hosts = ?, trustedhosts = ?, position = ?, ntype = ?, phone = ?, passwdforcechange = ?, passwdexpiration = ?,
            access = ?, accessfrom = ?, accessto = ?, twofactorauth = ?, twofactorauthsecretkey = ?
            WHERE id = ?',
            array_values($args)
        );

        if ($res) {
            if (!empty($user['diff_division_del'])) {
                foreach ($user['diff_division_del'] as $divisiondelid) {
                    $this->db->Execute('DELETE FROM userdivisions WHERE userid = ? AND divisionid = ?', array($user['id'], $divisiondelid));
                    $this->db->Execute('DELETE FROM uiconfig WHERE userid = ? AND divisionid = ?', array($user['id'], $divisiondelid));
                }
            }

            if (!empty($user['diff_division_add'])) {
                foreach ($user['diff_division_add'] as $divisionaddid) {
                    $this->db->Execute('INSERT INTO userdivisions (userid, divisionid) VALUES(?, ?)', array($user['id'], $divisionaddid));
                }
            }

            $usergroup_manager = new LMSUserGroupManager($this->db, $this->auth, $this->cache, $this->syslog);
            $usergroups = $usergroup_manager->getUserAssignments($user['id']);
            $usergroups = array_keys($usergroups);
            if (empty($user['usergroups'])) {
                $user['usergroups'] = array();
            }
            $usergroups_to_remove = array_diff($usergroups, $user['usergroups']);
            $usergroups_to_add = array_diff($user['usergroups'], $usergroups);

            foreach ($usergroups_to_remove as $group) {
                $usergroup_manager->UserassignmentDelete(array(
                    'userid' => $user['id'],
                    'usergroupid' => $group,
                ));
            }
            foreach ($usergroups_to_add as $group) {
                $usergroup_manager->UserassignmentAdd(array(
                    'userid' => $user['id'],
                    'usergroupid' => $group,
                ));
            }

            $customergroup_manager = new LMSCustomerGroupManager($this->db, $this->auth, $this->cache, $this->syslog);
            $customergroups = $customergroup_manager->getAllCustomerGroups();
            if (empty($customergroups)) {
                $customergroups = array();
            }
            $customergroups = array_keys($customergroups);

            if (!isset($user['customergroups'])) {
                $user['customergroups'] = array();
            }
            $excludedgroups = $this->db->GetAllByKey(
                'SELECT id, customergroupid FROM excludedgroups WHERE userid = ?',
                'customergroupid',
                array($user['id'])
            );
            if (empty($excludedgroups)) {
                $excludedgroups = array();
            }

            if (empty($user['customergroups'])) {
                $user['customergroups'] = array();
            }
            $excludedgroups_to_remove = array_diff(array_keys($excludedgroups), array_diff($customergroups, $user['customergroups']));
            $excludedgroups_to_add = array_diff($customergroups, $user['customergroups'], array_keys($excludedgroups));

            foreach ($excludedgroups_to_remove as $group) {
                if ($this->db->Execute(
                    'DELETE FROM excludedgroups WHERE userid = ? AND customergroupid = ?',
                    array($user['id'], $group)
                ) && $this->syslog) {
                    $args = array(
                        SYSLOG::RES_EXCLGROUP => $excludedgroups[$group]['id'],
                        SYSLOG::RES_CUSTGROUP => $group,
                        SYSLOG::RES_USER => $user['id'],
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_EXCLGROUP, SYSLOG::OPER_DELETE, $args);
                }
            }
            foreach ($excludedgroups_to_add as $group) {
                if ($this->db->Execute(
                    'INSERT INTO excludedgroups (userid, customergroupid) VALUES (?, ?)',
                    array($user['id'], $group)
                ) && $this->syslog) {
                    $args = array(
                        SYSLOG::RES_EXCLGROUP => $this->db->GetLastInsertID('excludedgroups'),
                        SYSLOG::RES_CUSTGROUP => $group,
                        SYSLOG::RES_USER => $user['id'],
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_EXCLGROUP, SYSLOG::OPER_ADD, $args);
                }
            }
        }

        if ($res && $this->syslog) {
            $args['added_divisions'] = implode(',', $user['diff_division_add']);
            $args['removed_divisions'] = implode(',', $user['diff_division_del']);
            $this->syslog->AddMessage(SYSLOG::RES_USER, SYSLOG::OPER_UPDATE, $args);
        }
        return $res;
    }

    /**
     * Returns user rights
     *
     * @param int $id User id
     * @return array User rights
     */
    public function getUserRights($id)
    {
        if (!($rights = $this->cache->getCache('users', $id, 'rights'))) {
            $rights = $this->db->GetOne('SELECT rights FROM users WHERE id = ?', array($id));
        }

        return explode(',', $rights);
    }

    public function PasswdExistsInHistory($id, $passwd)
    {
        $history = $this->db->GetAll('SELECT id, hash FROM passwdhistory WHERE userid = ? ORDER BY id DESC LIMIT ?', array($id, intval(ConfigHelper::getConfig('phpui.passwordhistory'))));
        if (empty($history)) {
            return false;
        }

        foreach ($history as $h) {
            if (password_verify($passwd, $h['hash'])) {
                return true;
            }
        }
        return false;
    }

    public function checkPassword($password, $net = false)
    {
        if ($net) {
            return $this->db->GetOne(
                'SELECT netpasswd FROM users WHERE id = ?',
                array(Auth::GetCurrentUser())
            ) == $password;
        } else {
            $dbpasswd = $this->db->GetOne(
                'SELECT passwd FROM users WHERE id = ?',
                array(Auth::GetCurrentUser())
            );
            return password_verify($password, $dbpasswd);
        }
    }

    public function isUserNetworkPasswordSet($id)
    {
        return $this->db->GetOne(
            'SELECT 1 FROM users WHERE id = ? AND netpasswd <> ?',
            array(
                $id,
                '',
            )
        ) == 1;
    }
}
