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

$_MODINFO['users'] = array(
        'summary' => array(
            'en' => 'System users',
            'pl' => 'Użytkownicy systemu'
            ),
        'version' => '1.11-git',
        'description' => array(
            'en' => 'System users management',
            'pl' => 'Zarządzanie użytkownikami systemu'
            ),
        'author' => 'LMS-developers',
        'revision' => '$Revision$',
        'notpublic' => false,
        'priority' => 10,
        'url' => 'http://www.lms.org.pl',
        'menus' => array(
                array(
                'id' => 'users',
                'text' => array(
                        'en' => 'Users',
                    'pl' => 'Uzytkownicy',
                        ),
                'img' => 'users.gif',
                'tip' => array(
                    'en' => 'Statistics of Internet Link Usage',
                    'pl' => '',
                    )
                ),
            ),
        'actions' => array(
            'list' => array(
                'description' => array(
                        'en' => 'Viewing users list',
                    'pl' => 'Przeglądanie listy użytkowników',
                    ),
                'default' => true,
                'menuname' => array(
                        'en' => 'Users list',
                    'pl' => 'Lista użytkowników',
                    ),
                'tip' => array(
                        'en' => 'List of system users',
                    'pl' => 'Lista użytkowników systemu',
                    ),
                'menu' => 'users',
                ),
            'add' => array(
                'description' => array(
                        'en' => 'Adding new users',
                    'pl' => 'Dodawanie nowego użytkownika',
                    ),
                'menuname' => array(
                        'en' => 'New user',
                    'pl' => 'Nowy użytkownik',
                    ),
                'tip' => array(
                        'en' => 'Adding a new user',
                    'pl' => 'Dodawanie nowego użytkownika',
                    ),
                'menu' => 'users',
                ),
            'info' => array(
                'description' => array(
                        'en' => 'Viewing users information',
                    'pl' => 'Przeglądanie informacji o użytkownikach',
                    ),
                ),
            'edit' => array(
                'description' => array(
                        'en' => 'Changing users data',
                    'pl' => 'Zmiana danych użytkowników',
                    ),
                ),
            'del' => array(
                'description' => array(
                        'en' => 'Deleting users',
                    'pl' => 'Usuwanie użytkowników',
                    ),
                ),
            'passwd' => array(
                'description' => array(
                        'en' => 'Changing users passwords',
                    'pl' => 'Zmiana haseł użytkowników',
                    ),
                ),
            ),
        );

// vi:encoding=utf-8:termencoding=iso-8859-2:syn=php:cindent:showmatch:
