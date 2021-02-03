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

$layout['pagetitle'] = trans('Copyrights');

$authors = array(
        
        'alec' => array(
            'realname' => 'Aleksander Machniak',
            'www' => 'www.alec.pl',
            'info' => 'PHP, C, Perl, HTML, JavaScript, images, doc, PostgreSQL'
             ),

        'Baseciq' => array(
            'realname' => 'Łukasz Jarosław Mozer',
            'www' => 'www.baseciq.org',
            'info' => 'inventor & maintainer, PHP, Perl, design, HTML, JavaScript, CSS, images, doc, project\'s WWW'
            ),

        'chilek' => array(
            'info' => 'PHP, Perl',
            'realname' => 'Tomasz Chilinski',
            ),

        'hunter' => array(
            'info' => 'PHP, Perl',
            'realname' => 'Krzysztof Drewicz',
            ),

        'Lexx' => array(
            'info' => 'PHP, C, doc, project\'s  WWW',
            'www' => 'www.lexx.w.pl',
            'realname' => 'Marcin Król',
             ),
    
        );

$others = array(

        'agaran' => array(
            'info' => 'Perl',
            'realname' => 'Maciej Pijanka',
            ),
        
        'kflis' => array(
            'info' => 'Betatesting, images',
            'www' => 'www.kflis.net',
            'realname' => 'Kuba Flis',
            ),

        'dzwonus' => array(
            'info' => 'Betatesting, idea co-author',
            'realname' => 'Tomasz Dzwonkowski',
            ),

        'victus' => array(
            'info' => 'Betatesting, idea co-author',
            'realname' => 'Sebastian Frasunkiewicz',
            ),

        'shasta' => array(
            'info' => 'MySQL, WWW site',
            'realname' => 'Kuba Jankowski',
            'www' => 's.atn.pl',
            ),

        'Bob_R' => array(
            'info' => 'CSS, HTML, JavaScript',
            'realname' => 'Paweł Czerski',
            'www' => 'plug.atn.pl/~bober/',
            ),

        'sickone' => array(
            'realname' => 'Paweł Kisiela',
            'info' => 'CSS, HTML, JavaScript',
            'www' => 'gamechannel.int.pl',
            ),

        'DziQs' => array(
            'realname' => 'Michał Zapalski',
            'info' => 'PHP, Perl',
            ),

        'kondi' => array(
            'realname' => 'Konrad Rzentarzewski',
            'info' => 'i18n, PHP, JavaScript',
            'www' => 'kondi.net',
            ),

        'Pierzak' => array(
            'realname' => 'Piotr Mierzeński',
            'info' => 'Logo project',
            ),
        );
                
$SMARTY->assign('authors', $authors);
$SMARTY->assign('others', $others);
