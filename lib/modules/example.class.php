<?

/*
 * LMS version 1.5-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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
	
	var $_version = '1.5-cvs';

	// autor modu³u. dla modu³ów standardowych 'LMS Developers'.
	
	var $_author = 'LMS Developers';

	// Opis. Krótki i zwiêz³y.
	
	var $_description = 'Klasa przyk³adowa';

	// Rewizja pliku, o ile rozwijany by³ w CVS. Je¿eli nie by³,
	// to mo¿na tutaj wpisaæ podwersjê modu³u. CORE i tak usuwa
	// tekst 'Revision:' oraz znaki $ z koñca i pocz±tku linii,
	// za to zewnêtrzne modu³y bêd± mog³y sobie porównywaæ te
	// warto¶ci aby sprawdziæ poprawno¶æ modu³ów.

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

	function _postinit()
	{

		// funkcja ta bêdzie wykonywana po zakoñczeniu ³adowania
		// wszystkich modu³ów. tutaj ka¿dy modu³ mo¿e sobie posprawdzaæ
		// obecno¶æ innych modu³ów i ewentualnie ubiæ LMS'a je¿eli wykryje
		// inny modu³ ;-)
	
		return TRUE;
	}
}

?>
