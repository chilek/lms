<?php

/*
 *  LMS version 1.11-git
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

class Session
{
    public $id;
    private $login;
    private $passwd;
    private $ip;
    private $db;
    private $pin_allowed_characters;
    public $islogged = false;
    public $error;

    public $_content = array();     // session content array

    public function __construct(&$DB, $timeout = 600)
    {
        global $LMS;

        session_start();
        $this->db = &$DB;
        $this->pin_allowed_characters = ConfigHelper::getConfig('phpui.pin_allowed_characters', '0123456789');
        $this->ip = str_replace('::ffff:', '', $_SERVER['REMOTE_ADDR']);

        if (isset($_GET['override'])) {
            $loginform = $_GET['loginform'];
        } elseif (isset($_POST['loginform'])) {
            $loginform = $_POST['loginform'];
        } elseif (isset($_POST['remindform'])) {
            $remindform = $_POST['remindform'];
        }

        if (isset($remindform)) {
            $sms_service = ConfigHelper::getConfig('sms.service', '', true);
            if (($remindform['type'] == 1 && !ConfigHelper::checkConfig('userpanel.mail_credential_reminders'))
                || ($remindform['type'] == 2 && (!ConfigHelper::checkConfig('userpanel.sms_credential_reminders')) || empty($sms_service))) {
                return;
            }

            if (ConfigHelper::getConfig('userpanel.google_recaptcha_sitekey') && !$this->ValidateRecaptchaResponse()) {
                return;
            }

            $ten = preg_replace('/-/', '', $remindform['ten']);
            $params = array($ten, $ten);
            switch ($remindform['type']) {
                case 1:
                    if (!check_email($remindform['email'])) {
                        return;
                    }
                    $join = 'JOIN customercontacts cc ON cc.customerid = c.id';
                    $where = ' AND contact = ? AND cc.type & ? > 0';
                    $params = array_merge($params, array($remindform['email'],(CONTACT_EMAIL|CONTACT_INVOICES|CONTACT_NOTIFICATIONS)));
                    break;
                case 2:
                    if (!preg_match('/^[0-9]+$/', $remindform['phone'])) {
                        return;
                    }
                    $join = 'JOIN customercontacts cc ON cc.customerid = c.id';
                    $where = ' AND contact = ? AND cc.type & ? = ?';
                    $params = array_merge(
                        $params,
                        array(preg_replace('/ -/', '', $remindform['phone']),
                            CONTACT_MOBILE,
                        CONTACT_MOBILE)
                    );
                    break;
                default:
                    return;
            }
            $customer = $this->db->GetRow("SELECT c.id, pin FROM customers c $join WHERE c.deleted = 0 AND ((ten <> '' AND REPLACE(ten, '-', '') = ?) OR (ssn <> '' AND ssn = ?))"
                . $where, $params);
            if (!$customer) {
                $this->error = trans('Credential reminder couldn\'t be sent!');
                return;
            }
            if ($remindform['type'] == 1) {
                $subject = ConfigHelper::getConfig('userpanel.reminder_mail_subject');
                $body = ConfigHelper::getConfig('userpanel.reminder_mail_body');
            } else {
                $body = ConfigHelper::getConfig('userpanel.reminder_sms_body');
            }
            $body = str_replace('%id', $customer['id'], $body);
            $body = str_replace('%pin', $customer['pin'], $body);
            if ($remindform['type'] == 1) {
                $LMS->SendMail(
                    $remindform['email'],
                    array('From' => '<' . ConfigHelper::getConfig('userpanel.reminder_mail_sender') . '>',
                        'To' => '<' . $remindform['email'] . '>',
                        'Subject' => $subject),
                    $body
                );
            } else {
                $LMS->SendSMS($remindform['phone'], $body);
            }
            $this->error = trans('Credential reminder has been sent!');
            return;
        }

        if (isset($loginform)) {
            $this->login = trim($loginform['login']);
            $this->passwd = trim($loginform['pwd']);
            $_SESSION['session_timestamp'] = time();
        } else {
            $this->login = isset($_SESSION['session_login']) ? $_SESSION['session_login'] : null;
            $this->passwd = isset($_SESSION['session_passwd']) ? $_SESSION['session_passwd'] : null;
            $this->id = isset($_SESSION['session_id']) ? $_SESSION['session_id'] : 0;
        }

        $authdata = null;
        if (isset($loginform) && ConfigHelper::getConfig('userpanel.google_recaptcha_sitekey')) {
            if ($this->ValidateRecaptchaResponse()) {
                $authdata = $this->VerifyPassword();
            }
        } elseif ($this->passwd) {
            $authdata = $this->VerifyPassword();
        }

        if ($authdata != null) {
            $authinfo = $this->GetCustomerAuthInfo($authdata['id']);
            if ($authinfo != null && isset($authinfo['enabled'])
                && $authinfo['enabled'] == 0
                && time() - $authinfo['failedlogindate'] < 600) {
                $authdata['passwd'] = null;
            }
        }

        if ($authdata != null && $authdata['passwd'] != null && $this->TimeOut($timeout)) {
            $this->islogged = true;
            $this->id = $authdata['id'];
            $_SESSION['session_login'] = $this->login;
            $_SESSION['session_passwd'] = $this->passwd;
            $_SESSION['session_id'] = $this->id;

            if ($this->id) {
                $authinfo = $this->GetCustomerAuthInfo($this->id);
                if ($authinfo == null || $authinfo['failedlogindate'] == null) {
                    $authinfo['failedlogindate'] = 0;
                    $authinfo['failedloginip'] = '';
                }
                $authinfo['id'] = $this->id;
                $authinfo['lastlogindate'] = time();
                $authinfo['lastloginip'] = $this->ip;
                $authinfo['enabled'] = 3;
                $this->SetCustomerAuthInfo($authinfo);
            }
        } else {
            $this->islogged = false;
            if (isset($loginform)) {
                writesyslog("Bad password for customer ID:".$this->login, LOG_WARNING);

                if ($authdata != null && $authdata['passwd'] == null) {
                    $authinfo = $this->GetCustomerAuthInfo($authdata['id']);
                    if ($authinfo == null) {
                        $authinfo['lastlogindate'] = 0;
                        $authinfo['lastloginip'] = '';
                        $authinfo['failedlogindate'] = 0;
                    }

                    if (time() - $authinfo['failedlogindate'] < 600) {
                        if (isset($authinfo['enabled']) && $authinfo['enabled'] > 0) {
                            $authinfo['enabled'] -= 1;
                        }
                    } else {
                        $authinfo['enabled'] = 2;
                    }

                    $authinfo['id'] = $authdata['id'];
                    $authinfo['failedlogindate'] = time();
                    $authinfo['failedloginip'] = $this->ip;
                    $this->SetCustomerAuthInfo($authinfo);
                }

                $this->error = trans('Access denied!');
            }

            $this->LogOut();
        }
    }

    private function ValidateRecaptchaResponse()
    {
        if (!isset($_POST['g-recaptcha-response'])) {
            return false;
        }

        if (!function_exists('curl_init')) {
            die('PHP cURL exension is not installed!');
        }

        $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');

        $post_fields = array(
            'secret' => urlencode(ConfigHelper::getConfig('userpanel.google_recaptcha_secret')),
            'response' => urlencode($_POST['g-recaptcha-response']),
            'ip' => $this->ip,
        );

        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($post_fields),
        ));

        $res = curl_exec($ch);
        if ($res !== false && ($res = json_decode($res, true)) !== null && $res['success']) {
            curl_close($ch);
            return true;
        }

        curl_close($ch);

        return false;
    }

    public function _postinit()
    {
        return true;
    }

    public function LogOut()
    {
        if ($this->islogged) {
            session_destroy();
        }
        unset($this->login);
        unset($this->password);
        unset($this->id);
        unset($_SESSION);
    }

    public function TimeOut($timeout = 600)
    {
        if ((time()-$_SESSION['session_timestamp']) > $timeout) {
            $this->error = trans('Idle time limit exceeded ($a sec.)', $timeout);
            return false;
        } else {
            $_SESSION['session_timestamp'] = time();
            return true;
        }
    }

    private function validPIN()
    {
        $string = $this->passwd;
        for ($i = 0; $i < strlen($this->pin_allowed_characters); $i++) {
            $string = str_replace($this->pin_allowed_characters[$i], '', $string);
        }
        return !strlen($string);
    }

    private function GetCustomerIDByPhoneAndPIN()
    {
        if (!$this->validPIN()) {
            return null;
        }

        $authinfo['id'] = $this->db->GetOne(
            'SELECT c.id FROM customers c, customercontacts cc
			WHERE customerid = c.id AND contact = ? AND cc.type < ? AND deleted = 0 LIMIT 1',
            array($this->login, CONTACT_EMAIL)
        );

        if (empty($authinfo['id'])) {
            return null;
        }

        $authinfo['passwd'] = $this->db->GetOne('SELECT pin FROM customers
			WHERE pin = ? AND id = ?', array($this->passwd, $authinfo['id']));

        return $authinfo;
    }

    private function GetCustomerIDByIDAndPIN()
    {
        if (!$this->validPIN() || !preg_match('/^[0-9]+$/', $this->login)) {
            return null;
        }

        $authinfo['id'] = $this->db->GetOne('SELECT id FROM customers
			WHERE id = ? AND deleted = 0', array($this->login));

        if (empty($authinfo['id'])) {
            return null;
        }

        $authinfo['passwd'] = $this->db->GetOne('SELECT pin FROM customers
			WHERE pin = ? AND id = ?', array($this->passwd, $this->login));

        return $authinfo;
    }

    private function GetCustomerIDByDocumentAndPIN()
    {
        if (!$this->validPIN()) {
            return null;
        }

        $authinfo['id'] = $this->db->GetOne(
            'SELECT c.id FROM customers c
			JOIN documents d ON d.customerid = c.id
			WHERE fullnumber = ? AND deleted = 0',
            array($this->login)
        );

        if (empty($authinfo['id'])) {
            return null;
        }

        $authinfo['passwd'] = $this->db->GetOne('SELECT pin FROM customers
			WHERE pin = ? AND id = ?', array($this->passwd, $authinfo['id']));

        return $authinfo;
    }

    private function GetCustomerIDByEmailAndPIN()
    {
        if (!$this->validPIN()) {
            return null;
        }

        $authinfo['id'] = $this->db->GetOne(
            'SELECT c.id FROM customers c, customercontacts cc
			WHERE cc.customerid = c.id AND contact = ? AND cc.type & ? > 0 AND deleted = 0 LIMIT 1',
            array($this->login, (CONTACT_EMAIL|CONTACT_INVOICES|CONTACT_NOTIFICATIONS))
        );

        if (empty($authinfo['id'])) {
            return null;
        }

        $authinfo['passwd'] = $this->db->GetOne('SELECT pin FROM customers
			WHERE pin = ? AND id = ?', array($this->passwd, $authinfo['id']));

        return $authinfo;
    }

    private function GetCustomerIDByNodeNameAndPassword()
    {
        if (!preg_match('/^[_a-z0-9-.]+$/i', $this->passwd) || !preg_match('/^[_a-z0-9-.]+$/i', $this->login)) {
            return null;
        }

        $authinfo['id'] = $this->db->GetOne('SELECT ownerid FROM nodes
			WHERE name = ?', array($this->login));

        if (empty($authinfo['id'])) {
            return null;
        }

        $authinfo['passwd'] = $this->db->GetOne('SELECT pin FROM customers c
			JOIN nodes n ON c.id = n.ownerid
			WHERE n.name = ? AND n.passwd = ?', array($this->login, $this->passwd));

        return $authinfo;
    }

    private function GetCustomerIDBySsnTenAndPIN()
    {
        if (!$this->validPIN()) {
            return null;
        }

        $ssnten = preg_replace('/[\-\s]/', '', $this->login);

        if (!strlen($ssnten)) {
            return null;
        }

        $authinfo['id'] = $this->db->GetOne(
            "SELECT id FROM customers
		    WHERE deleted = 0 AND (REPLACE(REPLACE(ssn, '-', ''), ' ', '') = ? OR REPLACE(REPLACE(ten, '-', ''), ' ', '') = ?)
		    LIMIT 1",
            array($ssnten, $ssnten)
        );

        if (empty($authinfo['id'])) {
            return null;
        }

        $authinfo['passwd'] = $this->db->GetOne(
            'SELECT pin FROM customers
		    WHERE id = ? AND pin = ?',
            array($authinfo['id'], $this->passwd)
        );

        return $authinfo;
    }

    private function GetCustomerAuthInfo($customerid)
    {
        return $this->db->GetRow(
            'SELECT customerid AS id, lastlogindate, lastloginip, failedlogindate, failedloginip, enabled FROM up_customers WHERE customerid=?',
            array($customerid)
        );
    }

    private function SetCustomerAuthInfo($authinfo)
    {
        $actauthinfo = $this->GetCustomerAuthInfo($authinfo['id']);
        if ($actauthinfo != null) {
            $this->db->Execute(
                'UPDATE up_customers SET lastlogindate=?, lastloginip=?, failedlogindate=?, failedloginip=?, enabled=? WHERE customerid=?',
                array($authinfo['lastlogindate'], $authinfo['lastloginip'], $authinfo['failedlogindate'], $authinfo['failedloginip'],
                $authinfo['enabled'],
                $authinfo['id'])
            );
        } else {
            $this->db->Execute(
                'INSERT INTO up_customers(customerid, lastlogindate, lastloginip, failedlogindate, failedloginip, enabled) VALUES (?, ?, ?, ?, ?, ?)',
                array($authinfo['id'], $authinfo['lastlogindate'], $authinfo['lastloginip'],
                $authinfo['failedlogindate'],
                $authinfo['failedloginip'],
                $authinfo['enabled'])
            );
        }
    }

    public function VerifyPassword()
    {
        if (empty($this->login)) {
            $this->error = trans('Please login.');
            return null;
        }

        switch (ConfigHelper::getConfig('userpanel.auth_type', 1)) {
            case 1:
                $authinfo = $this->GetCustomerIDByIDAndPIN();
                break;
            case 2:
                $authinfo = $this->GetCustomerIDByPhoneAndPIN();
                break;
            case 3:
                $authinfo = $this->GetCustomerIDByDocumentAndPIN();
                break;
            case 4:
                $authinfo = $this->GetCustomerIDByEmailAndPIN();
                break;
            case 5:
                $authinfo = $this->GetCustomerIDByNodeNameAndPassword();
                break;
            case 6:
                $authinfo = $this->GetCustomerIDBySsnTenAndPIN();
                break;
        }

        if (!empty($authinfo) && isset($authinfo['id'])) {
            return $authinfo;
        } else {
            return null;
        }
    }
}
