<?php

/*
 * LMS version 1.2.0 Janet
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

// list of modules with access for anyone

$access[allow] = "^(welcome|copyrights|logout|chpasswd)$";

$access[table][0][name]		= _('full access');
$access[table][0][allow_reg]	= "^.*$";

$access[table][1][name]		= _('all data reading');
$access[table][1][allow_reg]	= "^((admin|balance|db|net|node|netdev|tariff|payment|user)(list|list(debt|disc)|info|view|debt|search|balance)|netdevmap|print)$";

$access[table][2][name]		= _('computers connection/disconnection');
$access[table][2][allow_reg]	= "^nodeset$";

$access[table][3][name]		= _('finances manipulation');
$access[table][3][allow_reg]	= "^((tariff)(add|info|list|move|edit|delete)|(payment)(add|del|edit|info|list)|(balance|balance|userbalance)(new|add|ok)|(invoice|invoice(list|new|report)))$";

$access[table][4][name]         = _('configuration reload');
$access[table][4][allow_reg]    = "^reload$";

$access[table][5][name]		= _('users accounts manipulation');
$access[table][5][allow_reg]	= "^user(add|edit|del)$";

$access[table][6][name]		= _('computers manipulation');
$access[table][6][allow_reg]	= "^(node(add|scan|del|edit|set)|choose(mac|ip))$";

$access[table][7][name]         = _('stats access');
$access[table][7][allow_reg]    = "^traffic$";

$access[table][8][name]         = _('mailing access');
$access[table][8][allow_reg]    = "^(mailing|mailingsend)$";

$access[table][253][name]	= _('administrators accounts editing access forbidden');
$access[table][253][deny_reg]	= "^(admin(add|del|edit|passwd))$";

$access[table][255][name]	= _('no access');
$access[table][255][deny_reg]	= "^.*$";

?>
