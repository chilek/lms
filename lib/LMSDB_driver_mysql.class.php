<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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
 * LMSDB_driver_mysqli
 * 
 * MySQL engine driver wrapper for LMS.
 * 
 * @package LMS 
 */
class LMSDB_driver_mysql extends LMSDB_common implements LMSDBDriverInterface {
    
	public $_loaded = TRUE;
	public $_dbtype = 'mysql';

	public function __construct($dbhost, $dbuser, $dbpasswd, $dbname)
	{
		if(!extension_loaded('mysql'))
		{
		        trigger_error('MySQL extension not loaded!', E_USER_WARNING);
			$this->_loaded = FALSE;
	                return;
	        }

	    //$this->_version .= ' ('.preg_replace('/^.Revision: ([0-9.]+).*/','\1',$this->_revision).'/'.preg_replace('/^.Revision: ([0-9.]+).*/','\1','$Revision$').')';
		$this->_version .= '';
		$this->Connect($dbhost, $dbuser, $dbpasswd, $dbname);
	}

	public function _driver_dbversion()
	{
		return mysql_get_server_info();
	}

	public function _driver_connect($dbhost, $dbuser, $dbpasswd, $dbname)
	{
		if($this->_dblink = @mysql_connect($dbhost, $dbuser, $dbpasswd, true))
		{
			$this->_dbhost = $dbhost;
			$this->_dbuser = $dbuser;
			$this->_driver_selectdb($dbname);
		}
		else
		{
			$this->_error = TRUE;
		}

		return $this->_dblink;
	}

	public function _driver_shutdown()
	{
		$this->_loaded = FALSE;
		@mysql_close($this->_dblink); // apparently, mysql_close() is automagicly called after end of the script...
	}
	
	public function _driver_geterror()
	{
		if($this->_dblink)
			return mysql_error($this->_dblink);
		elseif($this->_query)
			return 'We\'re not connected!';
		else
			return mysql_error();
	}

	public function _driver_disconnect()
	{
		return @mysql_close($this->_dblink);
	}
	
	public function _driver_selectdb($dbname)
	{
		if($result = mysql_select_db($dbname, $this->_dblink))
			$this->_dbname = $dbname;
		return $result;
	}

	public function _driver_execute($query)
	{
		$this->_query = $query;

		if($this->_result = @mysql_query($query, $this->_dblink))
			$this->_error = FALSE;
		else
			$this->_error = TRUE;
		return $this->_result;
	}

        public function _driver_multi_execute($query)
        {
                $this->_query = $query;
                $total_result = FALSE;
                $db_errors = array();

                $queries = preg_split("/;+(?=([^'|^\\\']*['|\\\'][^'|^\\\']*['|\\\'])*[^'|^\\\']*[^'|^\\\']$)/", $query);
                foreach ($queries as $q)
                        if (strlen(trim($q)) > 0) {
                                $this->_driver_execute($q);
                                if ($this->_error == TRUE) {
                                    $total_result = TRUE;
                                    $db_errors = array_merge($db_errors, $this->errors);
                                }
                        }
                $this->_error = $total_result;
                $this->errors = $db_errors;
                return $total_result;
        }


	public function _driver_fetchrow_assoc($result = NULL)
	{
		if(! $this->_error)
			return mysql_fetch_array($result ? $result : $this->_result, MYSQL_ASSOC);
		else
			return FALSE;
	}

	public function _driver_fetchrow_num()
	{
		if(! $this->_error)
			return mysql_fetch_array($this->_result, MYSQL_NUM);
		else
			return FALSE;
	}

	public function _driver_affected_rows()
	{
		if(! $this->_error)
			return mysql_affected_rows();
		else
			return FALSE;
	}

	public function _driver_num_rows()
	{
		if(! $this->_error)
			return mysql_num_rows($this->_result);
		else
			return FALSE;
	}
	
	public function _driver_now()
	{
		return 'UNIX_TIMESTAMP()';
	}

	public function _driver_like()
	{
		return 'LIKE';
	}

	public function _driver_concat($input)
	{
		$return = implode(', ', $input);
		return 'CONCAT('.$return.')';
	}

	public function _driver_listtables()
	{
		return $this->GetCol('SELECT table_name FROM information_schema.tables
	                        WHERE table_type = ? AND table_schema = ?',
				array('BASE TABLE', $this->_dbname));
	}

	public function _driver_begintrans()
	{
		return $this->Execute('BEGIN');
	}

	public function _driver_committrans()
	{
		return $this->Execute('COMMIT');
	}
	
	public function _driver_rollbacktrans()
        {
	        return $this->Execute('ROLLBACK');
	}

	public function _driver_locktables($table, $locktype=null)
	{
		$locktype = $locktype ? strtoupper($locktype) : 'WRITE';

		if(is_array($table))
			$this->Execute('LOCK TABLES '.implode(' '.$locktype.', ', $table).' '.$locktype);
		else
			$this->Execute('LOCK TABLES '.$table.' '.$locktype);
	}

	public function _driver_unlocktables()
	{
		$this->Execute('UNLOCK TABLES');
	}

	public function _driver_lastinsertid($table = NULL)
        {
	        return $this->GetOne('SELECT LAST_INSERT_ID()');
	}

	public function _driver_groupconcat($field, $separator = ',')
	{
		return 'GROUP_CONCAT('.$field.' SEPARATOR \''.$separator.'\')';
	}
        
        public function _driver_setencoding($name)
	{
		$this->Execute('SET NAMES ?', array($name));
	}
        
}

?>
