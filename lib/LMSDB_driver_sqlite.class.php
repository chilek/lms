<?php

/*
 * LMS version 1.5-cvs
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

class LMSDB_driver_sqlite extends LMSDB_common
{
	var $_loaded = TRUE;
	var $_dbtype = 'sqlite';

	function LMSDB_driver_sqlite($dbhost,$dbuser,$dbpasswd,$dbname)
	{
		$this->_version .= ' (core: '.eregi_replace('^.Revision: ([0-9.]+).*','\1',$this->_revision).' / driver: '.$this->_dbtype.' '.eregi_replace('^.Revision: ([0-9.]+).*','\1','$Revision$').')';
		$this->Connect($dbhost,$dbuser,$dbpasswd,$dbname);
	}

	function _driver_dbversion()
	{
		return sqlite_libversion();
	}

	function _driver_connect($dbhost,$dbuser,$dbpasswd,$dbname)
	{
		if($this->_dblink = sqlite_open($dbname))
		{
		//	$this->_dbhost = $dbhost;
		//	$this->_dbuser = $dbuser;
			$this->_dbname = $dbname;
			$this->_error = FALSE;
			// create UDF functions on every connect
			$this->_driver_udf_functions();
		}
		else
			$this->_error = TRUE;
			
		return $this->_dblink;
	}

	function _driver_disconnect()
	{
		return sqlite_close($this->_dblink);
	}

	function _driver_geterror()
	{
		return sqlite_error_string(sqlite_last_error($this->_dblink));
	}
	
	function _driver_execute($query)
	{
		$this->_query = $query;

		if($this->_result = sqlite_query($this->_dblink,$query))
			$this->_error = FALSE;
		else
			$this->_error = TRUE;

		return $this->_result;
	}

	function _driver_fetchrow_assoc()
	{
		if(! $this->_error)
			return sqlite_fetch_array($this->_result,SQLITE_ASSOC);
		else
			return FALSE;
	}

	function _driver_fetchrow_num()
	{
		if(! $this->_error)
			return sqlite_fetch_array($this->_result,SQLITE_NUM);
		else
			return FALSE;
	}
	
	function _driver_affected_rows()
	{
		if(! $this->_error)
			return sqlite_changes($this->_dblink);
		else
			return FALSE;
	}

	function _driver_now()
	{
		return "strftime('%s','now')";
	}

	function _driver_like()
	{
		return 'LIKE';
	}

	function _driver_concat($input)
	{
		$return = implode(' || ',$input);
		return $return;
	}

	function _driver_listtables()
	{
		return $this->GetCol("SELECT tbl_name AS name FROM sqlite_master WHERE type = 'table' AND tbl_name NOT LIKE 'INFORMATION_SCHEMA_%'");
	}

	function _driver_begintrans()
	{
		return $this->Execute('BEGIN');
	}

	function _driver_committrans()
	{
		return $this->Execute('COMMIT');
	}
	
	function _driver_udf_functions()
	{
		sqlite_create_function($this->_dblink, 'inet_ntoa','long2ip',1);
		sqlite_create_function($this->_dblink, 'inet_aton','ip2long',1);
		sqlite_create_function($this->_dblink, 'upper','strtoupper',1);
		sqlite_create_function($this->_dblink, 'lower','strtolower',1);
		sqlite_create_function($this->_dblink, 'floor','floor',1);
		sqlite_create_function($this->_dblink, 'random','sql_random');
	}
}

?>
