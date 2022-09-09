<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2021 LMS Developers
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
 */

define('DOC_FLAG_SPLIT_PAYMENT', 8);

define('TARIFF_FLAG_NET_ACCOUNT', 16);
define('TARIFF_FLAG_SPLIT_PAYMENT', 32);

define('LIABILITY_FLAG_NET_ACCOUT', 16);
define('LIABILITY_FLAG_SPLIT_PAYMENT', 32);


$this->Execute(
    "UPDATE documents SET flags = (flags | ?) WHERE splitpayment = 1",
    array(
        DOC_FLAG_SPLIT_PAYMENT,
    )
);
$this->Execute("ALTER TABLE documents DROP COLUMN splitpayment");

$this->Execute(
    "UPDATE tariffs SET flags = (flags | ?) WHERE netflag = 1",
    array(
        TARIFF_FLAG_NET_ACCOUNT,
    )
);
$this->Execute(
    "UPDATE tariffs SET flags = (flags | ?) WHERE splitpayment = 1",
    array(
        TARIFF_FLAG_SPLIT_PAYMENT,
    )
);
$this->Execute("ALTER TABLE tariffs DROP COLUMN netflag");
$this->Execute(
    "ALTER TABLE tariffs DROP COLUMN splitpayment",
    array(
        TARIFF_FLAG_NET_ACCOUNT,
        TARIFF_FLAG_SPLIT_PAYMENT,
    )
);

$this->Execute("ALTER TABLE liabilities ADD COLUMN flags smallint DEFAULT 0 NOT NULL");
$this->Execute(
    "UPDATE liabilities SET flags = (flags | ?) WHERE netflag = 1",
    array(
        LIABILITY_FLAG_NET_ACCOUT,
    )
);
$this->Execute(
    "UPDATE liabilities SET flags = (flags | ?) WHERE splitpayment = 1",
    array(
        LIABILITY_FLAG_SPLIT_PAYMENT,
    )
);
$this->Execute("ALTER TABLE liabilities DROP COLUMN netflag");
$this->Execute("ALTER TABLE liabilities DROP COLUMN splitpayment");
