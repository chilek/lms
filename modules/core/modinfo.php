<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

$_MODINFO['core'] = array(
		'summary' => array( 
			'en' => 'Core module',
			'pl' => 'Moduł główny'
			),
		'version' => '1.11-git',
		'description' => array(
			'en' => 'Core module for LMS. Contains very basic actions and items.',
			'pl' => 'Główny moduł LMS. Zawiera bardzo podstawowe akcje i elementy.'
			),
		'author' => 'LMS-developers',
		'revision' => '$Revision$',
		'notpublic' => FALSE,
		'priority' => 0,
		'url' => 'http://www.lms.org.pl',
		'default' => TRUE,
		'actions' => array(
			'init' => array(
				'description' => array(
					'en' => 'System and basic classes initialization',
					'pl' => 'Inicjalizacja systemu i klas podstawowych',
					),
				'notpublic' => TRUE,
				'bindings' => array(
					'pre/*:*', 
					),
				'notemplate' => TRUE,
				),
			'end' => array(
				'notpublic' => TRUE,
				'bindings' => array(
					'post/*:*', 
					),
				'notemplate' => TRUE,
				),
			'logout' => array(
				'description' => array(
					'en' => 'System log out',
					'pl' => 'Wylogowanie z systemu',
					),
				'notemplate' => TRUE,
				'hidden' => TRUE,
				),
			'header' => array(
				'description' => array(
					'en' => 'Header pseudo action',
					'pl' => 'Pseudo-akcja nagłówka'
					),
				'bindings' => array(
					'post/core:init', 
					),
				'notpublic' => TRUE,
				'dontexec' => TRUE, // don't execute action script
				),
			'footer' => array(
				'description' => array(
					'en' => 'Footer pseudo action',
					'pl' => 'Pseudo-akcja stopki'
					),
				'bindings' => array(
					'pre/core:end'
					),
				'notpublic' => TRUE,
				'dontexec' => TRUE,
				),
			'menu' => array(
				'bindings' => array(
					'post/core:header'
					),
				'notpublic' => TRUE,
				),
			'install' => array(
				'description' => array(
					'en' => 'Post-install system initialization',
					'pl' => 'Poinstalacyjna inicjalizacja systemu',
					),
				'notemplate' => TRUE,
				),
			'dberrorhandler' => array(
				'bindings' => array(
					'pre/core:footer',
				),
				'notpublic' => TRUE,
				),
			'err_actionnotfound' => array(
				'notpublic' => TRUE,
				'dontexec' => TRUE,
				),
			'err_actionnotpublic' => array(
				'notpublic' => TRUE,
				'dontexec' => TRUE,
				),
			'err_modulenotfound' => array(
				'notpublic' => TRUE,
				'dontexec' => TRUE,
				),
			'err_modulenotpublic' => array(
				'notpublic' => TRUE,
				'dontexec' => TRUE,
				),
			'err_loginform' => array(
				'notpublic' => TRUE,
				'onlogin' => TRUE,
				),
			'copyrights' => array(
				'default' => TRUE,
				),
			),
		);

// vi:encoding=utf-8:termencoding=iso-8859-2:syn=php:cindent:showmatch:
?>
