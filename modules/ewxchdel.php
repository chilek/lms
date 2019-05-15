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

if (!empty($_GET['is_sure'])) {
    $DB->Execute('DELETE FROM ewx_channels WHERE id = ?', array(intval($_GET['id'])));
    $SESSION->redirect('?'.$SESSION->get('backto'));
} else if ($channel = $DB->GetRow('SELECT id, name FROM ewx_channels WHERE id = ?', array(intval($_GET['id'])))) {
    $layout['pagetitle'] = trans('Removing channel $a', strtoupper($channel['name']));
    $SMARTY->display('header.html');
    echo '<H1>'.$layout['pagetitle'].'</H1>';
    echo '<P>'.trans('Are you sure, you want to delete this channel?').'</P>';
    echo '<A href="?m=ewxchdel&id='.$channel['id'].'&is_sure=1">'.trans('Yes, I am sure.').'</A>';
    $SMARTY->display('footer.html');
} else {
    $SESSION->redirect('?m=ewxchlist');
}
