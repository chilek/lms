<?php

/*
 * LMS version 1.5-cvs
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

$layout['pagetitle'] = "Informacje o prawach autorskich";

$authors = array(
		
		'alec' => array(
			'realname' => 'Aleksander Machniak',
			'www' => 'www.alec.pl',
			'info' => 'kod PHP, C, Perl, HTML, JavaScript, grafika, dokumentacja, PostgreSQL, SQLite'
		     ),

		'Baseciq' => array(
			'realname' => '£ukasz Jaros³aw Mozer',
			'www' => 'www.baseciq.org',
			'info' => 'pomys³odawca i maintainer, kod PHP, Perl, design, HTML, JavaScript, CSS, grafika, dokumentacja, projekt strony WWW projektu'
			),

		'chilek' => array(
			'info' => 'kod PHP, Perl',
			'realname' => 'Tomasz Chilinski',
			),

		'hunter' => array(
			'info' => 'kod PHP, Perl',
			'realname' => 'Krzysztof Drewicz',
			),

		'Lexx' => array(
			'info' => 'kod PHP, C, dokumentacja, projekt strony WWW projektu',
			'www' => 'www.lexx.w.pl',
			'realname' => 'Marcin Król',
		     ),
	
		);

$others = array(

		'agaran' => array(
			'info' => 'Kod i support Perl',
			'realname' => 'Maciej Pijanka',
			),
		
		'kflis' => array(
			'info' => 'Betatesting, grafika',
			'www' => 'www.kflis.net',
			'realname' => 'Kuba Flis',
			),

		'dzwonus' => array(
			'info' => 'Betatesting, wspó³autor pomys³u',
			'realname' => 'Tomasz Dzwonkowski',
			),

		'victus' => array(
			'info' => 'Betatesting, wspó³autor pomys³u',
			'realname' => 'Sebastian Frasunkiewicz',
			),

		'shasta' => array(
			'info' => 'Support MySQL, strona WWW',
			'realname' => 'Kuba Jankowski',
			'www' => 's.atn.pl',
			),

		'Bob_R' => array(
			'info' => 'Support CSS, HTML, JavaScript',
			'realname' => 'Pawe³ Czerski',
			'www' => 'plug.atn.pl/~bober/',
			),

		'sickone' => array(
			'realname' => 'Pawe³ Kisiela',
			'info' => 'Support CSS, HTML, JavaScript',
			'www' => 'gamechannel.int.pl',
			),

		'DziQs' => array(
			'realname' => 'Micha³ Zapalski',
			'info' => 'Kod PHP i Perl',
			),

		'Pierzak' => array(
			'realname' => 'Piotr M.',
			'info' => 'Projekt logo',
			),
		);
				
$betatesters = array(
		'byko' => array(
			'realname' => 'Grzegorz Cichowski',
			'www' => 'byko.pawlacz.net',
			),
		);
		
		
$SMARTY->assign('authors', $authors);
$SMARTY->assign('others', $others);
$SMARTY->display('copyrights.html');

?>
