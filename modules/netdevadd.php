<?php

/*
 * LMS version 1.5-cvs
 *
 *  (C) Copyright 2001-2004 LMS Developers
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

$netdevdata = $_POST['netdev'];

if(isset($netdevdata)) {
	// Jakby to dzia³a³o to by by³o mi³o... :P
	//if($netdevdata['ports'] !="" && !eregi("^[0-9]{,4}$",$netdevdata['ports']))
        //        $error['ports'] = "Podana ilo¶æ portów jest b³êdna!";
	if($netdevdata['ports'] =='')
		$netdevdata['ports'] = 0;
	if($netdevdata['name'] == '')
		$error['name'] = 'Pole nazwa nie mo¿e byæ puste!';
	elseif(strlen($netdevdata['name'])>32)
		$error['name'] = 'Podana nazwa jest za d³uga!';

        if(!$error)
        {
	    $netdevid=$LMS->NetDevAdd($netdevdata);
	    header('Location: ?m=netdevinfo&id='.$netdevid);
	    die;
        }
}
		

$layout['pagetitle'] = 'Nowe urz±dzenie';

$SMARTY->assign('error',$error);
$SMARTY->assign('netdev',$netdevdata);
$SMARTY->display('netdevadd.html');

?>
