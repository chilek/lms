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
	var $_dbpasswd=NULL;
	var $_dbname=NULL;
	var $_error=NULL;
	var $_query=NULL;
	var $_result=NULL;

	function Now()
	{
		// Generalnie driver do bazy powinien nadpisaæ t± funkcjê
		// czym¶ co zwróci odpowiedni± komendê SQL'a, na przyk³ad
		// UNIX_TIMESTAMP() dla MySQL. Je¿eli jednak dana baza danych
		// nie posiada podobnej funkcji (or kto¶ kto pisa³ driver ma
		// sklerozê, Now() zwróci aktualny timestamp
		
		return time();
	}

	function ErrorEvent($errormsg)
	{
		$this->_error = $errormsg;
		trigger_error($errormsg);
	}
}
