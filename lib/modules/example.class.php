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
 * To jest przyk�adowa klasa modularna.
 * Taki zal���k budowania modu��w w LMS'ie.
 *
 * Klasa ta nic nie robi ani nie dzia�a.
 */

class example
{
	// wersja modu�u. mo�e by� cokolwiek, byle by z sensem.
	// dla modu��w LMS'a wersja to werjsa og�lna LMS'a.
	
	var $_version = '1.5-cvs';

	// autor modu�u. dla modu��w standardowych 'LMS Developers'.
	
	var $_author = 'LMS Developers';

	// Opis. Kr�tki i zwi�z�y.
	
	var $_description = 'Klasa przyk�adowa';

	// Rewizja pliku, o ile rozwijany by� w CVS. Je�eli nie by�,
	// to mo�na tutaj wpisa� podwersj� modu�u. CORE i tak usuwa
	// tekst 'Revision:' oraz znaki $ z ko�ca i pocz�tku linii,
	// za to zewn�trzne modu�y b�d� mog�y sobie por�wnywa� te
	// warto�ci aby sprawdzi� poprawno�� modu��w.

	var $_revision = '$Revision$';
	
	function example(&$core)
	{
		// do konstruktora klasy b�dzie zawsze przekazywana referencja
		// do obiektu g��nego LMS'a. Powinna ona by� inicjowana w taki
		// oto spos�b:
		
		$this->core = &$core;

		// Teraz odwo�ywanie si� do g��wne obiektu LMS'a powinno si�
		// odbywa� w spos�b $this->core->funkcja(). Odwo�anie do modu�u
		// np. 'user', w spos�b $this->core->user->funkcja(), chocia�
		// ca�kiem mo�liwe �e b�dzie mo�na $this->user->funkcja().
		
		return TRUE;
	}

	function _postinit()
	{

		// funkcja ta b�dzie wykonywana po zako�czeniu �adowania
		// wszystkich modu��w. tutaj ka�dy modu� mo�e sobie posprawdza�
		// obecno�� innych modu��w i ewentualnie ubi� LMS'a je�eli wykryje
		// inny modu� ;-)
	
		return TRUE;
	}
}

?>
