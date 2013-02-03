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

function module_main()
{
    global $DB,$LMS,$SESSION,$SMARTY;
    if(isset($_GET['confirm']))
    {
	$DB->Execute('UPDATE nodes SET warning=0 WHERE ownerid = ?', array($SESSION->id));
    }
    elseif($DB->GetOne('SELECT MAX(warning) FROM nodes WHERE ownerid = ?', array($SESSION->id)))
    {
	$message = $LMS->GetCustomerMessage($SESSION->id);
	$SMARTY->assign('message', $message);
    }

    $SMARTY->display('module:messages.html');
}

?>
