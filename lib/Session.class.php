<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

    public $SID = null;         // session unique ID
    public $_version = '1.11-git';      // library version
    public $_revision = '$Revision$';   // library revision
    private $_content = array();        // session content array
    private $_tab_content = array();    // web browser tab session content array
    private $_persistent_settings = array();    // user persistent settings
    public $_updated = false;           // indicates that content has
                        // been altered
    public $DB = null;              // database library object
    public $timeout = 600;          // timeout since session will
                        // be destroyed
    private $settings_timeout = 28800;          // timeout since user settings will
                        // be cleared
    public $autoupdate = false;     // do automatic update on each
                        // save() or save_by_ref() ?
    public $GCprob = 10;            // probality (in percent) of
                        // garbage collector procedure

    private $tabId = null;
    private static $oldBackTo = '';
    private static $backTo = '';

    public function __construct(&$DB, $timeout = 0, $settings_timeout = 0)
    {
        $this->DB =& $DB;

        if (isset($timeout) && $timeout != 0) {
            $this->timeout = $timeout;
        }

        if (isset($settings_timeout)) {
            $this->settings_timeout = $settings_timeout;
        }

        if (!isset($_COOKIE['SID'])) {
            $this->_createSession();
        } else {
            $this->_restoreSession();
        }

        if (rand(1, 100) <= $this->GCprob) {
            $this->_garbageCollector();
        }

        if (isset($_COOKIE['tabId'])) {
            $this->tabId = $_COOKIE['tabId'];
        }
    }

    public function close()
    {
        $this->_saveSession();
        $this->SID = null;
        $this->_content = array();
        $this->_tab_content = array();
    }

    public function finish()
    {
        $this->_destroySession();
    }

    public function makeSID()
    {
        list($usec, $sec) = explode(' ', microtime());
        return md5(uniqid(rand(), true)).sprintf('%09x', $sec).sprintf('%07x', ($usec * 10000000));
    }

    public function restore_user_settings($force_settings_restore = false)
    {
        $settings = $this->DB->GetRow('SELECT settings, persistentsettings FROM users WHERE login = ?', array($this->_content['session_login']));
        if (!empty($settings)) {
            if (isset($settings['persistentsettings'])) {
                $this->_persistent_settings = unserialize($settings['persistentsettings']);
            }
            $settings = unserialize($settings['settings']);
            if (!empty($settings) && (!isset($settings['mtime'])
                || time() - $settings['mtime'] < $this->settings_timeout || $force_settings_restore)) {
                $this->_content = array_merge($this->_content, $settings);
                if (isset($this->_content['tabs'])) {
                    $this->_tab_content = $this->_content['tabs'];
                    unset($this->_content['tabs']);
                }
            }
        }
    }

    // new browser tab can be opened as hidden or tabid of new tab can be not initialised
    // so we have to be careful and handle 'backto' session variable in special way and
    // correct this variable when new tab id has been determined before the moment
    public static function getOldBackTo()
    {
        return self::$oldBackTo;
    }

    public static function getBackTo()
    {
        return self::$backTo;
    }

    public function fixBackTo($oldTabId, $oldBackTo, $newTabId, $newBackTo)
    {
        $this->DB->BeginTrans();
        $this->DB->LockTables('sessions');

        $content = $this->DB->GetOne('SELECT content FROM sessions WHERE id = ?', array($this->SID));
        $content = unserialize($content);

        if (isset($content['tabs'][$oldTabId]['backto'])) {
            if ($content['tabs'][$oldTabId]['backto'] != $oldBackTo) {
                $content['tabs'][$oldTabId]['backto'] = $oldBackTo;
            }
        }
        if (!isset($content['tabs'][$newTabId])) {
            $content['tabs'][$newTabId] = array();
        }
        $content['tabs'][$newTabId]['backto'] = $newBackTo;

        $this->DB->Execute('UPDATE sessions SET content = ? WHERE id = ?', array(serialize($content), $this->SID));

        $this->DB->UnLockTables();
        $this->DB->CommitTrans();
    }

    public function save($variable, $content, $tab = false)
    {
        if ($tab) {
            if ($variable === 'backto') {
                self::$oldBackTo = $this->_tab_content[$this->tabId]['backto'];
                self::$backTo = $content;
            }
            if (!isset($this->_tab_content[$this->tabId])) {
                $this->_tab_content[$this->tabId] = array();
            }
            $this->_tab_content[$this->tabId][$variable] = $content;
        } else {
            $this->_content[$variable] = $content;
        }

        if ($variable == 'session_login') {
            $this->restore_user_settings();
        }

        if ($this->autoupdate) {
            $this->_saveSession();
        } else {
            $this->_updated = true;
        }
    }

    public function save_by_ref($variable, &$content, $tab = false)
    {
        if ($tab) {
            if (!isset($this->_tab_content[$this->tabId])) {
                $this->_tab_content[$this->tabId] = array();
            }
            $this->_tab_content[$this->tabId][$variable] =& $content;
        } else {
            $this->_content[$variable] =& $content;
        }
        if ($this->autoupdate) {
            $this->_saveSession();
        } else {
            $this->_updated = true;
        }
    }

    public function restore($variable, &$content, $tab = false)
    {
        if ($tab) {
            if (isset($this->_tab_content[$this->tabId][$variable])) {
                $content = $this->_tab_content[$this->tabId][$variable];
            } else {
                $content = null;
            }
        } else {
            if (isset($this->_content[$variable])) {
                $content = $this->_content[$variable];
            } else {
                $content = null;
            }
        }
    }

    public function get($variable, $tab = false)
    {
        if ($tab) {
            if (isset($this->_tab_content[$this->tabId][$variable])) {
                return $this->_tab_content[$this->tabId][$variable];
            } else {
                return null;
            }
        } else {
            if (isset($this->_content[$variable])) {
                return $this->_content[$variable];
            } else {
                return null;
            }
        }
    }

    public function remove($variable, $tab = false)
    {
        if ($tab) {
            if (isset($this->_tab_content[$this->tabId][$variable])) {
                unset($this->_tab_content[$this->tabId][$variable]);
            } else {
                return false;
            }
        } else {
            if (isset($this->_content[$variable])) {
                unset($this->_content[$variable]);
            } else {
                return false;
            }
        }
        if ($this->autoupdate) {
            $this->_saveSession();
        } else {
            $this->_updated = true;
        }
        return true;
    }

    public function is_set($variable, $tab = false)
    {
        if ($tab) {
            return isset($this->_tab_content[$this->tabId][$variable]);
        } else {
            return isset($this->_content[$variable]);
        }
    }

    public function _createSession()
    {
        $this->SID = $this->makeSID();
        $this->_content = array();
        $this->_tab_content = array();
        $this->_content['tabs'] = $this->_tab_content;
        $this->DB->Execute(
            'INSERT INTO sessions (id, ctime, mtime, atime, vdata, content) VALUES (?, ?NOW?, ?NOW?, ?NOW?, ?, ?)',
            array($this->SID, serialize($this->makeVData()), serialize($this->_content))
        );
        setcookie('SID', $this->SID);
    }

    public function _restoreSession()
    {
        $this->SID = $_COOKIE['SID'];

        $row = $this->DB->GetRow('SELECT *, ?NOW? AS tt FROM sessions WHERE id = ?', array($this->SID));

        if ($row && serialize($this->makeVData()) == $row['vdata']) {
            if (($row['mtime'] < $row['tt'] - $this->timeout) && ($row['atime'] < $row['tt'] - $this->timeout)) {
                $this->_destroySession();
            } else {
                if (!isset($_POST['xjxfun']) && !isset($_GET['ajax'])) {
                    $this->DB->Execute('UPDATE sessions SET atime = ?NOW? WHERE id = ?', array($this->SID));
                }
                $this->_content = unserialize($row['content']);
                $this->restore_user_settings(true);
                return;
            }
        } elseif ($row) {
            $this->_destroySession();
        }

        $this->_createSession();
    }

    public function _saveSession()
    {
        static $session_variables = array('session_id' => true, 'session_login' => true,
            'session_logname' => true, 'session_last' => true, 'session_lastip' => true,
            'session_smsauthenticated' => true, 'backto' => true, 'lastmodule' => true,
            'session_passwdrequiredchange' => true, 'session_authcoderequired' => true,
            'session_twofactorauthrequirechange' => true, 'tabs' => true,
            'prepared_persistent_filters' => true);

        if ($this->autoupdate || $this->_updated) {
            $content = array_merge($this->_content, array('tabs' => $this->_tab_content));
            $session_content = array_intersect_key($content, $session_variables);
            $settings_content = array_diff_key($content, $session_variables);
            $settings_content['mtime'] = time();

            // new browser tab can be opened as hidden or tabid of new tab can be not initialised
            // so we have to be careful and handle 'backto' session variable in special way and
            // we should check if tabs info in session content stored in database didn't change
            // in mean time and if it did than one or more from ajax calls changed it and we should
            // use it as reliable source of info.

            $this->DB->BeginTrans();
            $this->DB->LockTables('sessions');

            $content = unserialize($this->DB->GetOne('SELECT content FROM sessions WHERE id = ?', array($this->SID)));
            if (is_array($content['tabs']) && (!is_array($session_content['tabs']) || (is_array($content['tabs']) && count($content['tabs']) > count($session_content['tabs'])))) {
                $session_content['tabs'] = $content['tabs'];
            }

            $this->DB->Execute(
                'UPDATE sessions SET content = ?, mtime = ?NOW? WHERE id = ?',
                array(serialize($session_content), $this->SID)
            );

            $this->DB->UnLockTables();
            $this->DB->CommitTrans();

            $this->DB->Execute(
                'UPDATE users SET settings = ?, persistentsettings = ? WHERE login = ?',
                array(serialize($settings_content), serialize($this->_persistent_settings), $this->_content['session_login'])
            );
        }
    }

    public function _destroySession()
    {
        if (isset($this->_content['mtime']) && time() - $this->_content['mtime'] >= $this->settings_timeout) {
            if (isset($this->_content['session_login'])) {
                $this->DB->Execute('UPDATE users SET settings = ? WHERE login = ?', array('', $this->_content['session_login']));
            }
        }
        $this->DB->Execute('DELETE FROM sessions WHERE id = ?', array($this->SID));
        $this->_content = array();
        $this->_tab_content = array();
        $this->SID = null;
    }

    public function get_persistent_setting($variable)
    {
        if (isset($this->_persistent_settings[$variable])) {
            return $this->_persistent_settings[$variable];
        } else {
            return null;
        }
    }

    public function save_persistent_setting($variable, $content)
    {
        $this->_persistent_settings[$variable] = $content;

        if ($this->autoupdate) {
            $this->_saveSession();
        } else {
            $this->_updated = true;
        }
    }

    public function saveFilter($filter, $module = null, $persistentKeys = null, $reversePersistentKeys = false, $id = null)
    {
        if (empty($module)) {
            $module = $this->_content['module'];
        }

        if (isset($id)) {
            if (!isset($this->_content['filters'][$module]['subfilters'][$id])) {
                $this->_content['filters'][$module]['subfilters'][$id] = array();
            } elseif (isset($this->_content['filters'][$module]['subfilters'][$id]['persistent_filter']) && !isset($filter['persistent_filter'])) {
                $filter['persistent_filter'] = $this->_content['filters'][$module]['subfilters'][$id]['persistent_filter'];
            }
            $this->_content['filters'][$module]['subfilters'][$id] = $filter;
            if (isset($persistentKeys)) {
                $this->_content['prepared_persistent_filters'][$module]['subfilters'][$id] = Utils::filterArrayByKeys(
                    $filter,
                    $persistentKeys,
                    $reversePersistentKeys
                );
            }
        } else {
            $this->_content['filters'][$module] = $filter;
            if (isset($persistentKeys)) {
                $this->_content['prepared_persistent_filters'][$module] = Utils::filterArrayByKeys(
                    $filter,
                    $persistentKeys,
                    $reversePersistentKeys
                );
            }
        }

        if ($this->autoupdate) {
            $this->_saveSession();
        } else {
            $this->_updated = true;
        }
    }

    public function getFilter($module = null, $id = null)
    {
        if (empty($module)) {
            $module = $this->_content['module'];
        }

        if (isset($id)) {
            if (isset($this->_content['prepared_persistent_filters'][$module]['subfilters'][$id])) {
                $filter = $this->_content['prepared_persistent_filters'][$module]['subfilters'][$id];
                unset($this->_content['prepared_persistent_filters'][$module]['subfilters'][$id]);
                return $filter;
            } else {
                return $this->_content['filters'][$module]['subfilters'][$id];
            }
        } elseif (isset($this->_content['filters'][$module]['subfilters'])) {
            if (isset($this->_content['prepared_persistent_filters'][$module]['subfilters'])) {
                $filter = $this->_content['prepared_persistent_filters'][$module]['subfilters'];
                unset($this->_content['prepared_persistent_filters'][$module]['subfilters']);
                return $filter;
            } else {
                return $this->_content['filters'][$module]['subfilters'];
            }
        } elseif (!isset($this->_content['filters'][$module])) {
            return array();
        }

        if (isset($this->_content['prepared_persistent_filters'][$module])) {
            $filter = $this->_content['prepared_persistent_filters'][$module];
            unset($this->_content['prepared_persistent_filters'][$module]);
            return $filter;
        } else {
            return $this->_content['filters'][$module];
        }
    }

    public function removeFilter($module = null, $id = null)
    {
        if (empty($module)) {
            $module = $this->_content['module'];
        }

        if (isset($id)) {
            if (isset($this->_content['filters'][$module]['subfilters'][$id])) {
                unset($this->_content['filters'][$module]['subfilters'][$id]);
                if ($this->autoupdate) {
                    $this->_saveSession();
                } else {
                    $this->_updated = true;
                }
                return true;
            } else {
                return false;
            }
        } else {
            if (isset($this->_content['filters'][$module])) {
                unset($this->_content['filters'][$module]);
                if ($this->autoupdate) {
                    $this->_saveSession();
                } else {
                    $this->_updated = true;
                }
                return true;
            } else {
                return false;
            }
        }
    }

    public function savePersistentFilter($name, $filter, $module = null, $id = null)
    {
        if (empty($module)) {
            $module = $this->_content['module'];
        }

        if (isset($id)) {
            $this->_persistent_settings['filters'][$module]['subfilters'][$id][$name] = $filter;
        } else {
            $this->_persistent_settings['filters'][$module][$name] = $filter;
        }

        if ($this->autoupdate) {
            $this->_saveSession();
        } else {
            $this->_updated = true;
        }
    }

    public function getPersistentFilter($name, $module = null, $id = null)
    {
        if (empty($module)) {
            $module = $this->_content['module'];
        }

        if (isset($id)) {
            if (isset($this->_persistent_settings['filters'][$module]['subfilters'][$id][$name])) {
                return $this->_persistent_settings['filters'][$module]['subfilters'][$id][$name];
            }
            return array();
        } elseif (!isset($this->_persistent_settings['filters'][$module][$name])) {
            return array();
        }

        return $this->_persistent_settings['filters'][$module][$name];
    }

    public function getAllPersistentFilters($module = null)
    {
        if (empty($module)) {
            $module = $this->_content['module'];
        }

        $result = array();

        if (isset($this->_persistent_settings['filters'][$module]['subfilters'])) {
            foreach ($this->_persistent_settings['filters'][$module]['subfilters'] as $filter_id => $filters) {
                if (!isset($result[$filter_id])) {
                    $result[$filter_id] = array();
                }
                foreach ($filters as $filter_name => $filter) {
                    $result[$filter_id][] = array(
                        'text' => $filter_name,
                        'value' => $filter_name,
                    );
                }
            }
            return $result;
        } elseif (!isset($this->_persistent_settings['filters'][$module])) {
            return array();
        }

        foreach ($this->_persistent_settings['filters'][$module] as $filter_name => $filter) {
            $result[] = array(
                'text' => $filter_name,
                'value' => $filter_name,
            );
        }

        return $result;
    }

    public function removePersistentFilter($name, $module = null, $id = null)
    {
        if (empty($module)) {
            $module = $this->_content['module'];
        }

        if (isset($id)) {
            if (isset($this->_persistent_settings['filters'][$module]['subfilters'][$id][$name])) {
                unset($this->_persistent_settings['filters'][$module]['subfilters'][$id][$name]);
                if ($this->autoupdate) {
                    $this->_saveSession();
                } else {
                    $this->_updated = true;
                }
                return true;
            } else {
                return false;
            }
        } elseif (isset($this->_persistent_settings['filters'][$module][$name])) {
            unset($this->_persistent_settings['filters'][$module][$name]);
            if ($this->autoupdate) {
                $this->_saveSession();
            } else {
                $this->_updated = true;
            }
            return true;
        } else {
            return false;
        }
    }

    public function _garbageCollector()
    {
        // deleting sessions with timeout exceeded
        $this->DB->Execute('DELETE FROM sessions WHERE atime < ?NOW? - ? AND mtime < ?NOW? - ?', array($this->timeout, $this->timeout));
        return true;
    }

    public function makeVData()
    {
        foreach (array('REMOTE_ADDR', 'REMOTE_HOST', 'HTTP_USER_AGENT', 'HTTP_VIA', 'HTTP_X_FORWARDED_FOR', 'SERVER_NAME', 'SERVER_PORT') as $vkey) {
            if (isset($_SERVER[$vkey])) {
                $vdata[$vkey] = $_SERVER[$vkey];
            }
        }
        if (isset($vdata)) {
            return $vdata;
        } else {
            return null;
        }
    }

    public function redirect($location)
    {
        $this->close();
        header('Location: '.$location);
        die;
    }
}
