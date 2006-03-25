<?

/*
 * LMS version 1.9-cvs
 *
 *  (C) Copyright 2001-2006 LMS Developers
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
		'priority' => 0,
		'url' => 'http://lms.rulez.pl',
		'default' => TRUE,
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
				'onlogin' => TRUE,
				),
			'copyrights' => array(
				'default' => TRUE,
				),
			'logout' => array(
				),
			),
		);

// vi:encoding=utf-8:termencoding=iso-8859-2:syn=php:cindent:showmatch:
?>
