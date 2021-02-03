<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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

abstract class LMSInvoice extends LMSDocument
{
    abstract public function invoice_body_standard();

    abstract public function invoice_body_ft0100();

    public function Draw($data)
    {
        parent::Draw($data);
        if (isset($this->data['invoice'])) {
            $template = ConfigHelper::getConfig('invoices.cnote_template_file');
        } else {
            $template = ConfigHelper::getConfig('invoices.template_file');
        }
        switch ($template) {
            case "standard":
                $this->invoice_body_standard();
                break;
            case "FT-0100":
                $this->invoice_body_ft0100();
                break;
        }
    }
}
