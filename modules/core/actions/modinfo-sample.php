<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

$_MODINFO['sample'] = array(
        'summary' => array( // module name, summary
            'en' => 'Example template for modinfo.php',
            'pl' => 'Przykładowy szablon dla modinfo.php'
            ),
        'version' => '1.11-git', // version
        'description' => array( // description
            'en' => 'This is sample module that contains template for modinfo.php',
            'pl' => 'To jest przykładowy moduł, który tak naprawdę zawiera tylko przykładowy modinfo.php',
            ),
        'author' => 'LMS-developers', // author information
        'url' => 'http://www.lms.org.pl', // url of module homepage
        'revision' => '$Revision$', // cvs revision, optiona
        'notpublic' => true, // notpublic - this means, that user can't access any actions of this module
                    // directly, ie. using modified URL
        'menus' => array( // this part of modinfo contains information about menus that this module provides
            array(  // first menu
                'id' => 'core_administration', // id, used in actions
                'text' => array( // menutext
                    'en' => 'Administration',
                    'pl' => 'Administracja',
                    ),
                'img' => 'users.gif', // image from img directory, that we use in menu
                'tip' => array( // tiptext
                    'en' => 'Systems information and management',
                    'pl' => 'Informacja o systemie i administracja',
                    ),
                ),
            ),
        'priority' => 0, // priority, modules are sorted while building execstack
        'actions' => array( // this contains list of actions placed in actions subdirectory
            'header' => array( // array's indexes are used to define filename of action script
                'menuname' => array( // what we should put in menu, by default in menu that
                            // have same id like this modulename, so
                            // remember that if module has actions
                            // that should be visible in menu
                            // it must provide at least one menu, BUT
                            // but u can provide 'menu' element that contains
                            // id of target menu (see core/modinfo.php for
                            // samples.
                    'en' => 'Header pseudo action',
                    'pl' => 'Pseudo-akcja nagłówka'
                    ),
                'bindings' => array(
                    'pre/*:*',
                    ),
                'template' => 'header', // template name (without .html extension)
                            // by default it's action name
                'notpublic' => true, // this action isn't public, so, even if you specify
                            // menuname or menu it will be not placed in menu
                            // and user will be unable to access this action
                            // using url.
                'dontexec' => true, // don't execute action script, ie, this action
                            // is ONLY template and it don't have any action script
                ),
            'footer' => array(
                'menuname' => array(
                    'en' => 'Footer pseudo action',
                    'pl' => 'Pseudo-akcja stopki'
                    ),
                'bindings' => array(
                    'post/*:*'
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
                'dontexec' => true,
                'onlogin' => true,
                ),

            // menu administracja
            'welcome' => array(
                'menuname' => array(
                    'en' => 'Info',
                    'pl' => 'Informacje',
                    ),
                'tip' => array(
                    'en' => 'Basic system information',
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
            'userinfo' => array(
                'menuname' => array(
                    'en' => 'User information',
                    'pl' => 'Informacje o użytkowniku',
                    ),
                'notpublic' => true,
                'hidden' => true,
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
