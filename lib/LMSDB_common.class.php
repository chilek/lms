<?php

/*
 * LMS version 1.4-cvs
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
 * LMSDB - klasa wspólna.
 */

Class LMSDB_common
{
	var $_version = '1.4-cvs';
	var $_revision = '$Revision$';
	
	// Driver powinien nadpisaæ t± zmienn± warto¶ci± TRUE, ¿eby
	// funkcja inicjuj±ca baze danych wiedzia³a ¿e driver siê poprawnie
	// za³adowa³
	
	var $_loaded = FALSE;

	// Wewnêtrzne zmienne bazy danych, tj, resource, link, itp.

	var $_dbtype = 'NONE';
	var $_dblink = NULL;
	var $_dbhost = NULL;
	var $_dbuser = NULL;
	var $_dbname = NULL;
	var $_error = FALSE;
	var $_query = NULL;
	var $_result = NULL;

	var $errors = array();

	function LMSDB_common()
	{
		// zabezpieczmy siê przed inicjowaniem tej klasy samej w sobie
		die();
	}
	
	function Connect($dbhost,$dbuser,$dbpasswd,$dbname)
	{
		// Inicjuje po³±czenie do bazy danych
		return $this->_driver_connect($dbhost,$dbuser,$dbpasswd,$dbname);
	}

	function Destroy()
	{
		return $this->_driver_disconnect();
	}

	function Execute($query, $inputarray = NULL)
	{
	//	echo $this->_query_parser($query,$inputarray).'<HR>';
		if(! $this->_driver_execute($this->_query_parser($query,$inputarray)))
		{
			$this->errors[] = array(
					'query' => $this->_query,
					'error' => $this->_driver_geterror()
					);
		}
		return $this->_driver_affected_rows();
	}

	function GetAll($query = NULL, $inputarray = NULL)
	{
		// zwraca tablicê z ca³ym wynikiem
	
		if($query)
			$this->Execute($query, $inputarray);

		unset($result);

		while($row = $this->_driver_fetchrow_assoc())
			$result[] = $row;
		
		return $result;
	}

	function GetAllByKey($query = NULL, $key = NULL, $inputarray = NULL)
	{
		if($query)
			$this->Execute($query, $inputarray);

		unset($result);

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

		unset($result);

		while($row = $this->_driver_fetchrow_num())
			$result[] = $row[0];
		
		return $result;
	}

	function GetOne($query = NULL, $inputarray = NULL)
	{
		if($query)
			$this->Execute($query, $inputarray);

		unset($result);

		list($result) = $this->_driver_fetchrow_num();

		return $result;
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

	function GetDBVersion()
	{
		return $this->_driver_dbversion();
	}

	function _query_parser($query, $inputarray = NULL)
	{
		// najpierw sparsujmy wszystkie specjalne meta ¶mieci.
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
		// je¿eli baza danych wymaga innego eskejpowania ni¿ to, driver
		// powinien nadpisaæ t± funkcjê

		if($input === NULL)
			return 'NULL';
		elseif(gettype($input) == 'string')
			return '\''.addcslashes($input,"'\\\0").'\'';
		else
			return $input;
	}

	// Funkcje bezpieczeñstwa, tj. na wypadek gdyby driver ich nie
	// zdefiniowa³.

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
