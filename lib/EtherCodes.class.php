<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

class EtherCodes {
	private static $producers = array();

	public static function GetProducer($mac) {
		$mac = strtoupper(str_replace(':', '-', substr($mac, 0, 8)));

		if (empty(self::$producers)) {
			$maclines = @file(LIB_DIR . DIRECTORY_SEPARATOR . 'ethercodes.txt', FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
			if (!empty($maclines))
				foreach ($maclines as $line) {
					list ($prefix, $producer) = explode(':', $line);
					self::$producers[$prefix] = $producer;
			}
		}

		return $mac && isset(self::$producers[$mac]) ? self::$producers[$mac] : '';
	}
}
