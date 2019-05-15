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

$_MODINFO['traffic'] = array(
        'summary' => array(
            'en' => 'Traffic',
            'pl' => 'Statystyki',
            ),
        'description' => array(
            'en' => 'Statistics of Internet Link Usage',
            'pl' => 'Statystyki uĹźycia ĹÄcza internetowego',
            ),
        'version' => '1.11-git',
        'author' => 'LMS Developers',
        'url' => 'http://www.lms.org.pl',
        'revision' => '$Revision$',
        'notpublic' => false,
        'priority' => 50,
        'menus' => array(
            array(
                'id' => 'traffic',
                'text' => array(
                    'en' => 'Stats',
                    'pl' => 'Statystyki',
                    ),
                'img' => 'traffic.gif',
                'tip' => array(
                    'en' => 'Statistics of Internet Link Usage',
                    'pl' => 'Statystyki uĹźycia ĹÄcza internetowego',
                    )
                ),
            ),
        'actions' => array(
            'traffic' => array(
                'description' => array(
                    'en' => 'Viewing stats using filter',
                    'pl' => 'Przeglądanie statystyk z wykorzystaniem filtru',
                    ),
                'menuname' => array(
                    'en' => 'Filter',
                    'pl' => 'Filtr',
                    ),
                'tip' => array(
                    'en' => 'User-defined stats',
                    'pl' => 'Statystyki zdefiniowane przez użytkownika',
                    ),
                'menu' => 'traffic',
                'default' => true,
                ),
            'traffic-hour' => array(
                'description' => array(
                    'en' => 'Viewing last hour stats',
                    'pl' => 'Przeglądanie statystyk z ostatniej godziny',
                    ),
                'menuname' => array(
                    'en' => 'Last hour',
                    'pl' => 'Ostatnia godzina',
                    ),
                'tip' => array(
                    'en' => 'Last hour stats for all networks',
                    'pl' => 'Statystyki z ostatniej godziny dla całej sieci',
                    ),
                'menu' => 'traffic',
                'template' => 'traffic',
                ),
            'traffic-day' => array(
                'description' => array(
                    'en' => 'Viewing last day stats',
                    'pl' => 'Przeglądanie statystyk z ostatniej doby',
                    ),
                'menuname' => array(
                    'en' => 'Last day',
                    'pl' => 'Ostatnia doba',
                    ),
                'tip' => array(
                    'en' => 'Last day stats for all networks',
                    'pl' => 'Statystyki z ostatniej doby dla całej sieci',
                    ),
                'menu' => 'traffic',
                'template' => 'traffic',
                ),
            'traffic-month' => array(
                'description' => array(
                    'en' => 'Viewing last 30 days stats',
                    'pl' => 'Przeglądanie statystyk z ostatnich 30 dni',
                    ),
                'menuname' => array(
                    'en' => 'Last month',
                    'pl' => 'Ostatni miesiąc',
                    ),
                'tip' => array(
                    'en' => 'Last 30 days stats for all networks',
                    'pl' => 'Statystyki z ostatnich 30 dni dla całej sieci',
                    ),
                'menu' => 'traffic',
                'template' => 'traffic',
                ),
            'traffic-year' => array(
                'description' => array(
                    'en' => 'Viewing last year stats',
                    'pl' => 'Przeglądanie statystyk z ostatniego roku',
                    ),
                'menuname' => array(
                    'en' => 'Last year',
                    'pl' => 'Ostatni rok',
                    ),
                'tip' => array(
                    'en' => 'Last year stats for all networks',
                    'pl' => 'Statystyki z ostatniego roku dla całej sieci',
                    ),
                'menu' => 'traffic',
                'template' => 'traffic',
                ),
            'trafficdbcompact' => array(
                'description' => array(
                    'en' => 'Stats database compacting',
                    'pl' => 'Kompaktowanie bazy danych statystyk',
                    ),
                'menuname' => array(
                    'en' => 'Compacting',
                    'pl' => 'Kompaktowanie',
                    ),
                'tip' => array(
                    'en' => 'Stats database compacting',
                    'pl' => 'Kompaktowanie bazy danych statystyk',
                    ),
                'menu' => 'traffic',
                ),
            'compacting' => array(
                'description' => array(
                    'en' => 'Stats database compacting',
                    'pl' => 'Kompaktowanie bazy danych statystyk',
                    ),
                'notemplate' => true,
                ),
            'print' => array(
                'description' => array(
                    'en' => 'Printing stats form',
                    'pl' => 'Formularz wydruku statystyk',
                    ),
                'menuname' => array(
                    'en' => 'Printing',
                    'pl' => 'Wydruki',
                    ),
                'tip' => array(
                    'en' => 'Printing of customer stats',
                    'pl' => 'Wydruk statystyk klienta',
                    ),
                'menu' => 'traffic',
                ),
            'printtraffic' => array(
                'description' => array(
                    'en' => 'Stats printout',
                    'pl' => 'Wydruk statystyk',
                    ),
                'template' => 'printtraffic',
                ),
            'nodetraffic' => array(
                'description' => array(
                    'en' => 'Stats in node information window',
                    'pl' => 'Statystyki w oknie informacji o komputerze',
                    ),
                'notemplate' => true,
                'bindings' => array(
                        'post/core:nodeinfo'
                    ),
                ),
            ),
        );

// vi:encoding=utf-8:termencoding=iso-8859-2:syn=php:cindent:showmatch:
