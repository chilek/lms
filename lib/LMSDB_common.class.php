<?

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
 * LMSDB - klasa wspólna.
 */

Class LMSDB_common
{
	var $_version="1.1-cvs";
	var $_revision='$Revision$';
	
	// Driver powinien nadpisaæ t± zmienn± warto¶ci± TRUE, ¿eby
	// funkcja inicjuj±ca baze danych wiedzia³a ¿e driver siê poprawnie
	// za³adowa³
	
	var $_loaded=FALSE;

	// Wewnêtrzne zmienne bazy danych, tj, resource, link, itp.

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
		// zabezpieczmy siê przed inicjowaniem tej klasy samej w sobie
		
		die();

	}
	
	function Connect($dbhost,$dbuser,$dbpasswd,$dbname)
	{
		$this->_driver_connect($dbhost,$dbuser,$dbpasswd);
		$this->_driver_selectdb($dbname);
	}

	function Execute($query)
	{
		return $this->_driver_execute($query);
	}

	function GetAll($query = NULL)
	{
		if($query)
			$this->Execute($query);

		while($row = $this->_driver_fetchrow_assoc())
			$result[] = $row;
		
		return $result;
	}

}
