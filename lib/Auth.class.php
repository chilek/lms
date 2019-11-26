<?php

use PragmaRX\Google2FA\Google2FA;

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

class Auth
{

    private $id = null;
    public $login;
    public $logname;
    public $passwd;
    public $islogged = false;
    public $nousers = false;
    public $passverified = false;
    public $hostverified = false;
    public $access = false;
    public $accessfrom = false;
    public $accessto = false;
    private $authcoderequired = '';
    public $last;
    public $ip;
    public $lastip;
    private $passwdrequiredchange = false;
    private $twofactorauthrequiredchange = false;
    private $twofactorauthsecretkey = null;
    public $error;
    public $_version = '1.11-git';
    public $_revision = '$Revision$';
    public $DB = null;
    public $SESSION = null;
    public $SYSLOG = null;

    private static $auth = null;

    public static function GetCurrentUser()
    {
        if (self::$auth) {
            return self::$auth->id;
        }
        return null;
    }

    public function __construct(&$DB, &$SESSION)
    {
        self::$auth = $this;
        $this->DB = &$DB;
        $this->SESSION = &$SESSION;
        $this->SYSLOG = SYSLOG::getInstance();
        //$this->_revision = preg_replace('/^.Revision: ([0-9.]+).*/', '\1', $this->_revision);
        $this->_revision = '';

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $this->ip = str_replace('::ffff:', '', $ip);

        if (isset($_GET['override'])) {
            $loginform = $_GET['loginform'];
        } elseif (isset($_POST['loginform'])) {
            $loginform = $_POST['loginform'];
        } elseif (isset($_POST['authcodeform'])) {
            $loginform = $_POST['authcodeform'];
        }

        $this->SESSION->restore('session_login', $this->login);
        $this->SESSION->restore('session_authcoderequired', $this->authcoderequired);

        if (isset($loginform['backtologinform']) && !empty($loginform['backtologinform'])) {
            $this->authcoderequired = false;
        }

        if ($this->login) {
            $this->islogged = true;
        } elseif (isset($loginform)) {
            if ($this->authcoderequired) {
                $this->login = $this->authcoderequired;
                $this->authcode = $loginform['authcode'];
                $this->trusteddevice = isset($loginform['trusteddevice']);
                writesyslog('Login attempt (authentication code) by ' . $this->login, LOG_INFO);
            } else {
                $this->login = $loginform['login'];
                $this->passwd = $loginform['pwd'];
                writesyslog('Login attempt by ' . $this->login, LOG_INFO);
            }
        } elseif ($this->DB->GetOne('SELECT COUNT(id) FROM users') == 0) {
            $this->islogged = true;
            $this->nousers = true;
            $_GET['m'] = 'useradd';
            return true;
        }

        if ($this->islogged || ($this->login && $this->VerifyUser())) {
            $this->SESSION->restore('session_passwdrequiredchange', $this->passwdrequiredchange);
            $this->SESSION->restore('session_twofactorauthrequiredchange', $this->twofactorauthrequiredchange);
            if (empty($this->last)) {
                $this->SESSION->restore('session_last', $this->last);
                $this->SESSION->restore('session_lastip', $this->lastip);
            }

            $this->logname = $this->logname ? $this->logname : $this->SESSION->get('session_logname');
            $this->id = $this->id ? $this->id : $this->SESSION->get('session_id');

            if (isset($loginform)) {
                $this->DB->Execute('UPDATE users SET lastlogindate=?, lastloginip=? WHERE id=?', array(time(), $this->ip ,$this->id));
                writesyslog('User '.$this->login . (empty($this->authcode) ? '' : ' (authentication code)') . ' logged in.', LOG_INFO);
                if ($this->SYSLOG) {
                    $this->SYSLOG->NewTransaction('auth', $this->id);
                    $this->SYSLOG->AddMessage(
                        SYSLOG::RES_USER,
                        SYSLOG::OPER_USERLOGIN,
                        array(SYSLOG::RES_USER => $this->id, 'ip' => $this->ip, 'useragent' => $_SERVER['HTTP_USER_AGENT'])
                    );
                }
            }

            $this->SESSION->save('session_id', $this->id);
            $this->SESSION->save('session_login', $this->login);
            $this->SESSION->restore_user_settings();
            $this->SESSION->save('session_logname', $this->logname);
            $this->SESSION->save('session_last', $this->last);
            $this->SESSION->save('session_lastip', $this->lastip);
        } else {
            if (isset($loginform)) {
                if ($this->id) {
                    if ($this->authcoderequired) {
                        writesyslog('Bad authentication code (' . $this->authcode . ') for ' . $this->login, LOG_WARNING);
                    } else {
                        if (!$this->hostverified) {
                            writesyslog('Bad host (' . $this->ip . ') for ' . $this->login, LOG_WARNING);
                        }
                        if (!$this->passverified) {
                            writesyslog('Bad password for ' . $this->login, LOG_WARNING);
                        }

                        $this->DB->Execute(
                            'UPDATE users SET failedlogindate=?, failedloginip=? WHERE id = ?',
                            array(time(), $this->ip, $this->id)
                        );
                        if ($this->SYSLOG) {
                            $this->SYSLOG->NewTransaction('auth', $this->id);
                            $this->SYSLOG->AddMessage(
                                SYSLOG::RES_USER,
                                SYSLOG::OPER_USERLOGFAIL,
                                array(
                                    SYSLOG::RES_USER => $this->id,
                                    'ip' => $this->ip,
                                    'useragent' => $_SERVER['HTTP_USER_AGENT']
                                )
                            );
                        }
                    }
                } else {
                    writesyslog('Unknown login ' . $this->login . ' from ' . $this->ip, LOG_WARNING);
                }
            }

            if (!$this->authcoderequired) {
                $this->LogOut();
            }
        }
    }

    public function authCodeRequired()
    {
        return $this->authcoderequired != '';
    }

    public function _postinit()
    {
        return true;
    }

    public function LogOut()
    {
        if ($this->islogged) {
            writesyslog('User ' . $this->login . ' logged out.', LOG_INFO);
            if ($this->SYSLOG) {
                $this->SYSLOG->NewTransaction('auth', $this->id);
                $this->SYSLOG->AddMessage(
                    SYSLOG::RES_USER,
                    SYSLOG::OPER_USERLOGOUT,
                    array(SYSLOG::RES_USER => $this->id, 'ip' => $this->ip, 'useragent' => $_SERVER['HTTP_USER_AGENT'])
                );
            }
        }
        $this->SESSION->finish();
    }

    public function VerifyPassword($dbpasswd = '')
    {
        if (crypt($this->passwd, $dbpasswd) == $dbpasswd) {
            return true;
        }

        $this->error = trans('Wrong password or login.');
        return false;
    }

    public function VerifyAccess($access)
    {
        $access = intval($access);
        if (empty($access)) {
            $this->error = trans('Account is disabled');
            return false;
        } else {
            return true;
        }
    }

    public function VerifyAccessFrom($access)
    {
        $access = intval($access);
        if (empty($access)) {
            return true;
        }
        if ($access < time()) {
            return true;
        }
        if ($access > time()) {
            $this->error = trans('Account is not active');
            return false;
        }
    }

    public function VerifyAccessTo($access)
    {
        $access = intval($access);
        if (empty($access)) {
            return true;
        }
        if ($access > time()) {
            return true;
        }
        if ($access < time()) {
            $this->error = trans('Account is not active');
            return false;
        }
    }

    public function VerifyHost($hosts = '')
    {
        if (!$hosts) {
            return true;
        }

        $allowedlist = explode(',', $hosts);
        $isin = false;

        foreach ($allowedlist as $value) {
            $net = '';
            $mask = '';

            if (strpos($value, '/') === false) {
                $net = $value;
            } else {
                list($net, $mask) = explode('/', $value);
            }

            $net = trim($net);
            $mask = trim($mask);

            if ($mask == '') {
                $mask = '255.255.255.255';
            } elseif (is_numeric($mask)) {
                $mask = prefix2mask($mask);
            }

            if (isipinstrict($this->ip, $net, $mask)) {
                return true;
            }
        }

        $this->error = trans('Access denied!');
        return false;
    }

    public function VerifyUser()
    {
        $this->islogged = false;

        if ($user = $this->DB->GetRow('SELECT id, name, passwd, hosts, lastlogindate, lastloginip,
				passwdforcechange, passwdexpiration, passwdlastchange, access, accessfrom, accessto,
				twofactorauth, twofactorauthsecretkey
			FROM vusers WHERE login=? AND deleted=0', array($this->login))) {
            $this->logname = $user['name'];
            $this->id = $user['id'];
            $this->last = $user['lastlogindate'];
            $this->lastip = $user['lastloginip'];
            $this->passwdforcechange = $user['passwdforcechange'];
            $this->passwdexpiration = $user['passwdexpiration'];
            $this->passwdlastchange = $user['passwdlastchange'];
            $this->twofactorauth = $user['twofactorauth'];
            $this->twofactorauthsecretkey = $user['twofactorauthsecretkey'];

            if ($this->authcoderequired) {
                $this->DB->Execute(
                    'DELETE FROM twofactorauthcodehistory
                        WHERE userid = ? AND success = ? AND uts < ?NOW? - 3 * 60 AND (INET_NTOA(ipaddr) = ? OR ipaddr IS NULL)',
                    array($this->id, 0, $this->lastip)
                );

                if ($this->DB->GetOne(
                    'SELECT COUNT(*) FROM twofactorauthcodehistory
                        WHERE userid = ? AND success = ? AND INET_NTOA(ipaddr) = ?',
                    array(
                        $this->id,
                        0,
                        $this->lastip
                    )
                ) < 10) {
                    $google2fa = new Google2FA();
                    if ($google2fa->verifyKey($user['twofactorauthsecretkey'], $this->authcode)) {
                        $this->DB->Execute(
                            'DELETE FROM twofactorauthcodehistory WHERE userid = ? AND success = ? AND uts < ?NOW? - 3 * 60',
                            array($this->id, 1)
                        );

                        if ($this->DB->GetOne(
                            'SELECT id FROM twofactorauthcodehistory
                                WHERE userid = ? AND success = ? AND authcode = ?',
                            array(
                                $this->id,
                                1,
                                $this->authcode
                            )
                        )) {
                            $this->error = trans("This code has already been used before the moment.");
                        } else {
                            $this->DB->Execute('INSERT INTO twofactorauthcodehistory (userid, authcode, uts, success, ipaddr)
                                VALUES (?, ?, ?NOW?, ?, INET_ATON(?))', array($this->id, !empty($this->authcode) ?: '', 1, $this->lastip));

                            $this->authcoderequired = '';
                            $this->islogged = true;
                            if ($this->trusteddevice) {
                                $this->addTrustedDevice();
                            }
                        }
                    } else {
                        $this->DB->Execute('INSERT INTO twofactorauthcodehistory (userid, authcode, uts, ipaddr)
                            VALUES (?, ?, ?NOW?, INET_ATON(?))', array($this->id, !empty($this->authcode) ?: '', $this->lastip));

                        $this->error = trans("Wrong authentication code.");
                    }
                } else {
                    $this->error = trans("Too many failed login attempts in short time period.<br>Try again in a few minutes.");
                }
            } else {
                $this->passverified = $this->VerifyPassword($user['passwd']);
                $this->hostverified = $this->VerifyHost($user['hosts']);
                $this->access = $this->VerifyAccess($user['access']);
                $this->accessfrom = $this->VerifyAccessFrom($user['accessfrom']);
                $this->accessto = $this->VerifyAccessTo($user['accessto']);
                $this->islogged = ($this->passverified && $this->hostverified && $this->access && $this->accessfrom && $this->accessto);

                if ($this->islogged && !empty($user['twofactorauth']) && !empty($user['twofactorauthsecretkey'])) {
                    if ($this->isTrustedDevice()) {
                        $this->authcoderequired = '';
                    } else {
                        $this->authcoderequired = $this->login;
                        $this->islogged = false;
                    }
                }
            }

            $this->SESSION->save('session_authcoderequired', $this->authcoderequired);

            if ($this->islogged) {
                if (($this->passwdexpiration
                    && (time() - $this->passwdlastchange) / 86400 >= $user['passwdexpiration'])
                    || $this->passwdforcechange) {
                    $this->SESSION->save('session_passwdrequiredchange', true);
                }
                if (!$this->twofactorauth && ConfigHelper::checkConfig('phpui.two_factor_auth_required')) {
                    $this->SESSION->save('session_twofactorauthrequiredchange', true);
                }
            }
        } else {
            $this->error = trans('Wrong password or login.');
        }

        return $this->islogged;
    }

    public function requiredPasswordChange()
    {
        return $this->passwdrequiredchange;
    }

    public function requiredTwoFactorAuthChange()
    {
        return $this->twofactorauthrequiredchange;
    }

    private function getDesKey()
    {
        $des_key = $this->DB->GetOne(
            'SELECT keyvalue FROM dbinfo WHERE keytype = ?',
            array('deskey')
        );

        if (empty($des_key)) {
            $des_key = Utils::randomBytes(24);
            $this->DB->Execute(
                'INSERT INTO dbinfo (keytype, keyvalue) VALUES (?, ?)',
                array('deskey', $des_key)
            );
        }

        return $des_key;
    }

    private function getTrustedDeviceParams()
    {
        $des_key = $this->getDesKey();

        $user_agent = hash_hmac(
            'md5',
            filter_input(INPUT_SERVER, 'HTTP_USER_AGENT') ?: "\0\0\0\0\0",
            $des_key
        );
        $key = hash_hmac(
            'sha256',
            implode("\2\1\2", array($this->logname, $this->twofactorauthsecretkey)),
            $des_key,
            true
        );
        $iv = hash_hmac(
            'md5',
            implode("\3\2\3", array($this->logname, $this->twofactorauthsecretkey)),
            $des_key,
            true
        );
        $name = hash_hmac(
            'md5',
            $this->logname,
            $des_key
        );

        return compact('user_agent', 'key', 'iv', 'name', 'des_key');
    }

    // remember option by https://github.com/corrideat/
    public function addTrustedDevice()
    {
        $crypto_params = $this->getTrustedDeviceParams();
        extract($crypto_params);

        $expires = time() + ConfigHelper::getConfig('phpui.two_factor_auth_trust_device_time', 86400);
        $rand = mt_rand();
        $signature = hash_hmac(
            'sha512',
            implode("\1\0\1", array($this->logname, $this->twofactorauthsecretkey, $user_agent, $rand, $expires)),
            $des_key,
            true
        );
        $plain_content = sprintf("%d:%d:%s", $expires, $rand, $signature);
        $encrypted_content = openssl_encrypt($plain_content, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        if ($encrypted_content !== false) {
            $b64_encrypted_content = strtr(base64_encode($encrypted_content), '+/=', '-_,');
            setcookie($name, $b64_encrypted_content, $expires);
            $this->DB->Execute(
                'INSERT INTO twofactorauthtrusteddevices (userid, cookiename, useragent, ipaddr, expires) VALUES (?, ?, ?, INET_ATON(?), ?)',
                array($this->id, $name, filter_input(INPUT_SERVER, 'HTTP_USER_AGENT'), $this->ip, $expires)
            );
            return true;
        }

        return false;
    }

    public function isTrustedDevice()
    {
        $crypto_params = $this->getTrustedDeviceParams();
        extract($crypto_params);

        $this->DB->Execute('DELETE FROM twofactorauthtrusteddevices WHERE expires < ?NOW?');

        $b64_encrypted_content = filter_input(
            INPUT_COOKIE,
            $name,
            FILTER_VALIDATE_REGEXP,
            array('options' => array('regexp' => '/[a-zA-Z0-9_-]+,{0,3}/'))
        );

        if (is_string($b64_encrypted_content) && !empty($b64_encrypted_content) && strlen($b64_encrypted_content) % 4 === 0) {
            $encrypted_content = base64_decode(strtr($b64_encrypted_content, '-_,', '+/='), true);
            if ($encrypted_content !== false) {
                $plain_content = openssl_decrypt($encrypted_content, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
                if ($plain_content !== false) {
                    $now = time();
                    list ($expires, $rand, $signature) = explode(':', $plain_content, 3);
                    if ($this->DB->GetOne(
                        'SELECT COUNT(*) FROM twofactorauthtrusteddevices
                        WHERE userid = ? AND cookiename = ? AND expires = ?',
                        array($this->id, $name, $expires)
                    ) && $expires > $now && ($expires - $now) <= ConfigHelper::getConfig('phpui.two_factor_auth_trust_device_time', 86400)) {
                        $signature_verification = hash_hmac(
                            'sha512',
                            implode("\1\0\1", array($this->logname, $this->twofactorauthsecretkey, $user_agent, $rand, $expires)),
                            $des_key,
                            true
                        );
                        // constant time
                        $cmp = strlen($signature) ^ strlen($signature_verification);
                        $signature = $signature ^ $signature_verification;
                        for ($i = 0; $i < strlen($signature); $i++) {
                            $cmp += ord($signature{$i});
                        }
                        return ($cmp === 0);
                    }
                }
            }
        }

        return false;
    }

    public function removeTrustedDevices($userid = null, $deviceid = null)
    {
        $this->DB->Execute(
            'DELETE FROM twofactorauthtrusteddevices
            WHERE userid = ?' . (empty($deviceid) ? '' : ' AND id = ' . intval($deviceid)),
            array(empty($userid) ? $this->id : $userid)
        );
    }
}
