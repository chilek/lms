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
        'notpublic' => false,
        'priority' => 0,
        'url' => 'http://www.lms.org.pl',
        'default' => true,
        'actions' => array(
            'init' => array(
                'description' => array(
                    'en' => 'System and basic classes initialization',
                    'pl' => 'Inicjalizacja systemu i klas podstawowych',
                    ),
                'notpublic' => true,
                'bindings' => array(
                    'pre/*:*',
                    ),
                'notemplate' => true,
                ),
            'end' => array(
                'notpublic' => true,
                'bindings' => array(
                    'post/*:*',
                    ),
                'notemplate' => true,
                ),
            'logout' => array(
                'description' => array(
                    'en' => 'System log out',
                    'pl' => 'Wylogowanie z systemu',
                    ),
                'notemplate' => true,
                'hidden' => true,
                ),
            'header' => array(
                'description' => array(
                    'en' => 'Header pseudo action',
                    'pl' => 'Pseudo-akcja nagłówka'
                    ),
                'bindings' => array(
                    'post/core:init',
                    ),
                'notpublic' => true,
                'dontexec' => true, // don't execute action script
                ),
            'footer' => array(
                'description' => array(
                    'en' => 'Footer pseudo action',
                    'pl' => 'Pseudo-akcja stopki'
                    ),
                'bindings' => array(
                    'pre/core:end'
                    ),
                'notpublic' => true,
                'dontexec' => true,
                ),
            'menu' => array(
                'bindings' => array(
                    'post/core:header'
                    ),
                'notpublic' => true,
                ),
            'install' => array(
                'description' => array(
                    'en' => 'Post-install system initialization',
                    'pl' => 'Poinstalacyjna inicjalizacja systemu',
                    ),
                'notemplate' => true,
                ),
            'dberrorhandler' => array(
                'bindings' => array(
                    'pre/core:footer',
                ),
                'notpublic' => true,
                ),
            'err_actionnotfound' => array(
                'notpublic' => true,
                'dontexec' => true,
                ),
            'err_actionnotpublic' => array(
                'notpublic' => true,
                'dontexec' => true,
                ),
            'err_modulenotfound' => array(
                'notpublic' => true,
                'dontexec' => true,
                ),
            'err_modulenotpublic' => array(
                'notpublic' => true,
                'dontexec' => true,
                ),
            'err_loginform' => array(
                'notpublic' => true,
                'onlogin' => true,
                ),
            'copyrights' => array(
                'default' => true,
                ),
            ),
        );

// vi:encoding=utf-8:termencoding=iso-8859-2:syn=php:cindent:showmatch:
