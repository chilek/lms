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

$_MODINFO['auth'] = array(
        'summary' => array(
            'en' => 'Access rights',
            'pl' => 'Prawa dostępu'
            ),
        'description' => array(
            'en' => 'Users access rights management and verification',
            'pl' => 'Zarządzanie i weryfikacja uprawnień użytkowników'
            ),
        'version' => '1.11-git',
        'revision' => '$Revision$',
        'author' => 'LMS Developers',
        'url' => 'http://www.lms.org.pl',
        'notpublic' => false,
        'priority' => -1,
        'actions' => array(
            'engine' => array(
                // main action, cleaning menu and ExecStack arrays() from
                // unwanted staff
                'default' => true,
                'notemplate' => true,
                'bindings' => array(
                    'post/core:init',
                    ),
                'notpublic' => true,
                ),
            'noaccess' => array(
                'dontexec' => true,
                'hidden' => true,
                ),
            'rights' => array(
                'description' => array(
                    'en' => 'Viewing and editing users access rights',
                    'pl' => 'Przeglądanie i edycja praw dostępu użytkowników',
                    ),
            //  'notpublic' => TRUE,
                'notemplate' => true,
                'bindings' => array(
                    'pre/users:add',
                    'pre/users:edit',
                    'pre/users:info',
                    ),
                ),
            'save' => array(
                'notpublic' => true,
                'notemplate' => true,
                'hidden' => true,
                'bindings' => array(
                    'post/users:add',
                    'post/users:edit',
                    'post/core:install',
                    ),
                ),
            ),
        );

// vi:encoding=utf-8:termencoding=iso-8859-2:syn=php:cindent:showmatch:
