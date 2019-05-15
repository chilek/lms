<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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
 * Popup for userpanel messages LMS plugin class. PHP5 only.
 */
class urgent_notice_popup_plugin
{
    private $lms;

    /**
     * Class constructor
     *
     * @param object $LMS LMS object
     */
    public function __construct($LMS)
    {
        $this->lms = $LMS;
    }

    /**
     * Action check if notices module is on
     * and then check for notice urgent for customer
     * and assign smarty variable to control display
     * popup message in userpanel body.html file
     *
     * @param array $vars module notices data
     *
     * @return array module notices data
     */
    public function notice_check($vars)
    {
        global $SMARTY;
        global $SESSION;

        $LMS = $this->lms;
        $notice_urgent = $LMS->DB->GetRow(
            'SELECT m.subject, m.cdate, m.body, m.type, mi.id, mi.messageid, mi.destination, mi.status
				FROM customers c, messageitems mi, messages m
				WHERE c.id=mi.customerid
					AND m.id=mi.messageid
                    AND m.type = 6
                    AND mi.status = 1
                    AND c.id=?
                    ORDER BY m.cdate desc',
            array($SESSION->id)
        );
        $SMARTY->assign('notice_urgent', $notice_urgent);

        // always return $vars
        return $vars;
    }
}

// Initialize plugin
$popup_plugin = new urgent_notice_popup_plugin($LMS);

// Register plugin actions:
$LMS->RegisterHook('userpanel_module_call_before', array($popup_plugin, 'notice_check'));
