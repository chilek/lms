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
 * To jest pseudo-driver dla LMSDB, dla bazy danych 'mysql'.
 */

class LMSDB_driver_mysql extends LMSDB_common
{
	var $_loaded = TRUE;
	var $_dbtype = 'mysql';

	function LMSDB_driver_mysql($dbhost,$dbuser,$dbpasswd,$dbname)
	{
		$this->_version .= ' (core: '.eregi_replace('^.Revision: ([0-9.]+).*','\1',$this->_revision).' / driver:'.$_dbtype.' '.eregi_replace('^.Revision: ([0-9.]+).*','\1','$Revision$').')';
		$this->Connect($dbhost,$dbuser,$dbpasswd,$dbname);
	}

	function Connect($dbhost=NULL,$dbuser=NULL,$dbpasswd=NULL,$dbname=NULL)
	{
		if($dbhost)
			$this->_dbhost = $dbhost;
		
		if($dbuser)
			$this->_dbuser = $dbuser;
		
		if($dbpasswd)
			$this->_dbpasswd = $dbpasswd;
		
		if($dbname)
			$this->_dbname = $dbname;
		
		$this->_dblink = mysql_connect($this->_dbhost,$this->_dbuser,$this->_dbpasswd);

		mysql_select_db($this->_dbname,$this->_dblink);
	}

	function Execute($query=NULL)
	{
		if($query)
			$this->_query = $query;
		
		$this->_result = mysql_query($this->_query,$this->_dblink);
		
		return $this->_result;
	}

	function GetRow($query=NULL)
	{
		if($query)
			$this->Execute($query);

		return mysql_fetch_array($this->_result,MYSQL_ASSOC);
	}		
	
	function GetAll($query=NULL)
	{
		if($query)
			$this->_query = $query;
		
		$this->Execute();
		
		while($row = $this->GetRow())
			$result[] = $row;
		return $result;			
	}
		
}
?>
