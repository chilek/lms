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
 * To jest przyk³adowa klasa modularna.
 * Taki zal±¿êk budowania modu³ów w LMS'ie.
 *
 * Klasa ta nic nie robi ani nie dzia³a.
 */

class example
{
	// wersja modu³u. mo¿e byæ cokolwiek, byle by z sensem.
	// dla modu³ów LMS'a wersja to werjsa ogólna LMS'a.
	
	var $_version = '1.1-cvs';

	// autor modu³u. dla modu³ów standardowych 'LMS Developers'.
	
	var $_author = 'LMS Developers';

	// Opis. Krótki i zwiêz³y.
	
	var $_description = 'Klasa przyk³adowa';

	// Rewizja pliku, o ile rozwijany by³ w CVS. W przeciwnym wypadku
	// mo¿na pomin±æ, albo ustawiæ NULL.

	var $_revision = '$Revision$';
	
	function example(&$core)
	{
		// do konstruktora klasy bêdzie zawsze przekazywana referencja
		// do obiektu g³ónego LMS'a. Powinna ona byæ inicjowana w taki
		// oto sposób:
		
		$this->core = &$core;

		// Teraz odwo³ywanie siê do g³ówne obiektu LMS'a powinno siê
		// odbywaæ w sposób $this->core->funkcja(). Odwo³anie do modu³u
		// np. 'user', w sposób $this->core->user->funkcja(), chocia¿
		// ca³kiem mo¿liwe ¿e bêdzie mo¿na $this->user->funkcja().
		
		return TRUE;
	}
}

?>
