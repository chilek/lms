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

	function _driver_connect($dbhost,$dbuser,$dbpasswd,$dbname)
	{
		if($this->_dblink = pg_connect(($host != '' ? 'host='.$dbhost : '' ).' user='.$dbuser.' password='.$dbpasswd.' dbname='.$dbname))
		{
			$this->_dbhost = $dbhost;
			$this->_dbuser = $dbuser;
			$this->_dbname = $dbname;
			return $this->_dblink;
		}
		else
			return FALSE;
	}
	
	function _driver_execute($query)
	{
		if($this->_result = pg_query($this->_dblink,$query))
		{
			$this->_query = $query;
			$this->_error = FALSE;
		}
		else
			$this->_error = pg_last_error($this->_dblink);

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
		return $this->GetCol('SELECT relname AS name FROM pg_class WHERE relkind = \'r\' and relname !~ \'^pg_\'');
	}		
		
}

/* 
 * $Log$
 * Revision 1.6  2003/08/24 14:06:35  lukasz
 * - fixed bug #0000061 - added missing param for pg_fetch_array (but it's
 *   propably only workarround)
 *
 * Revision 1.5  2003/08/24 13:55:16  lukasz
 * - fix with local socket connection
 *
 * Revision 1.4  2003/08/24 13:12:54  lukasz
 * - massive attack: s/<?/<?php/g - that was causing problems on some fucked
 *   redhat's :>
 *
 * Revision 1.3  2003/08/22 18:03:57  lukasz
 * - added _driver_listtables()
 *
 * Revision 1.2  2003/08/22 13:16:23  lukasz
 * - fixed _driver_concat() (PG uses '||' not '+' as concat sign)
 *
 * Revision 1.1  2003/08/19 01:00:13  lukasz
 * - untested driver for pgsql
 *
 */

?>
