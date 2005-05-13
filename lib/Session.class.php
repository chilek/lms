<?php

/*
 * LMS version 1.7-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

	var $SID = NULL;			// session unique ID
	var $_version = '1.7-cvs';		// library version
	var $_revision = '$Revision$';	// library revision
	var $_content = array();		// session content array
	var $_updated = FALSE;			// indicates that content has
						// been altered
	var $DB = NULL;				// database library object
	var $CONFIG = NULL;			// configuration array
	var $timeout = 600;			// timeout since session will
						// be destroyed
	var $autoupdate = FALSE;		// do automatic update on each
						// save() or save_by_ref() ?
	var $GCprob = 10;			// probality (in percent) of
						// garbage collector procedure
	
	function Session(&$DB, &$CONFIG)
	{
		$this->DB =& $DB;
		$this->CONFIG =& $CONFIG;
		
		if(isset($this->CONFIG['phpui']['timeout']))
			$this->timeout =& $this->CONFIG['phpui']['timeout'];
		
		if(! isset($_COOKIE['SID']))
			$this->_createSession();
		else
			$this->_restoreSession();

		if(rand(1,100) <= $this->GCprob)
			$this->_garbageCollector();
	}

	function close()
	{
		$this->_saveSession();
		$this->SID = NULL;
		$this->_content = array();
	}

	function finish()
	{
		$this->_destroySession();
	}

	function makeSID()
	{
		list($usec, $sec) = split(' ', microtime());
		return md5(uniqid(rand(), true)).sprintf('%09x', $sec).sprintf('%07x', ($usec * 10000000));
	}

	function save($variable, $content)
	{
		$this->_content[$variable] = $content;
		if($this->autoupdate)
			$this->_saveSession();
		else
			$this->_updated = TRUE;
	}

	function save_by_ref($variable, &$content)
	{
		$this->_content[$variable] =& $content;
		if($this->autoupdate)
			$this->_saveSession();
		else
			$this->_updated = TRUE;
	}

	function restore($variable, &$content)
	{
		if(isset($this->_content[$variable]))
			$content = $this->_content[$variable];
		else
			return NULL;
	}

	function get($variable)
	{
		if(isset($this->_content[$variable]))
			return $this->_content[$variable];
		else
			return NULL;
	}

	function remove($variable)
	{
		if(isset($this->_content[$variable]))
		{
			unset($this->_content[$variable]);
			return TRUE;
		}
		else
			return FALSE;
	}

	function is_set($variable)
	{
		if(isset($this->_content[$variable]))
			return TRUE;
		else
			return FALSE;
	}

	function _createSession()
	{
		$this->SID = $this->makeSID();
		$this->_content = array();
		$this->DB->Execute('INSERT INTO sessions (id, ctime, mtime, atime, vdata, content) VALUES (?, ?NOW?, ?NOW?, ?NOW?, ?, ?)', array($this->SID, serialize($this->makeVData()), serialize($this->_content)));
		setcookie('SID', $this->SID);
	}

	function _restoreSession()
	{
		$this->SID = $_COOKIE['SID'];
		if(
				$this->DB->GetOne('SELECT id FROM sessions WHERE id = ?', array($this->SID)) &&
				serialize($this->makeVData()) == $this->DB->GetOne('SELECT vdata FROM sessions WHERE id = ?', array($this->SID)) &&
				$this->_testSessionTimeout()
		  )
		{
			$this->DB->Execute('UPDATE sessions SET atime = ?NOW? WHERE id = ?', array($this->SID));
			$this->_content = unserialize($this->DB->GetOne('SELECT content FROM sessions WHERE id = ?', array($this->SID)));
		}
		else
			$this->_createSession();
	}

	function _testSessionTimeout()
	{
		if($this->DB->GetOne('SELECT * FROM sessions WHERE id = ? AND mtime < ?NOW? - ? AND atime < ?NOW? - ?', array($this->SID, $this->timeout, $this->timeout)))
		{
			$this->_destroySession();
			return FALSE;
		}
		else
			return TRUE;
	}

	function _saveSession()
	{
		if($this->autoupdate || $this->_updated)
			$this->DB->Execute('UPDATE sessions SET content = ?, mtime = ?NOW? WHERE id = ?', array(serialize($this->_content), $this->SID));
	}

	function _destroySession()
	{
		$this->DB->Execute('DELETE FROM sessions WHERE id = ?', array($this->SID));
		$this->_content = array();
		$this->SID = NULL;
	}

	function _garbageCollector()
	{
		
		// usuwa sesje które dosiêgn± timeout
		$this->DB->Execute('DELETE FROM sessions WHERE atime < ?NOW? - ? AND mtime < ?NOW? - ?', array($this->timeout, $this->timeout)); 
		return TRUE;
	}

	function makeVData()
	{
		foreach(array('REMOTE_ADDR', 'REMOTE_HOST', 'HTTP_USER_AGENT', 'HTTP_VIA', 'HTTP_X_FORWARDED_FOR', 'SERVER_NAME', 'SERVER_PORT') as $vkey)
			if(isset($_SERVER[$vkey]))
				$vdata[$vkey] = $_SERVER[$vkey];
		if(isset($vdata))
			return $vdata;
		else
			return NULL;
	}

	function redirect($location)
	{
		$this->close();
		header('Location: '.$location);
		die;
	}
}

?>
