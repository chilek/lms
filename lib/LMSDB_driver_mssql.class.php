<?php

/*
 * LMS version 1.5-cvs
 *
 *  (C) Copyright 2001-2004 LMS Developers
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
 * To jest pseudo-driver dla LMSDB dla bazy danych 'mssql'.
 */

class LMSDB_driver_mssql extends LMSDB_common
{
	var $_loaded = TRUE;
	var $_dbtype = 'mssql';

	function LMSDB_driver_mssql($dbhost,$dbuser,$dbpasswd,$dbname)
	{
		$this->_version .= ' (core: '.eregi_replace('^.Revision: ([0-9.]+).*','\1',$this->_revision).' / driver: '.$this->_dbtype.' '.eregi_replace('^.Revision: ([0-9.]+).*','\1','$Revision$').')';
		$this->Connect($dbhost,$dbuser,$dbpasswd,$dbname);
	}

	function _driver_dbversion()
	{
		$version = $this->GetOne('SELECT @@VERSION');
		preg_match('/^Microsoft SQL Server [0-9.]*(.*).*/', $version, $result);
		return $result[1];
	}

	function _driver_connect($dbhost,$dbuser,$dbpasswd,$dbname)
	{
		if($this->_dblink = mssql_connect("$dbhost:1433",$dbuser,$dbpasswd))
		{
			$this->_dbhost = $dbhost;
			$this->_dbuser = $dbuser;
			$this->_driver_selectdb($dbname);
		}
		else
			$this->_error = TRUE;
		return $this->_dblink;
	}

	function _driver_disconnect()
	{
		return mssql_close($this->_dblink);
	}

	function _driver_geterror()
	{
		return mssql_get_last_message();
	}

	function _driver_selectdb($dbname)
	{
		if($result = mssql_select_db($dbname,$this->_dblink))
			$this->_dbname = $dbname;
		return $result;
	}
	
	function _driver_execute($query)
	{
		$this->_query = $query;
//echo $query."<BR>";
		if($this->_result = mssql_query($query, $this->_dblink))
			$this->_error = FALSE;
		else
			$this->_error = TRUE;
		return $this->_result;
	}

	function _driver_fetchrow_assoc()
	{
		if(! $this->_error)
			return mssql_fetch_array($this->_result, MSSQL_ASSOC);
		else
			return FALSE;
	}

	function _driver_fetchrow_num()
	{
		if(! $this->_error)
			return mssql_fetch_array($this->_result, MSSQL_NUM);
		else
			return FALSE;
	}
	
	function _driver_affected_rows()
	{
		if(! $this->_error)
		{
			if(!eregi('^(UPDATE)|(INSERT)|(DELETE)|(ALTER)|(BEGIN)|(COMMIT)|(CREATE)|(IF).*',$this->_query))
				return mssql_num_rows($this->_result);
			else
			{
				$rows = mssql_query('SELECT @@ROWCOUNT AS rows',$this->_dblink);
				return mssql_result($rows,0,'rows');
			}
		}
		else
			return FALSE;
	}

	function _driver_now()
	{
		return "DATEDIFF(ss,'1970-01-01 00:00',CURRENT_TIMESTAMP)";
	}

	function _driver_like()
	{
		return 'LIKE';
	}

	function _driver_concat($input)
	{
		return implode(' + ',$input);
	}

	function _driver_listtables()
	{
		return $this->GetCol("SELECT name FROM sysobjects WHERE type='U'");
	}

	function _driver_begintrans()
	{
		return $this->Execute('BEGIN TRANSACTION');
	}

	function _driver_committrans()
	{
		return $this->Execute('COMMIT');
	}

	function _query_parser($query, $inputarray = NULL)
	{
		// najpierw sparsujmy wszystkie specjalne meta ¶mieci.
		$query = eregi_replace('\?NOW\?',$this->_driver_now(),$query);
		$query = eregi_replace('\?LIKE\?',$this->_driver_like(),$query);
		$query = eregi_replace('inet_ntoa\(',$this->_dbuser.'.inet_ntoa(',$query);
		$query = eregi_replace('inet_aton\(',$this->_dbuser.'.inet_aton(',$query);
		
		if(eregi('^.*LIMIT 1$',$query))
		{
			$query = eregi_replace('LIMIT 1','',$query);
			$query = eregi_replace('^SELECT','SELECT TOP 1',$query);
		}
		
		if($inputarray)
		{
			$queryelements = explode("\0",str_replace('?',"?\0",$query));
			$query = '';
			foreach($queryelements as $queryelement)
			{
				if(strpos($queryelement,'?') !== FALSE)
				{
					list($key,$value) = each($inputarray);
					$queryelement = str_replace('?',$this->_quote_value($value),$queryelement);
				}
				$query .= $queryelement;
			}
		}
		return $query;
	}
}

?>
