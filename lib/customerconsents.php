<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2024 LMS Developers
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

$supportedCustomerConsents = ConfigHelper::getConfig('customers.supported_consents', '', true);
$supportedCustomerConsents = array_flip(preg_split('/(\r?\n|\s*[;,]\s*|\s+)/', $supportedCustomerConsents, 0, PREG_SPLIT_NO_EMPTY));

if (!empty($supportedCustomerConsents)) {
    $CCONSENTS = array_filter(
        $CCONSENTS,
        function ($consent) use ($supportedCustomerConsents) {
            if (is_array($consent)) {
                return isset($supportedCustomerConsents[$consent['name']]);
            } else {
                return true;
            }
        }
    );

    if (isset($SMARTY)) {
        $SMARTY->assign('_CCONSENTS', $CCONSENTS);
    }
}
