<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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

$_MODINFO['mailing'] = array(
		'summary' => array( 
			'en' => 'Mailing',
			'pl' => 'Mailing'
			),
		'description' => array(
			'en' => 'Mass mail to customers',
			'pl' => 'Korespondencja seryjna do klientów'
			),
		'version' => '1.11-cvs',
		'revision' => '$Revision$',
		'author' => 'LMS Developers',
		'url' => 'http://www.lms.org.pl',
		'notpublic' => FALSE,
		'priority' => 8,
		'menus' => array(
			array(
				'id' => 'mailing',
				'text' => array(
					'en' => 'Mailing',
					'pl' => 'Mailing',
					),
				'img' => 'mail.gif',
				'tip' => array(
					'en' => 'Mass Mail',
					'pl' => 'Korespondencja seryjna',
					),
				),
			),
		'actions' => array(
			'mailing' => array(
				'default' => TRUE,
				'description' => array(
					'en' => 'Sending mail to customers groups',
					'pl' => 'Wysyłanie poczty do grup użytkowników',
					),
				),
			),
		);

// vi:encoding=utf-8:termencoding=iso-8859-2:syn=php:cindent:showmatch:
?>
