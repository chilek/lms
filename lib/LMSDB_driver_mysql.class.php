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
		$this->_version .= ' (core: '.eregi_replace('^.Revision: ([0-9.]+).*','\1',$this->_revision).' / driver: '.$this->_dbtype.' '.eregi_replace('^.Revision: ([0-9.]+).*','\1','$Revision$').')';
		$this->Connect($dbhost,$dbuser,$dbpasswd,$dbname);
	}

	function _driver_connect($dbhost,$dbuser,$dbpasswd)
	{
		if($this->_dblink = mysql_connect($dbaddr,$dbuser,$dbpasswd))
		{
			$this->_dbhost = $dbhost;
			$this->_dbuser = $dbuser;
		}
		return $this->_dblink;
	}
	
	function _driver_selectdb($dbname)
	{
		if($result = mysql_select_db($dbname,$this->_dblink))
			$this->_dbname = $dbname;
		return $result;
	}

	function _driver_execute($query)
	{
		if($this->_result = mysql_query($query,$this->_dblink))
		{
			$this->_query = $query;
			$this->_error = FALSE;
		}else
			$this->_error = mysql_error();

		return $this->_result;
	}

	function _driver_fetchrow_assoc()
	{
		if(! $this->_error)
			return mysql_fetch_array($this->_result,MYSQL_ASSOC);
		else
			return FALSE;
	}

	function _driver_fetchrow_num()
	{
		if(! $this->_error)
			return mysql_fetch_array($this->_result,MYSQL_NUM);
		else
			return FALSE;
	}

	function _driver_now()
	{
		return 'UNIX_TIMESTAMP()';
	}

	function _driver_like()
	{
		return 'LIKE';
	}
}

/* $Log$
/* Revision 1.6  2003/08/18 16:46:50  lukasz
/* - fixed CVS tags
/* - beta state - need testing
/*
 */

?>	
