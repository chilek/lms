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
    trans('Notices'), // Display name
    'notices',      // Module name - must be the same as directory name
    trans('Shows notices'), // Tip
    40,         // Priority
    trans('This module is for showing notices for your customer'),   // Description
    null,
    'lms-userpanel-notices'
);

$USERPANEL->registerCallback('notices', function ($db, $smarty) {
    global $SESSION;

    $notice_urgent = $db->GetRow(
        'SELECT m.subject, m.cdate, m.body, m.type, mi.id, mi.messageid, mi.destination, mi.status
				FROM customers c, messageitems mi, messages m
				WHERE c.id=mi.customerid
					AND m.id=mi.messageid
                    AND m.type = ?
                    AND mi.status = ?
                    AND c.id=?
                    ORDER BY m.cdate desc',
        array(MSG_USERPANEL_URGENT, MSG_SENT, $SESSION->id)
    );

    $db->Execute('UPDATE messageitems SET lastreaddate = ?NOW? WHERE id = ?', array($notice_urgent['id']));

    $smarty->assign('notice_urgent', $notice_urgent);
    $smarty->assign('module_backto', ltrim($_SERVER['QUERY_STRING'], 'm='));

    return $smarty->fetch('module:callback-handler.html');
});
