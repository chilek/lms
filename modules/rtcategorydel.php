<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

if (!$LMS->CategoryExists($_GET['id'])) {
    $layout['pagetitle'] = trans('Remove category ID: $a', sprintf("%04d", $_GET['id']));
    $body = '<p>' . trans('Specified ID is not proper or does not exist!') . '</p>';
    $body .= '<a HREF="?' . $SESSION->get_history_entry() . '">' . trans('Back') . '</a></p>';
    $SMARTY->assign('body', $body);
    $SMARTY->display('dialog.html');
} else {
    $category = intval($_GET['id']);

    $DB->Execute('DELETE FROM rtcategories WHERE id=?', array($category));
    $userpanel_rtcategories = $DB->GetOne('SELECT value FROM uiconfig WHERE section = \'userpanel\' AND var = \'default_categories\'');

    // Remove userpanel helpdesk default category
    if (!empty($userpanel_rtcategories)) {
        $cats = array_filter(
            explode(',', $userpanel_rtcategories),
            function ($elem) use ($category) {
                return $elem != $category;
            }
        );
        $DB->Execute('UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'default_categories\'', array(implode(',', $cats)));
    }

    $SESSION->redirect('?m=rtcategorylist');
}
