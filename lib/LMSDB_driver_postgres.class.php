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

/*
 * To jest pseudo-driver dla LMSDB, dla bazy danych 'postgres'.
 */

class LMSDB_driver_postgres extends LMSDB_common
{
	var $_loaded = TRUE;
	var $_dbtype = 'postgres';

	function LMSDB_driver_postgres($dbhost,$dbuser,$dbpasswd,$dbname)
	{
		if(!extension_loaded('pgsql'))
		{
			trigger_error('PostgreSQL extension not loaded!', E_USER_WARNING);
			$this->_loaded = FALSE;
			return;
		}

		//$this->_version .= ' ('.preg_replace('/^.Revision: ([0-9.]+).*/','\1',$this->_revision).'/'.preg_replace('/^.Revision: ([0-9.]+).*/','\1','$Revision$').')';
		$this->_version .= '';
		$this->Connect($dbhost,$dbuser,$dbpasswd,$dbname);
	}

	function _driver_dbversion()
	{
		return $this->GetOne("SELECT split_part(version(),' ',2)");
	}

	function _driver_connect($dbhost,$dbuser,$dbpasswd,$dbname)
	{
		$cstring = join(' ',array(
			($dbhost != '' && $dbhost != 'localhost' ? 'host='.$dbhost : ''),
			($dbuser != '' ? 'user='.$dbuser : ''),
			($dbpasswd != '' ? 'password='.$dbpasswd : ''),
			($dbname != '' ? 'dbname='.$dbname : ''),
			'connect_timeout=10'
		));

		if($this->_dblink = @pg_connect($cstring, PGSQL_CONNECT_FORCE_NEW))
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
		$this->_loaded = FALSE;
		@pg_close($this->_dblink);
	}

	function _driver_geterror()
	{
		if($this->_dblink)
                        return pg_last_error($this->_dblink);
                else
            		return 'We\'re not connected!';
	}
	
	function _driver_execute($query)
	{
		$this->_query = $query;

		if($this->_result = @pg_query($this->_dblink,$query))
			$this->_error = FALSE;
		else
			$this->_error = TRUE;
		return $this->_result;
	}

	function _driver_fetchrow_assoc($result = NULL)
	{
		if(! $this->_error)
			return @pg_fetch_array($result ? $result : $this->_result,NULL, PGSQL_ASSOC);
		else
			return FALSE;
	}

	function _driver_fetchrow_num()
	{
		if(! $this->_error)
			return @pg_fetch_array($this->_result,NULL,PGSQL_NUM);
		else
			return FALSE;
	}
	
	function _driver_affected_rows()
	{
		if(! $this->_error)
			return @pg_affected_rows($this->_result);
		else
			return FALSE;
	}

	function _driver_num_rows()
	{
		if(! $this->_error)
			return @pg_num_rows($this->_result);
		else
			return FALSE;
	}

	function _quote_value($input)
	{
		if($input === NULL)
			return 'NULL';
		elseif(gettype($input) == 'string')
			return '\''.@pg_escape_string($this->_dblink, $input).'\'';
		else
			return $input;
	}

	function _driver_now()
	{
		return 'EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer';
	}

	function _driver_like()
	{
		return 'ILIKE';
	}

	function _driver_concat($input)
	{
		return implode(' || ',$input);
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

	function _driver_rollbacktrans()
	{
		return $this->Execute('ROLLBACK');
	}

	// @todo: locktype
	function _driver_locktables($table, $locktype=null)
        {
	        if(is_array($table))
		        $this->Execute('LOCK '.implode(', ', $table));
		else
		        $this->Execute('LOCK '.$table);
	}

	function _driver_unlocktables()
	{
		return TRUE;
	}

	function _driver_lastinsertid($table)
	{
               return $this->GetOne('SELECT currval(\''.$table.'_id_seq\')');
	}

	function _driver_groupconcat($field, $separator = ',')
	{
		return 'array_to_string(array_agg('.$field.'), \''.$separator.'\')';
	}
}

?>
