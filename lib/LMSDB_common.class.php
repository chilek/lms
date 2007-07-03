<?php

/*
 * LMS version 1.9-cvs
 *
 *  (C) Copyright 2001-2007 LMS Developers
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
 * LMSDB - klasa wsp�lna.
 */

Class LMSDB_common
{
	var $_version = '1.9-cvs';
	var $_revision = '$Revision$';
	
	// Driver powinien nadpisa� t� zmienn� warto�ci� TRUE, �eby
	// funkcja inicjuj�ca baze danych wiedzia�a �e driver si� poprawnie
	// za�adowa�
	
	var $_loaded = FALSE;

	// Wewn�trzne zmienne bazy danych, tj, resource, link, itp.

	var $_dbtype = 'NONE';
	var $_dblink = NULL;
	var $_dbhost = NULL;
	var $_dbuser = NULL;
	var $_dbname = NULL;
	var $_error = FALSE;
	var $_query = NULL;
	var $_result = NULL;

	var $errors = array();
	var $debug = FALSE;

	function LMSDB_common()
	{
		// zabezpieczmy si� przed inicjowaniem tej klasy samej w sobie
		die();
	}
	
	function Connect($dbhost,$dbuser,$dbpasswd,$dbname)
	{
		if(method_exists($this, '_driver_shutdown'))
			register_shutdown_function(array($this, '_driver_shutdown'));
		
		// Inicjuje po��czenie do bazy danych, nie musimy zwraca�
		// dblinka na zewn�trz gdy� jest to niepotrzebne.
		
		if($this->_driver_connect($dbhost,$dbuser,$dbpasswd,$dbname))
			return $this->_dblink;
		else
		{
			$this->errors[] = array(
					'query' => 'database connect',
					'error' => $this->_driver_geterror(),
					);
			return FALSE;
		}
	}

	function Destroy()
	{
		return $this->_driver_disconnect();
	}

	function Execute($query, $inputarray = NULL)
	{
		if(! $this->_driver_execute($this->_query_parser($query, $inputarray)))
			$this->errors[] = array(
					'query' => $this->_query,
					'error' => $this->_driver_geterror()
					);
		elseif($this->debug)
			$this->errors[] = array(
					'query' => $this->_query,
					'error' => 'DEBUG: NOERROR'
					);
		return $this->_driver_affected_rows();
	}

	function GetAll($query = NULL, $inputarray = NULL)
	{
		if($query)
			$this->Execute($query, $inputarray);

		$result = NULL;

		while($row = $this->_driver_fetchrow_assoc())
			$result[] = $row;
		
		return $result;
	}

	function GetAllByKey($query = NULL, $key = NULL, $inputarray = NULL)
	{
		if($query)
			$this->Execute($query, $inputarray);

		$result = NULL;

		while($row = $this->_driver_fetchrow_assoc())
			$result[$row[$key]] = $row;

		return $result;
	}

	function GetRow($query = NULL, $inputarray = NULL)
	{
		if($query)
			$this->Execute($query, $inputarray);

		return $this->_driver_fetchrow_assoc();
	}

	function GetCol($query = NULL, $inputarray = NULL)
	{
		if($query)
			$this->Execute($query, $inputarray);

		$result = NULL;

		while($row = $this->_driver_fetchrow_num())
			$result[] = $row[0];
		
		return $result;
	}

	function GetOne($query = NULL, $inputarray = NULL)
	{
		if($query)
			$this->Execute($query, $inputarray);

		$result = NULL;

		list($result) = $this->_driver_fetchrow_num();

		return $result;
	}

	// with Exec() & FetchRow() we can do big results looping
	// in less memory consumptive way than using GetAll() & foreach()
	function Exec($query, $inputarray = NULL)
	{
		if(! $this->_driver_execute($this->_query_parser($query, $inputarray)))
			$this->errors[] = array(
					'query' => $this->_query,
					'error' => $this->_driver_geterror()
					);
		elseif($this->debug)
			$this->errors[] = array(
					'query' => $this->_query,
					'error' => 'DEBUG: NOERROR'
					);
		
		if($this->_driver_num_rows())
			return $this->_result;
		else
			return NULL;
	}

	function FetchRow($result)
	{
		return $this->_driver_fetchrow_assoc($result);
	}
	
	function Concat()
	{
		return $this->_driver_concat(func_get_args());
	}

	function Now()
	{
		return $this->_driver_now();
	}

	function ListTables()
	{
		return $this->_driver_listtables();
	}

	function BeginTrans()
	{
		return $this->_driver_begintrans();
	}

	function CommitTrans()
	{
		return $this->_driver_committrans();
	}

	function LockTables($table)
	{
		return $this->_driver_locktables($table);
	}

	function UnLockTables()
	{
		return $this->_driver_unlocktables();
	}

	function GetDBVersion()
	{
		return $this->_driver_dbversion();
	}

	function GetLastInsertID($table = NULL)
	{
		return $this->_driver_lastinsertid($table);
	}

	function _query_parser($query, $inputarray = NULL)
	{
		// najpierw sparsujmy wszystkie specjalne meta �mieci.
		$query = eregi_replace('\?NOW\?',$this->_driver_now(),$query);
		$query = eregi_replace('\?LIKE\?',$this->_driver_like(),$query);

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

	function _quote_value($input)
	{
		// je�eli baza danych wymaga innego eskejpowania ni� to, driver
		// powinien nadpisa� t� funkcj�

		if($input === NULL)
			return 'NULL';
		elseif(gettype($input) == 'string')
			return '\''.addcslashes($input,"'\\\0").'\'';
		else
			return $input;
	}

	// Funkcje bezpiecze�stwa, tj. na wypadek gdyby driver ich nie
	// zdefiniowa�.

	function _driver_now()
	{
		return time();
	}

	function _driver_like()
	{
		return 'LIKE';
	}

}

?>
