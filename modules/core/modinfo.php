<?

/*
 * LMS version 1.9-cvs
 *
 *  (C) Copyright 2004 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is licensed under LMS Public License. Please, see
 *  doc/LICENSE.en file for information about copyright notice.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  LMS Public License for more details.
 *
 *  $Id$
 */

$_MODINFO['core'] = array(
		'summary' => array( 
			'en' => 'Core module',
			'pl' => 'Gówwny moduł'
			),
		'version' => '1.9-cvs',
		'description' => array(
			'en' => 'Core module for LMS. Contains very basic actions and items.',
			'pl' => 'Główny moduł LMS. Zawiera bardzo podstawowe akcje i elementy.'
			),
		'author' => 'LMS-developers',
		'revision' => '$Revision$',
		'notpublic' => FALSE,
		'menus' => array(
			array(
				'id' => 'core_administration',
				'text' => array(
					'en' => 'Administration',
					'pl' => 'Administracja',
					),
				'img' => 'users.gif',
				'tip' => array(
					'en' => 'Systems informations and management',
					'pl' => 'Informacja o systemie i administracja',
					),
				),
			),
		'priority' => 0,
		'url' => 'http://lms.rulez.pl',
		'actions' => array(
			'header' => array(
				'menuname' => array(
					'en' => 'Header pseudo action',
					'pl' => 'Pseudo-akcja nagłówka'
					),
				'bindings' => array(
					'pre/*:*', 
					),
				'notpublic' => TRUE,
				'dontexec' => TRUE, // don't execute action script
				),
			'footer' => array(
				'menuname' => array(
					'en' => 'Footer pseudo action',
					'pl' => 'Pseudo-akcja stopki'
					),
				'bindings' => array(
					'post/*:*'
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
				'dontexec' => TRUE,
				'onlogin' => TRUE,
				),

			// menu administracja
			'welcome' => array(
				'menuname' => array(
					'en' => 'Info',
					'pl' => 'Informacje',
					),
				'tip' => array(
					'en' => 'Basic system informations',
					'pl' => 'Podstawowe informacje o systemie',
					),
				'menu' => 'core_administration',
				),
			'userlist' => array(
				'menuname' => array(
					'en' => 'Users',
					'pl' => 'Użytkownicy',
					),
				'tip' => array(
					'en' => 'User list',
					'pl' => 'Lista użytkowników',
					),
				'menu' => 'core_administration',
				),
			'useradd' => array(
				'menuname' => array(
					'en' => 'New user',
					'pl' => 'Nowy użytkownik',
					),
				'tip' => array(
					'en' => 'New user',
					'pl' => 'Nowy użytkownik',
					),
				'menu' => 'core_administration',
				),
			'userdel' => array(
				'hidden' => TRUE,
				),
			'useredit' => array(
				'hidden' => TRUE,
				'template' => 'userinfo', // TODO: use diffrent template than useredit.html
				),
			'userinfo' => array(
				'menuname' => array( // ta akcja jest ukryta, więc nie musi mieć 'menuname'
					'en' => 'User information',
					'pl' => 'Informacje o użytkowniku',
					),
				'hidden' => TRUE,	
				),
			'dblist' => array(
				'menuname' => array(
					'en' => 'Backups',
					'pl' => 'Kopie zapasowe',
					),
				'menu' => 'core_administration',
				),		
			),
		);

// vi:encoding=utf-8:termencoding=iso-8859-2:syn=php:cindent:showmatch:
?>
