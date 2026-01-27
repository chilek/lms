<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2026 LMS Developers
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

use \Lms\KSeF\KSeF;

class LMSHtmlInvoice extends LMSHtmlDocument
{
    public function __construct($smarty)
    {
        parent::__construct($smarty, 'invoices', 'invoice' . DIRECTORY_SEPARATOR . 'invoiceheader.html');
    }

    public function Draw($data)
    {
        parent::Draw($data);

        $ksef_offline_support = ConfigHelper::checkConfig('ksef.offline_support');

        if (!empty($this->data['ksefenvironment'])
            && ($ksef_offline_support || !empty($this->data['ksefnumber']) && !empty($this->data['ksefstatus']))) {
            $this->smarty->assign(
                'ksefurl',
                KSeF::getQrCodeUrl([
                    'environment' => $this->data['ksefenvironment'],
                    'ten' => $this->data['division_ten'],
                    'date' => $this->data['cdate'],
                    'hash' => $this->data['ksefhash'],
                ])
            );

            if (empty($this->data['ksefnumber']) || empty($this->data['ksefstatus'])) {
                $this->smarty->assign(
                    'ksefcertificateurl',
                    KSeF::getCertificateQrCodeUrl([
                        'environment' => $this->data['ksefenvironment'],
                        'ten' => $this->data['division_ten'],
                        'divisionid' => $this->data['divisionid'],
                        'hash' => $this->data['ksefhash'],
                    ])
                );
            }

            $this->smarty->assign('ksefnumber', empty($this->data['ksefnumber']) || empty($this->data['ksefstatus']) ? 'OFFLINE' : $this->data['ksefnumber']);
        }

        $template_file = ConfigHelper::getConfig('invoices.template_file');
        if (isset($this->data['invoice'])) {
            $template_file = ConfigHelper::getConfig('invoices.cnote_template_file', $template_file);
        }
        if (!$this->smarty->templateExists('file:' . $template_file)) {
            $template_file = 'invoice' . DIRECTORY_SEPARATOR . $template_file;
        }

        $this->smarty->assign('type', $this->data['type']);
        $this->smarty->assign('duplicate', $this->data['type'] == DOC_ENTITY_DUPLICATE);
        $this->smarty->assign('invoice', $this->data);

        $this->contents .= $this->smarty->fetch('file:' . $template_file);
    }
}
