<?php

/*
 * LMS version 1.1-cvs
 *
 *  (C) Copyright 2001-2003 LMS Developers
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
	var $_version="1.1-cvs";
	var $_revision='$Revision$';
	
	// Driver powinien nadpisa� t� zmienn� warto�ci� TRUE, �eby
	// funkcja inicjuj�ca baze danych wiedzia�a �e driver si� poprawnie
	// za�adowa�
	
	var $_loaded=FALSE;

	// Wewn�trzne zmienne bazy danych, tj, resource, link, itp.

	var $_dbtype='NONE';
	var $_dblink=NULL;
	var $_dbhost=NULL;
	var $_dbuser=NULL;
	var $_dbname=NULL;
	var $_error=NULL;
	var $_query=NULL;
	var $_result=NULL;

	function LMSDB_common()
	{
		// zabezpieczmy si� przed inicjowaniem tej klasy samej w sobie
		
		die();

	}
	
	function Connect($dbhost,$dbuser,$dbpasswd,$dbname)
	{

		// Inicjuje po��czenie do bazy danych
	
		return $this->_driver_connect($dbhost,$dbuser,$dbpasswd,$dbname);
	}

	function Destroy()
	{
		return $this->_driver_disconnect();
	}

	function Execute($query, $inputarray = NULL)
	{

		// wykonuje query sql'owe, jednocze�nie je parsuj�c
		$this->_driver_execute($this->_query_parser($query,$inputarray));
		// i zwraca ilo�� zmodyfikowanych wierszy
		return $this->_driver_affected_rows();
	}

	function GetAll($query = NULL, $inputarray = NULL)
	{

		// zwraca tablic� z ca�ym wynikiem
	
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

/* 
 * $Log$
 * Revision 1.17  2003/09/12 22:22:52  alec
 * Execute zwraca ilo�� zmodyfikowanych wierszy dla zapyta� UPDATE, DELETE, INSERT
 *
 * Revision 1.16  2003/09/10 00:16:19  lukasz
 * - LMSDB::Destroy();
 *
 * Revision 1.15  2003/08/28 21:07:21  lukasz
 * - added support for transactions
 *
 * Revision 1.14  2003/08/27 19:25:18  lukasz
 * - unset result before returning it
 *
 * Revision 1.13  2003/08/24 13:10:26  lukasz
 * - added few comments
 * - s/<?/<?php/g
 *
 * Revision 1.12  2003/08/24 00:59:29  lukasz
 * - LMSDB: GetAllByKey($query, $key, $inputarray)
 * - LMS: more fixes for new DAL
 *
 * Revision 1.11  2003/08/22 13:15:17  lukasz
 * - ListTables()
 *
 * Revision 1.10  2003/08/19 01:01:57  lukasz
 * - added Now() and Concat(), fixed Connect() (doesn't invoke _driver_selectdb(), because pgsql doesn't have _driver_selectdb())
 *
 * Revision 1.9  2003/08/18 16:47:37  lukasz
 * - once again fixed CVS tags
 *
 * Revision 1.8  2003/08/18 16:46:50  lukasz
 * - fixed CVS tags
 * - beta state - need testing
 *
 */

?>
