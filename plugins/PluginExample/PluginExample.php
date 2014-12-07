<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2013 LMS Developers
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

/**
 * PluginExample
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class PluginExample extends LMSPlugin
{
    public function registerHandlers()
    {
        $this->handlers = array(
            'welcome_on_load' => array(
                'class' => 'WelcomeHandler',
                'method' => 'welcomeOnLoad'
            ),
            'welcome_before_display' => array(
                'class' => 'WelcomeHandler',
                'method' => 'welcomeBeforeDisplay'
            ),
            'useradd_validation_before_submit' => array(
                'class' => 'UseraddHandler',
                'method' => 'useraddValidationBeforeSubmit'
            ),
            'useradd_after_submit' => array(
                'class' => 'UseraddHandler',
                'method' => 'useraddAfterSubmit'
            ),
        );
    }
}
