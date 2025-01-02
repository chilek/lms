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
    private $unsecure_pin_validity;
    public $islogged = false;
    public $isPasswdChangeRequired = false;
    public $error;
    public $info;

    private $SID = null;            // session unique ID
    private $_vdata = array();
    private $_content = array();    // session content array
    private $_updated = false;      // indicates that content has
                                    // been altered
    private $autoupdate = false;    // do automatic update on each
                                    // save() or save_by_ref() ?
    private $GCprob = 10;           // probality (in percent) of
                                    // garbage collector procedure
    private $atime = 0;
    private $timeout;

    private $authcoderequired = '';
    private $authcode = null;

    public function __construct(&$DB, $timeout = 600)
    {
        global $LMS, $USERPANEL_AUTH_TYPES;

        $this->db = &$DB;
        $this->pin_allowed_characters = ConfigHelper::getConfig(
            'customers.pin_allowed_characters',
            ConfigHelper::getConfig(
                'phpui.pin_allowed_characters',
                '0123456789'
            )
        );
        $this->unsecure_pin_validity = intval(ConfigHelper::getConfig(
            'customers.unsecure_pin_validity',
            ConfigHelper::getConfig(
                'phpui.unsecure_pin_validity',
                0,
                true
            ),
            true
        ));
        $this->ip = str_replace('::ffff:', '', $_SERVER['REMOTE_ADDR']);
        $this->timeout = $timeout;

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
                || ($remindform['type'] == 2 && (!ConfigHelper::checkConfig('userpanel.sms_credential_reminders') || empty($sms_service)))) {
                return;
            }

            if (ConfigHelper::getConfig('userpanel.google_recaptcha_sitekey') && !$this->ValidateRecaptchaResponse()) {
                return;
            }

            $ten = preg_replace('/[^a-z0-9]/i', '', $remindform['ten']);
            $params = array($ten, $ten);
            switch ($remindform['type']) {
                case 1:
                    if (!check_email($remindform['email'])) {
                        return;
                    }
                    $join = 'JOIN customercontacts cc ON cc.customerid = c.id';
                    $where = ' AND contact = ? AND cc.type & ? = ?';
                    $params = array_merge(
                        $params,
                        array(
                            $remindform['email'],
                            CONTACT_EMAIL | CONTACT_DISABLED,
                            CONTACT_EMAIL,
                        )
                    );
                    break;
                case 2:
                    $phone = preg_replace('/[\s\-]/', '', $remindform['phone']);
                    if (!preg_match('/^[0-9]+$/', $phone)) {
                        return;
                    }
                    $join = 'JOIN customercontacts cc ON cc.customerid = c.id';
                    $where = ' AND REPLACE(REPLACE(contact, \'-\', \'\'), \' \', \'\') = ? AND cc.type & ? = ?';
                    $params = array_merge(
                        $params,
                        array(
                            $phone,
                            CONTACT_MOBILE | CONTACT_DISABLED,
                            CONTACT_MOBILE,
                        )
                    );
                    break;
                default:
                    return;
            }

            $allowed_customer_status = $this->getAllowedCustomerStatus();

            $auth_type = intval(ConfigHelper::getConfig('userpanel.auth_type', USERPANEL_AUTH_TYPE_ID_PIN));
            if ($auth_type == USERPANEL_AUTH_TYPE_EXTID_PIN) {
                $auth_options = array();
                foreach ($USERPANEL_AUTH_TYPES[USERPANEL_AUTH_TYPE_EXTID_PIN]['options'] as $option) {
                    $auth_options[$option['name']] = ConfigHelper::getConfig('userpanel.' . $option['name'], '');
                }
                $service_provider_id = intval($auth_options['authentication_customer_extid_service_provider_id']);
            }

            $customer = $this->db->GetRow(
                'SELECT
                    c.id,
                    c.pin,
                    c.pinlastchange,
                    e.extid
                FROM customers c
                ' . $join . "
                LEFT JOIN customerextids e ON e.customerid = c.id AND e.serviceproviderid "
                    . ($auth_type != USERPANEL_AUTH_TYPE_EXTID_PIN || empty($service_provider_id) ? ' IS NULL' : ' = ' . $service_provider_id) .
                " WHERE c.deleted = 0
                    AND ((ten <> '' AND REPLACE(REPLACE(ten, '-', ''), ' ', '') = ?)
                        OR (ssn <> '' AND ssn = ?))
                    " . (isset($allowed_customer_status) ? ' AND c.status IN (' . implode(', ', $allowed_customer_status) . ')' : '')
                    . $where,
                $params
            );
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

            if (preg_match('/^\$[0-9a-z]+\$/', $customer['pin']) || $this->unsecure_pin_validity && time() - $customer['pinlastchange'] > $this->unsecure_pin_validity) {
                $pin_min_size = intval(ConfigHelper::getConfig(
                    'customers.pin_min_length',
                    ConfigHelper::getConfig(
                        'phpui.pin_min_size',
                        4
                    )
                ));
                if (!$pin_min_size) {
                    $pin_min_size = 4;
                }
                $pin_max_size = intval(ConfigHelper::getConfig(
                    'customers.pin_max_length',
                    ConfigHelper::getConfig(
                        'phpui.pin_max_size',
                        6
                    )
                ));
                if (!$pin_max_size) {
                    $pin_max_size = 6;
                }
                if ($pin_min_size > $pin_max_size) {
                    $pin_max_size = $pin_min_size;
                }
                $customer['pin'] = generate_random_string(random_int($pin_min_size, $pin_max_size), $this->pin_allowed_characters);

                $this->db->Execute(
                    'UPDATE customers
                    SET pin = ?, pinlastchange = ?NOW?
                    WHERE id = ?',
                    array(
                        $customer['pin'],
                        $customer['id'],
                    )
                );
            }

            $body = str_replace(
                array(
                    '%id',
                    '%pin',
                    '%extid',
                    '%login',
                ),
                array(
                    $customer['id'],
                    $customer['pin'],
                    $customer['extid'],
                    $customer['id'],
                ),
                $body
            );
            if ($remindform['type'] == 1) {
                $LMS->SendMail(
                    $remindform['email'],
                    array('From' => '<' . ConfigHelper::getConfig('userpanel.reminder_mail_sender') . '>',
                        'To' => '<' . $remindform['email'] . '>',
                        'Subject' => $subject),
                    $body
                );
            } else {
                $sms_options = $LMS->getCustomerSMSOptions();

                $LMS->SendSMS($phone, $body, null, $sms_options ?? null);
            }
            $this->error = trans('Credential reminder has been sent!');
            return;
        }

        if (isset($_COOKIE['USID'])) {
            $this->_restoreSession();
            $this->restore('session_login', $this->login);
            $this->restore('session_authcoderequired', $this->authcoderequired);
            $this->restore('session_id', $this->id);
            $this->restore('session_authcode', $session_authcode);
        }

        if (!empty($loginform['backtologinform'])) {
            $this->authcoderequired = '';
            $this->login = null;
            $this->id = null;
            $this->remove('session_login');
            $this->remove('session_authcoderequired');
            $this->remove('session_id');
        }

        if ($this->login) {
            $this->islogged = true;
        } elseif (isset($loginform)) {
            if ($this->authcoderequired && $this->id) {
                if (empty($loginform['authcode'])) {
                    $this->authcoderequired = '';
                    $this->login = null;
                    $this->id = null;
                    $this->remove('session_login');
                    $this->remove('session_authcoderequired');
                    $this->remove('session_id');
                } else {
                    writesyslog('Login attempt (using authentication code) by customer ID: ' . $this->id, LOG_INFO);

                    $authinfo = $this->GetCustomerAuthInfo($this->id);
                    $authcode = $loginform['authcode'];

                    if ($authcode == $session_authcode) {
                        $this->islogged = true;
                        $this->isPasswdChangeRequired = $this->unsecure_pin_validity && !preg_match('/^\$[0-9a-z]+\$/', $authinfo['passwd']);
                        $this->authcoderequired = '';

                        $this->save('session_login', $this->login);

                        $this->save('passwd_change_required', $this->isPasswdChangeRequired);

                        if ($authinfo == null || $authinfo['failedlogindate'] == null) {
                            $authinfo['failedlogindate'] = 0;
                            $authinfo['failedloginip'] = '';
                        }
                        $authinfo['id'] = $this->id;
                        $authinfo['lastlogindate'] = time();
                        $authinfo['lastloginip'] = $this->ip;
                        $authinfo['enabled'] = 3;
                        $this->SetCustomerAuthInfo($authinfo);

                        writesyslog('Customer with ID: ' . $this->id . ' (using authentication code) logged in.', LOG_INFO);

                        $this->remove('session_authcode');
                        $this->remove('session_authcode_dt');
                    } else {
                        $this->error = trans('Incorrect one-time password!');

                        writesyslog('Bad authentication code (' . $authcode . ') for customer with ID: ' . $this->id, LOG_WARNING);
                    }
                }
            } elseif (isset($loginform['login'])) {
                $this->login = trim($loginform['login']);
                $this->passwd = trim($loginform['pwd']);
                $this->atime = time();

                $authdata = null;
                if (isset($loginform) && ConfigHelper::getConfig('userpanel.google_recaptcha_sitekey')) {
                    if ($this->passwd && $this->ValidateRecaptchaResponse()) {
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
                        $this->error = trans('Access is temporarily blocked. Please try again in 10 minutes.');
                    } else {
                        if ($authdata['passwd'] != null) {
                            $this->islogged = true;
                            $this->isPasswdChangeRequired = $this->unsecure_pin_validity && !preg_match('/^\$[0-9a-z]+\$/', $authdata['passwd']);
                            $this->id = $authdata['id'];
                            $this->authcoderequired = ConfigHelper::getConfig('userpanel.twofactor_auth_type', '', true);

                            if ($this->id) {
                                if (isset($_COOKIE['USID'])) {
                                    $this->_restoreSession();
                                }
                                if (empty($this->_vdata)) {
                                    $this->_createSession();
                                }

                                if (random_int(1, 100) <= $this->GCprob) {
                                    $this->_garbageCollector();
                                }

                                $authinfo = $this->GetCustomerAuthInfo($this->id);

                                $this->save('session_id', $this->id);

                                if ($this->authcoderequired) {
                                    $this->islogged = false;

                                    $this->restore('session_authcode_dt', $session_authcode_dt);

                                    $authinfo = $this->GetCustomerAuthInfo($this->id);

                                    switch ($this->authcoderequired) {
                                        case 'sms':
                                            $sms_service = ConfigHelper::getConfig('sms.service', '', true);
                                            if (empty($sms_service)) {
                                                $this->error = trans('SMS sending service is not configured!');
                                            } else {
                                                if (empty($authinfo['phones'])) {
                                                    $this->error = trans('You dont\'t have any phone numbers assigned to your account!');
                                                } else {
                                                    if (time() - $session_authcode_dt < 180) {
                                                        $this->error = trans('Your one-time password has already been sent via SMS to your phone number within the last 3 minutes.');
                                                    } else {
                                                        $authcode = mt_rand(100000, 999999);
                                                        $this->save('session_authcode', $authcode);
                                                        $this->save('session_authcode_dt', time());

                                                        $sms_options = $LMS->getCustomerSMSOptions();

                                                        foreach (explode(',', $authinfo['phones']) as $phone) {
                                                            $LMS->SendSMS(
                                                                $phone,
                                                                trans('Your one-time password is: $a', $authcode),
                                                                null,
                                                                $sms_options ?? null
                                                            );
                                                        }

                                                        $this->info = trans('Your one-time password has been sent via SMS to your phone number.');
                                                    }
                                                }
                                            }
                                            break;
                                        case 'email':
                                            break;
                                    }
                                } else {
                                    $this->save('session_login', $this->login);

                                    $this->save('passwd_change_required', $this->isPasswdChangeRequired);

                                    if ($authinfo == null || $authinfo['failedlogindate'] == null) {
                                        $authinfo['failedlogindate'] = 0;
                                        $authinfo['failedloginip'] = '';
                                    }
                                    $authinfo['id'] = $this->id;
                                    $authinfo['lastlogindate'] = time();
                                    $authinfo['lastloginip'] = $this->ip;
                                    $authinfo['enabled'] = 3;
                                    $this->SetCustomerAuthInfo($authinfo);

                                    writesyslog('Customer with ID: ' . $this->id . ' logged in.', LOG_INFO);
                                }
                            }
                        } elseif (empty($this->error)) {
                            $this->error = trans('Access denied!');
                        }
                    }
                } else {
                    $this->islogged = false;

                    writesyslog("Bad password for customer ID: " . $this->login, LOG_WARNING);

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

                    if (empty($this->error)) {
                        $this->error = trans('Access denied!');
                    }
                }
            }
        }

        if (($this->login || !isset($loginform)) && empty($this->authcoderequired) && isset($_COOKIE['USID'])) {
            if (!isset($this->_vdata['REMOTE_ADDR']) || $this->_vdata['REMOTE_ADDR'] != $this->ip) {
                $this->islogged = false;
                writesyslog(
                    "Session ip address does not match to web browser ip address. Customer ID: " . $this->login,
                    LOG_WARNING
                );
                $this->LogOut();

                return;
            } else {
                $this->islogged = true;
                $this->isPasswdChangeRequired = $this->get('passwd_change_required');
            }
        }

        $this->save('session_authcoderequired', $this->authcoderequired);
    }

    public function save($variable, $content)
    {
        $this->_content[$variable] = $content;

        if ($this->autoupdate) {
            $this->_saveSession();
        } else {
            $this->_updated = true;
        }
    }

    public function save_by_ref($variable, &$content)
    {
        $this->_content[$variable] =& $content;

        if ($this->autoupdate) {
            $this->_saveSession();
        } else {
            $this->_updated = true;
        }
    }

    public function restore($variable, &$content)
    {
        $content = $this->_content[$variable] ?? null;
    }

    public function get($variable)
    {
        return $this->_content[$variable] ?? null;
    }

    public function remove($variable)
    {
        if (isset($this->_content[$variable])) {
            unset($this->_content[$variable]);
        } else {
            return false;
        }
        if ($this->autoupdate) {
            $this->_saveSession();
        } else {
            $this->_updated = true;
        }
        return true;
    }

    public function is_set($variable)
    {
        return isset($this->_content[$variable]);
    }

    private function _garbageCollector()
    {
        // deleting sessions with timeout exceeded
        $this->db->Execute('DELETE FROM up_sessions WHERE atime < ?NOW? - ? AND mtime < ?NOW? - ?', array($this->timeout, $this->timeout));

        return true;
    }

    private function makeVData()
    {
        foreach (array('REMOTE_ADDR', 'REMOTE_HOST', 'HTTP_USER_AGENT', 'HTTP_VIA', 'HTTP_X_FORWARDED_FOR', 'SERVER_NAME', 'SERVER_PORT') as $vkey) {
            if (isset($_SERVER[$vkey])) {
                $vdata[$vkey] = $_SERVER[$vkey];
            }
        }
        return $vdata ?? null;
    }

    public function close()
    {
        $this->_saveSession();
        $this->SID = null;
        $this->_content = array();
    }

    public function finish()
    {
        $this->_destroySession();
    }

    private function makeSID()
    {
        [$usec, $sec] = explode(' ', microtime());
        return md5(uniqid(random_int(0, mt_getrandmax()), true)) . sprintf('%09x', $sec) . sprintf('%07x', ($usec * 10000000));
    }

    private function _createSession()
    {
        $this->SID = $this->makeSID();
        $this->_vdata = $this->makeVData();
        $this->_content = array();
        $this->atime = $now = time();
        $this->db->Execute(
            'INSERT INTO up_sessions (id, customerid, ctime, mtime, atime, vdata, content) VALUES (?, ?, ?, ?, ?, ?, ?)',
            array(
                $this->SID,
                $this->id,
                $now,
                $now,
                $now,
                serialize($this->_vdata),
                serialize($this->_content),
            )
        );
        setcookie('USID', $this->SID);
    }

    private function _restoreSession()
    {
        $this->SID = $_COOKIE['USID'];

        $row = $this->db->GetRow('SELECT * FROM up_sessions WHERE id = ?', array($this->SID));

        $now = time();

        $vdata = $this->makeVData();

        if ($row && serialize($vdata) == $row['vdata']) {
            if (($row['mtime'] < $now - $this->timeout) && ($row['atime'] < $now - $this->timeout)) {
                $this->_destroySession();
            } else {
                $this->db->Execute('UPDATE up_sessions SET atime = ? WHERE id = ?', array($now, $this->SID));
                $this->id = $row['customerid'];
                $this->_vdata = $vdata;
                $this->_content = unserialize($row['content']);
                $this->atime = $now;
            }
        } elseif ($row) {
            $this->_destroySession();
        }
    }

    private function _saveSession()
    {
        if ($this->SID && ($this->autoupdate || $this->_updated)) {
            $this->db->Execute(
                'UPDATE up_sessions SET content = ?, mtime = ?NOW? WHERE id = ?',
                array(
                    serialize($this->_content),
                    $this->SID,
                )
            );
        }
    }

    private function _destroySession()
    {
        $this->db->Execute('DELETE FROM up_sessions WHERE id = ?', array($this->SID));
        $this->_content = array();
        $this->SID = null;
        setcookie('USID', false);
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
        curl_close($ch);
        if ($res !== false && ($res = json_decode($res, true)) !== null && $res['success']) {
            return true;
        }

        return false;
    }

    public function _postinit()
    {
        return true;
    }

    public function LogOut()
    {
        if ($this->islogged) {
            $this->_destroySession();
        }
        unset($this->id);
    }

    public function TimeOut($timeout = 600)
    {
        if (time() - $this->atime > $timeout) {
            $this->error = trans('Idle time limit exceeded ($a sec.)', $timeout);
            return false;
        } else {
            return true;
        }
    }

    private function validPIN()
    {
        if (!ConfigHelper::checkConfig('userpanel.pin_validation')) {
            return true;
        }

        $string = $this->passwd;

        for ($i = 0; $i < strlen($this->pin_allowed_characters); $i++) {
            $string = str_replace($this->pin_allowed_characters[$i], '', $string);
        }
        return !strlen($string);
    }

    private function checkPIN($passwd, $passwdlastchange)
    {
        if (preg_match('/^\$[0-9a-z]+\$/i', $passwd)) {
            return password_verify($this->passwd, $passwd);
        }

        if (preg_match('/^[0-9a-f]{32}$/i', $passwd)) {
            return md5($this->passwd) == $passwd;
        }

        if ($this->unsecure_pin_validity && time() - $passwdlastchange >= $this->unsecure_pin_validity) {
            $this->error = trans('PIN is expired - use credential reminder form!');
            return false;
        }

        return $this->passwd == $passwd;
    }

    private function getAllowedCustomerStatus()
    {
        $allowed_customer_status =
            Utils::determineAllowedCustomerStatus(ConfigHelper::getConfig('userpanel.allowed_customer_status', ''), -1);
        if ($allowed_customer_status === -1) {
            $allowed_customer_status = null;
        }
        return $allowed_customer_status;
    }

    private function GetCustomerIDByPhoneAndPIN()
    {
        if (!$this->validPIN()) {
            return null;
        }

        $allowed_customer_status = $this->getAllowedCustomerStatus();

        $authinfo['id'] = $this->db->GetOne(
            'SELECT c.id FROM customers c, customercontacts cc
            WHERE customerid = c.id
                AND contact = ? AND cc.type < ?
                AND deleted = 0
                ' . (isset($allowed_customer_status) ? ' AND c.status IN (' . implode(', ', $allowed_customer_status) . ')' : '') . '
                LIMIT 1',
            array(
                $this->login,
                CONTACT_EMAIL,
            )
        );

        if (empty($authinfo['id'])) {
            return null;
        }

        $customer = $this->db->GetRow(
            'SELECT pin, pinlastchange
            FROM customers
            WHERE id = ?',
            array(
                $authinfo['id'],
            )
        );

        if ($this->checkPIN($customer['pin'], $customer['pinlastchange'])) {
            $authinfo['passwd'] = $customer['pin'];
        } else {
            $authinfo['passwd'] = null;
        }

        return $authinfo;
    }

    private function GetCustomerIDByIDAndPIN()
    {
        if (!$this->validPIN() || !preg_match('/^[0-9]+$/', $this->login)) {
            return null;
        }

        $allowed_customer_status = $this->getAllowedCustomerStatus();

        $authinfo['id'] = $this->db->GetOne(
            'SELECT id FROM customers
            WHERE id = ?
                AND deleted = 0
                ' . (isset($allowed_customer_status) ? ' AND status IN (' . implode(', ', $allowed_customer_status) . ')' : ''),
            array(
                $this->login,
            )
        );

        if (empty($authinfo['id'])) {
            return null;
        }

        $customer = $this->db->GetRow(
            'SELECT pin, pinlastchange
            FROM customers
            WHERE id = ?',
            array(
                $this->login,
            )
        );

        if ($this->checkPIN($customer['pin'], $customer['pinlastchange'])) {
            $authinfo['passwd'] = $customer['pin'];
        } else {
            $authinfo['passwd'] = null;
        }

        return $authinfo;
    }

    private function GetCustomerIDByDocumentAndPIN()
    {
        if (!$this->validPIN()) {
            return null;
        }

        $allowed_customer_status = $this->getAllowedCustomerStatus();

        $authinfo['id'] = $this->db->GetOne(
            'SELECT c.id FROM customers c
            JOIN documents d ON d.customerid = c.id
            WHERE fullnumber = ?
                AND deleted = 0
                ' . (isset($allowed_customer_status) ? ' AND c.status IN (' . implode(', ', $allowed_customer_status) . ')' : ''),
            array($this->login)
        );

        if (empty($authinfo['id'])) {
            return null;
        }

        $customer = $this->db->GetRow(
            'SELECT pin, pinlastchange
            FROM customers
			WHERE id = ?',
            array(
                $authinfo['id']
            )
        );

        if ($this->checkPIN($customer['pin'], $customer['pinlastchange'])) {
            $authinfo['passwd'] = $customer['pin'];
        } else {
            $authinfo['passwd'] = null;
        }

        return $authinfo;
    }

    private function GetCustomerIDByEmailAndPIN()
    {
        if (!$this->validPIN()) {
            return null;
        }

        $allowed_customer_status = $this->getAllowedCustomerStatus();

        $authinfo['id'] = $this->db->GetOne(
            'SELECT c.id FROM customers c, customercontacts cc
            WHERE cc.customerid = c.id
                AND contact = ?
                    AND cc.type & ? > 0
                    AND deleted = 0
                    ' . (isset($allowed_customer_status) ? ' AND c.status IN (' . implode(', ', $allowed_customer_status) . ')' : '') . '
                LIMIT 1',
            array(
                $this->login,
                CONTACT_EMAIL | CONTACT_INVOICES | CONTACT_NOTIFICATIONS,
            )
        );

        if (empty($authinfo['id'])) {
            return null;
        }

        $customer = $this->db->GetRow(
            'SELECT pin, pinlastchange
            FROM customers
            WHERE id = ?',
            array(
                $authinfo['id'],
            )
        );

        if ($this->checkPIN($customer['pin'], $customer['pinlastchange'])) {
            $authinfo['passwd'] = $customer['pin'];
        } else {
            $authinfo['passwd'] = null;
        }

        return $authinfo;
    }

    private function GetCustomerIDByNodeNameAndPassword()
    {
        if (!preg_match('/^[_a-z0-9-.]+$/i', $this->passwd) || !preg_match('/^[_a-z0-9-.]+$/i', $this->login)) {
            return null;
        }

        $allowed_customer_status = $this->getAllowedCustomerStatus();

        $authinfo['id'] = $this->db->GetOne(
            'SELECT n.ownerid FROM nodes n
            JOIN customers c ON c.id = n.ownerid
            WHERE n.name = ?
                ' . (isset($allowed_customer_status) ? ' AND c.status IN (' . implode(', ', $allowed_customer_status) . ')' : ''),
            array(
                $this->login,
            )
        );

        if (empty($authinfo['id'])) {
            return null;
        }

        $customer = $this->db->GetRow(
            'SELECT n.passwd AS pin, pinlastchange
            FROM customers c
            JOIN nodes n ON c.id = n.ownerid
            WHERE n.name = ?',
            array(
                $this->login,
            )
        );

        if ($this->checkPIN($customer['pin'], $customer['pinlastchange'])) {
            $authinfo['passwd'] = $customer['pin'];
        } else {
            $authinfo['passwd'] = null;
        }

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

        $allowed_customer_status = $this->getAllowedCustomerStatus();

        $authinfo['id'] = $this->db->GetOne(
            "SELECT id FROM customers
            WHERE deleted = 0
                AND (REPLACE(REPLACE(ssn, '-', ''), ' ', '') = ? OR REPLACE(REPLACE(ten, '-', ''), ' ', '') = ?)
                " . (isset($allowed_customer_status) ? ' AND status IN (' . implode(', ', $allowed_customer_status) . ')' : '') . "
            LIMIT 1",
            array($ssnten, $ssnten)
        );

        if (empty($authinfo['id'])) {
            return null;
        }

        $customer = $this->db->GetRow(
            'SELECT pin, pinlastchange
            FROM customers
            WHERE id = ?',
            array(
                $authinfo['id'],
            )
        );

        if ($this->checkPIN($customer['pin'], $customer['pinlastchange'])) {
            $authinfo['passwd'] = $customer['pin'];
        } else {
            $authinfo['passwd'] = null;
        }

        return $authinfo;
    }

    private function GetCustomerIDByExtIDAndPIN()
    {
        if (!$this->validPIN()) {
            return null;
        }

        $allowed_customer_status = $this->getAllowedCustomerStatus();

        $customer_extid_service_provider_id = intval(ConfigHelper::getConfig('userpanel.authentication_customer_extid_service_provider_id', ''));
        $authinfo['id'] = $this->db->GetOne(
            'SELECT
                c.id
            FROM customers c
            JOIN customerextids e ON e.customerid = c.id
            WHERE c.deleted = 0
                AND e.extid = ?
                AND e.serviceproviderid ' . (empty($customer_extid_service_provider_id) ? ' IS NULL' : ' = ' . $customer_extid_service_provider_id) . '
                ' . (isset($allowed_customer_status) ? ' AND c.status IN (' . implode(', ', $allowed_customer_status) . ')' : '') . '
                LIMIT 1',
            array(
                $this->login,
            )
        );

        if (empty($authinfo['id'])) {
            return null;
        }

        $customer = $this->db->GetRow(
            'SELECT pin, pinlastchange
            FROM customers
            WHERE id = ?',
            array(
                $authinfo['id'],
            )
        );

        if ($this->checkPIN($customer['pin'], $customer['pinlastchange'])) {
            $authinfo['passwd'] = $customer['pin'];
        } else {
            $authinfo['passwd'] = null;
        }

        return $authinfo;
    }

    private function GetCustomerAuthInfo($customerid)
    {
        return $this->db->GetRow(
            'SELECT
                c.id AS id,
                c.pin AS passwd,
                c.pinlastchange,
                lastlogindate,
                lastloginip,
                failedlogindate,
                failedloginip,
                enabled,
                m.emails,
                p.phones
            FROM customers c
            LEFT JOIN up_customers ON up_customers.customerid = c.id
            LEFT JOIN (
                SELECT
                    customercontacts.customerid,
                    ' . $this->db->GroupConcat('customercontacts.contact') . ' AS emails
                FROM customercontacts
                WHERE (customercontacts.type & ?) = ?
                GROUP BY customercontacts.customerid
            ) m ON m.customerid = c.id
            LEFT JOIN (
                SELECT
                    customercontacts.customerid,
                    ' . $this->db->GroupConcat('customercontacts.contact') . ' AS phones
                FROM customercontacts
                WHERE (customercontacts.type & ?) = ?
                GROUP BY customercontacts.customerid
            ) p ON p.customerid = c.id
            WHERE c.id = ?',
            array(
                CONTACT_EMAIL | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
                CONTACT_EMAIL | CONTACT_NOTIFICATIONS,
                CONTACT_MOBILE | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
                CONTACT_MOBILE | CONTACT_NOTIFICATIONS,
                $customerid,
            )
        );
    }

    private function SetCustomerAuthInfo($authinfo)
    {
        $actauthinfo = $this->GetCustomerAuthInfo($authinfo['id']);
        if ($actauthinfo != null) {
            $this->db->Execute(
                'UPDATE up_customers
                    SET lastlogindate = ?, lastloginip = ?, failedlogindate = ?, failedloginip = ?, enabled = ? WHERE customerid = ?',
                array(
                    $authinfo['lastlogindate'],
                    $authinfo['lastloginip'],
                    $authinfo['failedlogindate'],
                    $authinfo['failedloginip'],
                    $authinfo['enabled'],
                    $authinfo['id'],
                )
            );
        } else {
            $this->db->Execute(
                'INSERT INTO up_customers (customerid, lastlogindate, lastloginip, failedlogindate, failedloginip, enabled) VALUES (?, ?, ?, ?, ?, ?)',
                array(
                    $authinfo['id'],
                    $authinfo['lastlogindate'],
                    $authinfo['lastloginip'],
                    $authinfo['failedlogindate'],
                    $authinfo['failedloginip'],
                    $authinfo['enabled'],
                )
            );
        }

        $this->db->Execute(
            'UPDATE customers
                SET pinlastchange = ?
            WHERE id = ?',
            array(
                empty($authinfo['pinlastchange']) ? 0 : $authinfo['pinlastchange'],
                $authinfo['id'],
            )
        );
    }

    public function VerifyPassword()
    {
        if (empty($this->login)) {
            $this->error = trans('Please login.');
            return null;
        }

        switch (ConfigHelper::getConfig('userpanel.auth_type', USERPANEL_AUTH_TYPE_ID_PIN)) {
            case USERPANEL_AUTH_TYPE_ID_PIN:
                $authinfo = $this->GetCustomerIDByIDAndPIN();
                break;
            case USERPANEL_AUTH_TYPE_PHONE_PIN:
                $authinfo = $this->GetCustomerIDByPhoneAndPIN();
                break;
            case USERPANEL_AUTH_TYPE_DOCUMENT_PIN:
                $authinfo = $this->GetCustomerIDByDocumentAndPIN();
                break;
            case USERPANEL_AUTH_TYPE_EMAIL_PIN:
                $authinfo = $this->GetCustomerIDByEmailAndPIN();
                break;
            case USERPANEL_AUTH_TYPE_PPPOE_LOGIN_PASSWORD:
                $authinfo = $this->GetCustomerIDByNodeNameAndPassword();
                break;
            case USERPANEL_AUTH_TYPE_TEN_SSN_PIN:
                $authinfo = $this->GetCustomerIDBySsnTenAndPIN();
                break;
            case USERPANEL_AUTH_TYPE_EXTID_PIN:
                $authinfo = $this->GetCustomerIDByExtIDAndPIN();
                break;
        }

        if (!empty($authinfo) && isset($authinfo['id'])) {
            return $authinfo;
        } else {
            return null;
        }
    }

    public function authCodeRequired()
    {
        return $this->authcoderequired != '';
    }
}
