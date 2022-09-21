<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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


if (!$this->ResourceExists('voip_cdr_caller_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX voip_cdr_caller_idx ON voip_cdr (caller)");
}
if (!$this->ResourceExists('voip_cdr_callee_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX voip_cdr_callee_idx ON voip_cdr (callee)");
}
