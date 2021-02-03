<?php

/*
 *  LMS version 1.11-git
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

$USERPANEL->AddModule(
    trans('Info'),    // Display name
    'info',         // Module name - must be the same as directory name
    trans('Show basic informations about you'), // Tip
    10,         // Priority
    trans('This module is for showing basic informations about current logged customer'),   // Description
    array(      // Array of submenus in LMS
        array(
            'name' => trans('Changes affirmation'),
            'link' => '?m=userpanel&module=info&f=changes',
            'tip' => trans('Customers\' changes in info module'),
            'prio' => 30,
        ),
    ),
    'lms-ui-icon-documentation'
);
