<?php

/*
 * LMS version 1.5-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

function uptimef($ts)
{
	if($ts==0)
		return 'n/a';
	$min= $ts / 60;
	$hours = $min / 60;
	$days  = floor($hours / 24);
	$hours = floor($hours - ($days * 24));
	$min= floor($min - ($days * 60 * 24) - ($hours * 60));
	if ($days != 0)
	{
		$result = $days;
		if($days==1)
			$result .= ' day ';
		else
			$result .= ' days ';
	}
	if ($hours != 0) 
	{
		$result .= $hours;
		if($hours==1)
			$result .= ' hour ';
		else
			$result .= ' hours ';
	}
	if($min != 0)
	{
		$result .= $min;
		if($min==1)
			$result .= ' minute ';
		else
			$result .= ' minutes ';
	}
	return trim($result);
}

function check_fid($fid)
{
	return TRUE;
}

function check_ssn($ssn)
{
	return TRUE;
}

function to_words($num, $power = 0, $powsuffix = '', $short_version = 0)
{
	return $num;
}

?>
