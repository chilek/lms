<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

class Session {

	public $SID = NULL;			// session unique ID
	public $_version = '1.11-git';		// library version
	public $_revision = '$Revision$';	// library revision
	public $_content = array();		// session content array
	public $_updated = FALSE;			// indicates that content has
						// been altered
	public $DB = NULL;				// database library object
	public $timeout = 600;			// timeout since session will
						// be destroyed
	public $autoupdate = FALSE;		// do automatic update on each
						// save() or save_by_ref() ?
	public $GCprob = 10;			// probality (in percent) of
						// garbage collector procedure

	public function __construct(&$DB, $timeout = 0) {
		$this->DB =& $DB;

		if (isset($timeout) && $timeout != 0)
			$this->timeout = $timeout;

		if (!isset($_COOKIE['SID']))
			$this->_createSession();
		else
			$this->_restoreSession();

		if (rand(1, 100) <= $this->GCprob)
			$this->_garbageCollector();
	}

	public function close()
	{
		$this->_saveSession();
		$this->SID = NULL;
		$this->_content = array();
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

	public function restore_user_settings() {
		$settings = $this->DB->GetOne('SELECT settings FROM users WHERE login = ?', array($this->_content['session_login']));
		if (!empty($settings))
			$this->_content = array_merge($this->_content, unserialize($settings));
	}

	public function save($variable, $content) {
		$this->_content[$variable] = $content;

		if ($variable == 'session_login')
			$this->restore_user_settings();

		if ($this->autoupdate)
			$this->_saveSession();
		else
			$this->_updated = TRUE;
	}

	public function save_by_ref($variable, &$content)
	{
		$this->_content[$variable] =& $content;
		if($this->autoupdate)
			$this->_saveSession();
		else
			$this->_updated = TRUE;
	}

	public function restore($variable, &$content)
	{
		if(isset($this->_content[$variable]))
			$content = $this->_content[$variable];
		else
			$content = NULL;
	}

	public function get($variable)
	{
		if(isset($this->_content[$variable]))
			return $this->_content[$variable];
		else
			return NULL;
	}

	public function remove($variable)
	{
		if(isset($this->_content[$variable]))
		{
			unset($this->_content[$variable]);
			if($this->autoupdate)
				$this->_saveSession();
			else
				$this->_updated = TRUE;
			return TRUE;
		}
		else
			return FALSE;
	}

	public function is_set($variable)
	{
		if(isset($this->_content[$variable]))
			return TRUE;
		else
			return FALSE;
	}

	public function _createSession() {
		$this->SID = $this->makeSID();
		$this->_content = array();
		$this->DB->Execute('INSERT INTO sessions (id, ctime, mtime, atime, vdata, content) VALUES (?, ?NOW?, ?NOW?, ?NOW?, ?, ?)', array($this->SID, serialize($this->makeVData()), serialize($this->_content)));
		setcookie('SID', $this->SID);
	}

	public function _restoreSession() {
		$this->SID = $_COOKIE['SID'];

		$row = $this->DB->GetRow('SELECT *, ?NOW? AS tt FROM sessions WHERE id = ?', array($this->SID));

		if ($row && serialize($this->makeVData()) == $row['vdata']) {
			if (($row['mtime'] < $row['tt'] - $this->timeout) && ($row['atime'] < $row['tt'] - $this->timeout))
				$this->_destroySession();
			else {
				if (!isset($_POST['xjxfun']))
					$this->DB->Execute('UPDATE sessions SET atime = ?NOW? WHERE id = ?', array($this->SID));
				$this->_content = unserialize($row['content']);
				$this->restore_user_settings();
				return;
			}
		} elseif ($row)
			$this->_destroySession();

		$this->_createSession();
	}

	public function _saveSession() {
		static $session_variables = array('session_id' => true, 'session_login' => true,
			'session_logname' => true, 'session_last' => true, 'session_lastip' => true,
			'session_smsauthenticated' => true, 'backto' => true, 'lastmodule' => true);

		if ($this->autoupdate || $this->_updated) {
			$session_content = array_intersect_key($this->_content, $session_variables);
			$settings_content = array_diff_key($this->_content, $session_variables);
			$this->DB->Execute('UPDATE sessions SET content = ?, mtime = ?NOW? WHERE id = ?',
				array(serialize($session_content), $this->SID));
			$this->DB->Execute('UPDATE users SET settings = ? WHERE login = ?',
				array(serialize($settings_content), $this->_content['session_login']));
		}
	}

	public function _destroySession()
	{
		$this->DB->Execute('DELETE FROM sessions WHERE id = ?', array($this->SID));
		$this->_content = array();
		$this->SID = NULL;
	}

	public function _garbageCollector()
	{
		// deleting sessions with timeout exceeded
		$this->DB->Execute('DELETE FROM sessions WHERE atime < ?NOW? - ? AND mtime < ?NOW? - ?', array($this->timeout, $this->timeout)); 
		return TRUE;
	}

	public function makeVData()
	{
		foreach(array('REMOTE_ADDR', 'REMOTE_HOST', 'HTTP_USER_AGENT', 'HTTP_VIA', 'HTTP_X_FORWARDED_FOR', 'SERVER_NAME', 'SERVER_PORT') as $vkey)
			if(isset($_SERVER[$vkey]))
				$vdata[$vkey] = $_SERVER[$vkey];
		if(isset($vdata))
			return $vdata;
		else
			return NULL;
	}

	public function redirect($location)
	{
		$this->close();
		header('Location: '.$location);
		die;
	}
}

?>
