<?php

use PragmaRX\Google2FA\Google2FA;

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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
    // minimum delay enforced between two failed password attempts for the
    // same account, to slow down brute-force/dictionary attacks
    const LOGIN_THROTTLE_SECONDS = 2;

    private $id = null;
    public $login;
    private $targetLogin = null;
    public $logname;
    public $logrname;
    public $passwd;
    public $islogged = false;
    public $nousers = false;
    public $passverified = false;
    public $hostverified = false;
    public $trustedhost = false;
    public $access = false;
    public $accessfrom = false;
    public $accessto = false;
    private $authcoderequired = '';
    public $last;
    public $ip;
    public $lastip;
    private $passwdforcechange = false;
    private $passwdexpiration = null;
    private $passwdlastchange = null;
    private $passwdrequiredchange = false;
    private $twofactorauthrequiredchange = false;
    private $twofactorauth = null;
    private $twofactorauthsecretkey = null;
    private $authcode = null;
    private $trusteddevice = false;
    private $browserFingerprint = null;
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

    public static function GetCurrentUserName()
    {
        return self::$auth ? self::$auth->logname : null;
    }

    public static function GetCurrentUserReversedName()
    {
        return self::$auth ? self::$auth->logrname : null;
    }

    public function __construct(&$DB, &$SESSION)
    {
        self::$auth = $this;
        $this->DB = &$DB;
        $this->SESSION = &$SESSION;
        $this->SYSLOG = SYSLOG::getInstance();
        //$this->_revision = preg_replace('/^.Revision: ([0-9.]+).*/', '\1', $this->_revision);
        $this->_revision = '';

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $trusted_proxies = ConfigHelper::getConfig('phpui.trusted_proxies', '');
        if ($trusted_proxies !== '' && Utils::isAllowedIP($ip, $trusted_proxies) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            foreach (array_reverse(array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']))) as $hop) {
                if (filter_var($hop, FILTER_VALIDATE_IP) && !Utils::isAllowedIP($hop, $trusted_proxies)) {
                    $ip = $hop;
                    break;
                }
            }
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
        $this->SESSION->restore('session_target_login', $this->targetLogin);
        $this->SESSION->restore('session_authcoderequired', $this->authcoderequired);

        if (!empty($loginform['backtologinform'])) {
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
            } elseif (isset($loginform['login'])) {
                $this->SESSION->save('session_browser_fingerprint', null);
                $components = explode('#', $loginform['login']);
                $this->login = $login = $components[0];
                $targetLogin = count($components) == 2 ? $components[1] : null;
                // 'login#targetlogin' lets an admin instantly switch into another
                // user's session (ChangeLog: "allow to instantly switch user or
                // login as different user"). The gate below used to check whether
                // ANY full_access user existed anywhere in the system, not whether
                // the authenticating login ($login) itself had full_access — so any
                // operator who knew an admin's login could switch INTO that admin
                // account using their own password. Scope the check to $login.
                if (!empty($targetLogin)
                    && $this->DB->GetOne(
                        'SELECT 1 FROM users WHERE login = ? AND deleted = 0 AND access = 1 AND '
                        . $this->DB->RegExp('rights', 'full_access'),
                        array($login)
                    )
                ) {
                    $this->targetLogin = $targetLogin;
                }
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

            $this->switchUser();

            $this->logname = $this->logname ?: $this->SESSION->get('session_logname');
            $this->logrname = $this->logrname ?: $this->SESSION->get('session_logrname');
            $this->id = $this->id ?: $this->SESSION->get('session_id');

            if (isset($loginform)) {
                $this->SESSION->regenerateSID();
                $this->SESSION->save('session_browser_fingerprint', null);
                $this->DB->Execute('UPDATE users SET lastlogindate=?, lastloginip=? WHERE id=?', array(time(), $this->ip ,$this->id));
                writesyslog('User '.$this->login . (empty($this->authcode) ? '' : ' (authentication code)') . ' logged in.', LOG_INFO);
                if ($this->SYSLOG) {
                    $this->SYSLOG->NewTransaction('auth', $this->id);
                    $this->SYSLOG->AddMessage(
                        SYSLOG::RES_USER,
                        SYSLOG::OPER_USERLOGIN,
                        array(
                            SYSLOG::RES_USER => $this->id,
                            'ip' => $this->ip,
                            'useragent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                        )
                    );
                }
            }

            $this->SESSION->save('session_id', $this->id);
            $this->SESSION->save('session_login', $this->login);
            if (isset($this->targetLogin)) {
                $this->SESSION->save('session_target_login', $this->targetLogin);
            }
            $this->SESSION->restore_user_settings();
            $this->SESSION->save('session_logname', $this->logname);
            $this->SESSION->save('session_logrname', $this->logrname);
            $this->SESSION->save('session_last', $this->last);
            $this->SESSION->save('session_lastip', $this->lastip);
        } else {
            if (isset($loginform)) {
                if ($this->id) {
                    if ($this->authcoderequired) {
                        if (isset($this->authcode)) {
                            writesyslog('Bad authentication code (' . ($this->authcode ?? '-') . ') for ' . $this->login, LOG_WARNING);
                        }
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
            } elseif (isset($this->targetLogin)) {
                $this->SESSION->save('session_target_login', $this->targetLogin);
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
        if (password_verify($this->passwd, $dbpasswd)) {
            return true;
        }

        // legacy accounts not yet migrated to password_hash(); hash_equals()
        // avoids the PHP loose-comparison ("magic hash") type-juggling bypass
        if (hash_equals((string) $dbpasswd, md5($this->passwd))) {
            if ($this->id) {
                $this->DB->Execute(
                    'UPDATE users SET passwd = ? WHERE id = ?',
                    array(password_hash($this->passwd, PASSWORD_DEFAULT), $this->id)
                );
            }
            return true;
        }

        // Wrong password is an error even with 2FA; a bad TOTP code is reported separately.
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

    private function verifyAddressList($addresslist = '')
    {
        $addresslist = preg_split("/([\s]+|[\s]*,[\s]*)/", $addresslist, -1, PREG_SPLIT_NO_EMPTY);
        $isin = false;

        foreach ($addresslist as $value) {
            $net = '';
            $mask = '';

            if (strpos($value, '/') === false) {
                $net = $value;
            } else {
                [$net, $mask] = explode('/', $value);
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

        return false;
    }

    public function VerifyHost($hosts = '')
    {
        if (!$hosts) {
            return true;
        }

        if ($this->verifyAddressList($hosts)) {
            return true;
        } else {
            $this->error = trans('Access denied!');
            return false;
        }
    }

    public function verifyTrustedHost($hosts = '')
    {
        if (!$hosts) {
            return false;
        }

        if ($this->verifyAddressList($hosts)) {
            return true;
        } else {
            return false;
        }
    }

    private function VerifyDivision($userid)
    {
        $userid = intval($userid);
        if ($this->DB->GetOne('SELECT id FROM userdivisions WHERE userid = ?', array($userid))) {
            return true;
        } else {
            return false;
        }
    }

    public function SwitchUser($userid = null)
    {
        if (((isset($this->targetLogin) && ($user = $this->DB->GetRow('SELECT id, name, rname, passwd, hosts, trustedhosts, lastlogindate, lastloginip,
                passwdforcechange, passwdexpiration, passwdlastchange, access, accessfrom, accessto,
                twofactorauth, twofactorauthsecretkey
            FROM vusers WHERE login = ? AND deleted = 0', array($this->targetLogin))) !== null)
                || (isset($userid) && ($user = $this->DB->GetRow('SELECT id, name, rname, passwd, hosts, trustedhosts, lastlogindate, lastloginip,
                passwdforcechange, passwdexpiration, passwdlastchange, access, accessfrom, accessto,
                twofactorauth, twofactorauthsecretkey
            FROM vusers WHERE id = ? AND deleted = 0', array($userid))) !== null))
            && $this->VerifyDivision($user['id'])) {
            $this->logname = $user['name'];
            $this->logrname = $user['rname'];
            $this->id = $user['id'];
            $this->last = $user['lastlogindate'];
            $this->lastip = $user['lastloginip'];
            $this->passwdforcechange = $user['passwdforcechange'];
            $this->passwdexpiration = $user['passwdexpiration'];
            $this->passwdlastchange = $user['passwdlastchange'];
            $this->twofactorauth = $user['twofactorauth'];
            $this->twofactorauthsecretkey = $user['twofactorauthsecretkey'];
            $this->targetLogin = null;

            if (isset($userid)) {
                $this->SESSION->save('session_id', $this->id);
                $this->SESSION->save('session_login', $this->login);
                $this->SESSION->restore_user_settings();
                $this->SESSION->save('session_logname', $this->logname);
                $this->SESSION->save('session_logrname', $this->logrname);
            }
        }
    }

    public function VerifyUser()
    {
        $this->islogged = false;

        if ($user = $this->DB->GetRow('SELECT id, name, rname, passwd, hosts, trustedhosts, lastlogindate, lastloginip,
				passwdforcechange, passwdexpiration, passwdlastchange, access, accessfrom, accessto,
				twofactorauth, twofactorauthsecretkey, failedlogindate
			FROM vusers WHERE login=? AND deleted=0', array($this->login))) {
            $this->logname = $user['name'];
            $this->logrname = $user['rname'];
            $this->id = $user['id'];
            $this->last = $user['lastlogindate'];
            $this->lastip = $user['lastloginip'];
            $this->passwdforcechange = $user['passwdforcechange'];
            $this->passwdexpiration = $user['passwdexpiration'];
            $this->passwdlastchange = $user['passwdlastchange'];
            $this->twofactorauth = $user['twofactorauth'];
            $this->twofactorauthsecretkey = $user['twofactorauthsecretkey'];

            $division = $this->VerifyDivision($user['id']);
            if ($division) {
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
                        $this->SESSION->restore('session_passverified', $this->passverified);
                        $this->restoreLoginFingerprint();
                        $google2fa = new Google2FA();
                        if ($this->passverified && $google2fa->verifyKey(
                            $user['twofactorauthsecretkey'],
                            $this->authcode
                        )) {
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
                                $this->DB->Execute(
                                    'INSERT INTO twofactorauthcodehistory (userid, authcode, uts, success, ipaddr)
                                    VALUES (?, ?, ?NOW?, ?, ?)',
                                    array(
                                        $this->id,
                                        !empty($this->authcode) ?: '',
                                        1,
                                        empty($this->lastip) ? null : ip2long($this->lastip),
                                    )
                                );

                                $this->authcoderequired = '';
                                $this->islogged = true;
                                if ($this->trusteddevice) {
                                    $this->addTrustedDevice();
                                }
                            }
                        } else {
                            $this->DB->Execute(
                                'INSERT INTO twofactorauthcodehistory (userid, authcode, uts, ipaddr)
                                VALUES (?, ?, ?NOW?, ?)',
                                array(
                                    $this->id,
                                    !empty($this->authcode) ?: '',
                                    empty($this->lastip) ? null : ip2long($this->lastip),
                                )
                            );

                            $this->error = trans("Wrong authentication code.");
                        }
                    } else {
                        $this->error = trans("Too many failed login attempts in short time period.<br>Try again in a few minutes.");
                    }
                } elseif (!empty($user['failedlogindate']) && time() - $user['failedlogindate'] < self::LOGIN_THROTTLE_SECONDS) {
                    $this->error = trans("Too many failed login attempts in short time period.<br>Try again in a few minutes.");
                } else {
                    $hook_data = LMSPluginManager::getInstance()->executeHook(
                        'password_verification',
                        [
                            'login' => $this->login,
                            'passwd' => $this->passwd,
                            'result' => false,
                        ]
                    );
                    $this->passverified = $hook_data['result'] ?: $this->VerifyPassword($user['passwd']);
                    $this->hostverified = $this->VerifyHost($user['hosts']);
                    $this->trustedhost = $this->verifyTrustedHost($user['trustedhosts']);
                    $this->access = $this->VerifyAccess($user['access']);
                    $this->accessfrom = $this->VerifyAccessFrom($user['accessfrom']);
                    $this->accessto = $this->VerifyAccessTo($user['accessto']);
                    $this->islogged = ($this->passverified && $this->hostverified && $this->access && $this->accessfrom && $this->accessto);
                    if ($this->islogged) {
                        $this->saveLoginFingerprint($_POST['loginform']['fingerprint'] ?? null);
                    }
                    if (!$this->trustedhost && !empty($user['twofactorauth']) && !empty($user['twofactorauthsecretkey'])) {
                        $this->SESSION->save('session_passverified', $this->passverified);
                    }

                    if ($this->islogged && !$this->trustedhost && !empty($user['twofactorauth']) && !empty($user['twofactorauthsecretkey'])) {
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
                $this->error = trans('The user has no assigned company.');
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

    private function parseBrowserFingerprint($json)
    {
        if (!is_string($json) || $json === '' || strlen($json) > 8192) {
            return null;
        }

        $data = json_decode($json, true);
        if (!is_array($data) || ($data['version'] ?? null) !== 1) {
            return null;
        }

        $strings = ['os' => 32, 'browser' => 32, 'platform' => 100,
            'canvas' => 64, 'webglRenderer' => 512, 'webglVendor' => 256,
            'timezone' => 100];
        $result = ['version' => 1];
        foreach ($strings as $field => $limit) {
            $value = $data[$field] ?? null;
            if ($value !== null && (!is_string($value) || strlen($value) > $limit)) {
                return null;
            }
            $result[$field] = $value === '' ? null : $value;
        }
        if (!in_array($result['os'], ['windows', 'linux', 'macos', 'android', 'ios'], true)
            || !in_array($result['browser'], ['chrome', 'firefox', 'edge', 'opera', 'safari'], true)
            || ($result['canvas'] !== null && !preg_match('/^[a-f0-9]{64}$/D', $result['canvas']))) {
            return null;
        }

        foreach (['hardwareConcurrency' => [1, 1024], 'deviceMemory' => [0, 4096],
            'maxTouchPoints' => [0, 100], 'colorDepth' => [1, 128],
            'pixelRatio' => [0.1, 20]] as $field => [$min, $max]) {
            $value = $data[$field] ?? null;
            if ($value !== null && ((!is_int($value) && !is_float($value)) || $value < $min || $value > $max)) {
                return null;
            }
            $result[$field] = $value;
        }

        $languages = $data['languages'] ?? [];
        if (!is_array($languages) || count($languages) > 5) {
            return null;
        }
        foreach ($languages as $language) {
            if (!is_string($language) || strlen($language) > 32) {
                return null;
            }
        }
        $result['languages'] = array_values($languages);
        return $result;
    }

    private function fingerprintWeights()
    {
        return ['os' => 15, 'browser' => 15, 'platform' => 5,
            'canvas' => 15, 'webglRenderer' => 10, 'webglVendor' => 5,
            'languages' => 10, 'timezone' => 5, 'hardwareConcurrency' => 5,
            'deviceMemory' => 5, 'maxTouchPoints' => 5, 'colorDepth' => 3,
            'pixelRatio' => 2];
    }

    private function fingerprintCoverage(array $fingerprint)
    {
        $coverage = 0;
        foreach ($this->fingerprintWeights() as $field => $weight) {
            $value = $fingerprint[$field] ?? null;
            if ($value !== null && $value !== '' && $value !== []) {
                $coverage += $weight;
            }
        }
        return $coverage;
    }

    private function compareBrowserFingerprints(array $stored, array $current)
    {
        if (($stored['version'] ?? null) !== 1 || ($current['version'] ?? null) !== 1) {
            return 0;
        }
        foreach (['os', 'browser'] as $field) {
            if (empty($stored[$field]) || $stored[$field] !== ($current[$field] ?? null)) {
                return 0;
            }
        }
        if ($this->fingerprintCoverage($stored) < 90 || $this->fingerprintCoverage($current) < 90) {
            return 0;
        }
        $score = 0;
        foreach ($this->fingerprintWeights() as $field => $weight) {
            if (isset($stored[$field], $current[$field]) && $stored[$field] === $current[$field]) {
                $score += $weight;
            }
        }
        return $score;
    }

    private function saveLoginFingerprint($json)
    {
        $this->browserFingerprint = $this->parseBrowserFingerprint($json);
        $this->SESSION->save('session_browser_fingerprint', [
            'userid' => $this->id,
            'created_at' => time(),
            'data' => $this->browserFingerprint,
        ]);
    }

    private function restoreLoginFingerprint()
    {
        $entry = null;
        $this->SESSION->restore('session_browser_fingerprint', $entry);
        $this->browserFingerprint = null;
        if (!is_array($entry) || !isset($entry['userid'], $entry['created_at'])
            || (int) $entry['userid'] !== (int) $this->id
            || !is_int($entry['created_at']) || $entry['created_at'] > time()
            || time() - $entry['created_at'] > 300 || !is_array($entry['data'] ?? null)) {
            return;
        }
        $json = json_encode($entry['data']);
        $this->browserFingerprint = $this->parseBrowserFingerprint($json);
    }

    private function getTrustedDeviceParams()
    {
        $desKey = $this->getDesKey();
        $key = hash_hmac(
            'sha256',
            implode("\2\1\2", [$this->logname, $this->twofactorauthsecretkey]),
            $desKey,
            true
        );
        $name = hash_hmac('md5', $this->logname, $desKey);
        return compact('key', 'name');
    }

    private function buildTrustedDeviceCookie(array $cryptoParams, $expires)
    {
        $rand = bin2hex(random_bytes(32));
        $nonce = random_bytes(12);
        $plaintext = json_encode(['version' => 2, 'expires' => (int) $expires, 'rand' => $rand]);
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $cryptoParams['key'],
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            $cryptoParams['name'],
            16
        );
        if ($ciphertext === false) {
            return false;
        }
        return [
            'cookie' => 'v2.' . rtrim(strtr(base64_encode($nonce . $tag . $ciphertext), '+/', '-_'), '='),
            'tokenhash' => hash('sha256', $rand),
        ];
    }

    private function decodeTrustedDeviceCookie($value, array $cryptoParams)
    {
        if (!is_string($value) || strlen($value) > 2048
            || !preg_match('/^v2\.([a-zA-Z0-9_-]+)$/D', $value, $matches)) {
            return null;
        }
        $encoded = $matches[1];
        $raw = base64_decode(strtr($encoded, '-_', '+/')
            . str_repeat('=', (4 - strlen($encoded) % 4) % 4), true);
        if ($raw === false || strlen($raw) < 29) {
            return null;
        }
        $plaintext = openssl_decrypt(
            substr($raw, 28),
            'aes-256-gcm',
            $cryptoParams['key'],
            OPENSSL_RAW_DATA,
            substr($raw, 0, 12),
            substr($raw, 12, 16),
            $cryptoParams['name']
        );
        if ($plaintext === false) {
            return null;
        }
        $data = json_decode($plaintext, true);
        if (!is_array($data) || ($data['version'] ?? null) !== 2
            || !is_int($data['expires'] ?? null) || !is_string($data['rand'] ?? null)
            || !preg_match('/^[a-f0-9]{64}$/D', $data['rand'])) {
            return null;
        }
        return $data;
    }

    // remember option by https://github.com/corrideat/
    public function addTrustedDevice()
    {
        if (!is_array($this->browserFingerprint)
            || $this->fingerprintCoverage($this->browserFingerprint) < 90) {
            return false;
        }
        $cryptoParams = $this->getTrustedDeviceParams();
        $expires = time() + (int) ConfigHelper::getConfig('phpui.two_factor_auth_trust_device_time', 86400);
        try {
            $cookie = $this->buildTrustedDeviceCookie($cryptoParams, $expires);
        } catch (Exception $e) {
            return false;
        }
        if ($cookie === false) {
            return false;
        }
        $result = $this->DB->Execute(
            'INSERT INTO twofactorauthtrusteddevices
                (userid, cookiename, useragent, ipaddr, expires, tokenhash, fingerprint, fingerprintversion)
                VALUES (?, ?, ?, INET_ATON(?), ?, ?, ?, ?)',
            [$this->id, $cryptoParams['name'], $_SERVER['HTTP_USER_AGENT'] ?? '',
                $this->ip, $expires, $cookie['tokenhash'], json_encode($this->browserFingerprint), 1]
        );
        if ($result === false) {
            return false;
        }
        if (!setcookie($cryptoParams['name'], $cookie['cookie'], [
            'expires' => $expires,
            'path' => '/',
            'httponly' => true,
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'samesite' => 'Lax',
        ])) {
            $this->DB->Execute(
                'DELETE FROM twofactorauthtrusteddevices WHERE userid = ? AND tokenhash = ?',
                [
                    $this->id,
                    $cookie['tokenhash'],
                ]
            );
            return false;
        }
        return true;
    }

    public function isTrustedDevice()
    {
        if (!is_array($this->browserFingerprint)) {
            return false;
        }
        $cryptoParams = $this->getTrustedDeviceParams();
        $this->DB->Execute('DELETE FROM twofactorauthtrusteddevices WHERE expires < ?NOW?');
        $data = $this->decodeTrustedDeviceCookie($_COOKIE[$cryptoParams['name']] ?? null, $cryptoParams);
        if ($data === null) {
            return false;
        }
        $now = time();
        $maxLifetime = (int) ConfigHelper::getConfig('phpui.two_factor_auth_trust_device_time', 86400);
        if ($data['expires'] <= $now || $data['expires'] - $now > $maxLifetime) {
            return false;
        }
        $device = $this->DB->GetRow(
            'SELECT fingerprint, fingerprintversion FROM twofactorauthtrusteddevices
                WHERE userid = ? AND cookiename = ? AND tokenhash = ? AND expires = ?',
            [$this->id, $cryptoParams['name'], hash('sha256', $data['rand']), $data['expires']]
        );
        if (!$device || (int) $device['fingerprintversion'] !== 1) {
            return false;
        }
        $stored = $this->parseBrowserFingerprint($device['fingerprint']);
        if ($stored === null) {
            return false;
        }
        $minimumScore = max(
            90,
            min(100, (int) ConfigHelper::getConfig('phpui.two_factor_auth_trust_device_min_score', 90))
        );
        return $this->compareBrowserFingerprints($stored, $this->browserFingerprint) >= $minimumScore;
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
