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
 * LMSUserManager
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class LMSUserManager extends LMSManager implements LMSUserManagerInterface
{

    /**
     * Sets user password
     * 
     * @global array $SYSLOG_RESOURCE_KEYS
     * @param int $id User id
     * @param string $passwd Password
     */
    public function setUserPassword($id, $passwd)
    {
        global $SYSLOG_RESOURCE_KEYS;
        $args = array(
            'passwd' => crypt($passwd),
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => $id
        );
        $this->db->Execute('UPDATE users SET passwd=?, passwdlastchange=?NOW? WHERE id=?', array_values($args));
	$this->db->Execute('INSERT INTO passwdhistory (userid, hash) VALUES (?, ?)', array($id, crypt($passwd)));
        if ($this->syslog) {
            unset($args['passwd']);
            $this->syslog->AddMessage(SYSLOG_RES_USER, SYSLOG_OPER_USERPASSWDCHANGE, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]));
        }
    }

    /**
     * Returns user name
     * 
     * @param int $id User id
     * @return string User name
     */
    public function getUserName($id = null) {
        if ($id === null) {
            $id = $this->auth->id;
        } else if (!$id) {
            return '';
        }

        if (!($name = $this->cache->getCache('users', $id, 'name'))) {
            if ($this->auth && $this->auth->id == $id) {
                $name = $this->auth->logname;
            } else {
                $name = $this->db->GetOne('SELECT name FROM users WHERE id=?', array($id));
            }
            $this->cache->setCache('users', $id, 'name', $name);
        }
        return $name;
    }

    /**
     * Returns active users names
     * 
     * @return array Users names
     */
    public function getUserNames()
    {
        return $this->db->GetAll('SELECT id, name FROM users WHERE deleted=0 ORDER BY name ASC');
    }

    /**
     * Returns users
     * 
     * @return array Users data
     */
    public function getUserList()
    {
        $userlist = $this->db->GetAll(
            'SELECT id, login, name, lastlogindate, lastloginip, passwdexpiration, passwdlastchange, access, swekey_id, accessfrom, accessto  
            FROM users 
            WHERE deleted=0 
            ORDER BY login ASC'
        );
        if ($userlist) {
            foreach ($userlist as $idx => $row) {
                if ($row['id'] == $this->auth->id) {
                    $row['lastlogindate'] = $this->auth->last;
                    $userlist[$idx]['lastlogindate'] = $this->auth->last;
                    $row['lastloginip'] = $this->auth->lastip;
                    $userlist[$idx]['lastloginip'] = $this->auth->lastip;
                }

                if ($row['accessfrom']) {
                    $userlist[$idx]['accessfrom'] = date('Y/m/d', $row['accessfrom']);
                } else {
                    $userlist[$idx]['accessfrom'] = '-';
                }

                if ($row['accessto']) {
                    $userlist[$idx]['accessto'] = date('Y/m/d', $row['accessto']);
                } else {
                    $userlist[$idx]['accessto'] = '-';
                }

                if ($row['lastlogindate']) {
                    $userlist[$idx]['lastlogin'] = date('Y/m/d H:i', $row['lastlogindate']);
                } else {
                    $userlist[$idx]['lastlogin'] = '-';
                }

                if ($row['passwdlastchange']) {
                    $userlist[$idx]['passwdlastchange'] = date('Y/m/d H:i', $row['passwdlastchange']);
                } else {
                    $userlist[$idx]['passwdlastchange'] = '-';
                }

                if (check_ip($row['lastloginip'])) {
                    $userlist[$idx]['lastloginhost'] = gethostbyaddr($row['lastloginip']);
                } else {
                    $userlist[$idx]['lastloginhost'] = '-';
                    $userlist[$idx]['lastloginip'] = '-';
                }
            }
        }

        $userlist['total'] = sizeof($userlist);
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
     * @global array $SYSLOG_RESOURCE_KEYS
     * @param array $user User data
     * @return int|false User id on success, false otherwise
     */
    public function userAdd($user)
    {
        global $SYSLOG_RESOURCE_KEYS;
        $args = array(
            'login' => $user['login'],
            'name' => $user['name'],
            'email' => $user['email'],
            'passwd' => crypt($user['password']),
            'rights' => $user['rights'],
            'hosts' => $user['hosts'],
            'position' => $user['position'],
            'ntype' => !empty($user['ntype']) ? $user['ntype'] : null,
            'phone' => !empty($user['phone']) ? $user['phone'] : null,
            'passwdexpiration' => !empty($user['passwdexpiration']) ? $user['passwdexpiration'] : 0,
            'access' => !empty($user['access']) ? 1 : 0,
            'accessfrom' => !empty($user['accessfrom']) ? $user['accessfrom'] : 0,
            'accessto' => !empty($user['accessto']) ? $user['accessto'] : 0,
        );
        $user_inserted = $this->db->Execute(
            'INSERT INTO users (login, name, email, passwd, rights, hosts, position, ntype, phone, passwdexpiration, access, accessfrom, accessto)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            array_values($args)
        );
        if ($user_inserted) {
            $id = $this->db->GetOne(
                'SELECT id FROM users WHERE login=?', 
                array($user['login'])
            );
            if ($this->syslog) {
                unset($args['passwd']);
                $args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]] = $id;
                $this->syslog->AddMessage(SYSLOG_RES_USER, SYSLOG_OPER_ADD, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]));
            }
            return $id;
        } else {
            return false;
        }
    }

    /**
     * Deletes user
     * 
     * @global array $SYSLOG_RESOURCE_KEYS
     * @param int $id User id
     * @return boolean True on success
     */
    public function userDelete($id)
    {
        global $SYSLOG_RESOURCE_KEYS;
        if ($this->db->Execute('UPDATE users SET deleted=1, access=0 WHERE id=?', array($id))) {
            if ($this->syslog) {
                $args = array(
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => $id,
                    'deleted' => 1,
                    'access' => 0,
                );
                $this->syslog->AddMessage(SYSLOG_RES_USER, SYSLOG_OPER_UPDATE, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]));
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
                return TRUE;
                break;
            case '1':
                return -1;
                break;
            case '':
            default:
                return FALSE;
                break;
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
     * Returns user data
     * 
     * @param int $id User id
     * @return array
     */
    public function getUserInfo($id)
    {
        $userinfo = $this->db->GetRow('SELECT * FROM users WHERE id = ?', array($id));
        if ($userinfo) {
            $this->cache->setCache('users', $id, null, $userinfo);

            if ($userinfo['id'] == $this->auth->id) {
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
     * @global array $SYSLOG_RESOURCE_KEYS
     * @param array $user New user data
     * @return int|false Affected rows
     */
    public function userUpdate($user)
    {
        global $SYSLOG_RESOURCE_KEYS;
        $args = array(
            'login' => $user['login'],
            'name' => $user['name'],
            'email' => $user['email'],
            'rights' => $user['rights'],
            'hosts' => $user['hosts'],
            'position' => $user['position'],
            'swekey_id' => !empty($user['use_swekey']) ? $user['swekey_id'] : null,
            'ntype' => !empty($user['ntype']) ? $user['ntype'] : null,
            'phone' => !empty($user['phone']) ? $user['phone'] : null,
            'passwdexpiration' => !empty($user['passwdexpiration']) ? $user['passwdexpiration'] : 0,
            'access' => !empty($user['access']) ? 1 : 0,
            'accessfrom' => !empty($user['accessfrom']) ? $user['accessfrom'] : 0,
            'accessto' => !empty($user['accessto']) ? $user['accessto'] : 0,
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => $user['id']
        );
        $res = $this->db->Execute('UPDATE users SET login=?, name=?, email=?, rights=?,
				hosts=?, position=?, swekey_id=?, ntype=?, phone=?, passwdexpiration=?, access=?, accessfrom=?, accessto=? WHERE id=?', array_values($args));
        if ($res && $this->syslog) {
            $this->syslog->AddMessage(SYSLOG_RES_USER, SYSLOG_OPER_UPDATE, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]));
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

		$rights = explode(',', $rights);

        return $rights;
    }
    
    public function PasswdExistsInHistory($id, $passwd) 
    {
	    $history = $this->db->GetAll('SELECT id, hash FROM passwdhistory WHERE userid = ? ORDER BY id DESC LIMIT ?', array($id, intval(ConfigHelper::getConfig('phpui.passwordhistory'))));
	    foreach ($history as $h) {
		    if (crypt($passwd, $h['hash']) == $h['hash']) return TRUE;
	    }
	    return FALSE;
    }

}
