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

/*
 * To jest pseudo-driver dla LMSDB, dla bazy danych 'postgres'.
 */

class LMSDB_driver_postgres extends LMSDB_common
{
	var $_loaded = TRUE;
	var $_dbtype = 'postgres';

	function LMSDB_driver_postgres($dbhost,$dbuser,$dbpasswd,$dbname)
	{
		$this->_version .= ' (core: '.eregi_replace('^.Revision: ([0-9.]+).*','\1',$this->_revision).' / driver: '.$this->_dbtype.' '.eregi_replace('^.Revision: ([0-9.]+).*','\1','$Revision$').')';
		$this->Connect($dbhost,$dbuser,$dbpasswd,$dbname);
	}

	function _driver_dbversion()
	{
		list($var1, $var2) = explode(' ',$this->GetOne("SELECT version()"));
		return $var2;
	}

	function _driver_connect($dbhost,$dbuser,$dbpasswd,$dbname)
	{
		$cstring = join(' ',array(
			($dbhost != '' ? 'host='.$dbhost : ''),
			($dbuser != '' ? 'user='.$dbuser : ''),
			($dbpasswd != '' ? 'password='.$dbpasswd : ''),
			($dbname != '' ? 'dbname='.$dbname : '')
		));
		
		if($this->_dblink = pg_connect($cstring))
		{
			$this->_dbhost = $dbhost;
			$this->_dbuser = $dbuser;
			$this->_dbname = $dbname;
			$this->_error = FALSE;
		}
		else
			$this->_error = TRUE;
		return $this->_dblink;
	}

	function _driver_disconnect()
	{
		return pg_close($this->_dblink);
	}

	function _driver_geterror()
	{
		return pg_last_error($this->_dblink);
	}
	
	function _driver_execute($query)
	{
		$this->_query = $query;
		if($this->_result = pg_query($this->_dblink,$query))
			$this->_error = FALSE;
		else
			$this->_error = TRUE;
		return $this->_result;
	}

	function _driver_fetchrow_assoc()
	{
		if(! $this->_error)
			return pg_fetch_array($this->_result,NULL,PGSQL_ASSOC);
		else
			return FALSE;
	}

	function _driver_fetchrow_num()
	{
		if(! $this->_error)
			return pg_fetch_array($this->_result,NULL,PGSQL_NUM);
		else
			return FALSE;
	}
	
	function _driver_affected_rows()
	{
		if(! $this->_error)
			return pg_affected_rows($this->_result);
		else
			return FALSE;
	}

	function _driver_now()
	{
		return 'EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))';
	}

	function _driver_like()
	{
		return 'ILIKE';
	}

	function _driver_concat($input)
	{
		$return = implode(' || ',$input);
		return $return;
	}

	function _driver_listtables()
	{
		return $this->GetCol('SELECT relname AS name FROM pg_class WHERE relkind = \'r\' and relname !~ \'^pg_\' and relname !~ \'^sql_\'');
	}

	function _driver_begintrans()
	{
		return $this->Execute('BEGIN');
	}

	function _driver_committrans()
	{
		return $this->Execute('COMMIT');
	}
}

?>
