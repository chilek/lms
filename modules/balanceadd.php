<?php

/*
 * LMS version 1.1-cvs
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

$addbalance = $_POST[addbalance];

$_SESSION[addbc] = $addbalance[comment];

$addbalance[value] = str_replace(",",".",$addbalance[value]);

if($addbalance[type]=="3"||$addbalance[type]=="4")
	{
		if(isset($addbalance[muserid]))
		{
			foreach($addbalance[muserid] as $value)
				if($LMS->UserExists($value))
				{
					$addbalance[userid]=$value;
					$LMS->AddBalance($addbalance);
				}
		}
		else
		{
			if($LMS->UserExists($addbalance[userid]))
				$LMS->AddBalance($addbalance);
		}
	}

	if($addbalance[type]=="2"||$addbalance[type]=="1")
	{
		$addbalance[userid] = "0";
		$LMS->AddBalance($addbalance);
	}

header("Location: ?".$_SESSION[backto]);

?>
