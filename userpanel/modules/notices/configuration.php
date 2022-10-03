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
    'lms-ui-icon-message fa-fw'
);

require_once('UserpanelNoticeHandler.php');
$notice_handler = new UserpanelNoticeHandler($DB, $SMARTY, isset($SESSION->id) ? $SESSION->id : null);

$USERPANEL->registerCallback('notices', function ($db, $smarty, $mod_dir) use ($notice_handler) {
    $urgent_notice = $notice_handler->getUrgentNotice();
    if (empty($urgent_notice)) {
        return '';
    }

    $notice_handler->markNoticeAsRead($urgent_notice['id']);
    $smarty->assign('urgent_notice', $urgent_notice);

    $unread_notices = $notice_handler->getUnreadNotices();
    $smarty->assign('unread_notices', $unread_notices);

    global $module_dir;
    $old_module_dir = $module_dir;
    $module_dir = $mod_dir;

    $html = $smarty->fetch('module:notices-callback-handler.html');

    $module_dir = $old_module_dir;

    return $html;
});
