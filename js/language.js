/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

$_LANG.trans = function() {
	if ($_LANG.hasOwnProperty(arguments[0])) {
		var text = $_LANG[arguments[0]];
		var start = 0;
		var argn;
		var replace;
		while ((start = text.indexOf('$', start)) != -1) {
			argn = text.charCodeAt(start + 1) - 96;
			if (argn >= 0 && argn <= arguments.length) {
				if (typeof(arguments[argn]) == 'number') {
					replace = arguments[argn].toString();
				} else {
					replace = arguments[argn];
				}
				text = text.replace(text.substr(start, 2), replace);
				start += replace.length;
			} else {
				start += 2;
			}
		}
		return text;
	} else {
		return '';
	}
}

var trans = $_LANG.trans,
	$trans = trans,
	$t = $trans;
