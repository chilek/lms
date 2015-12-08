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

abstract class LMSInvoice {
	protected $invoice;
	protected $backend;

	public function __construct($pagesize, $orientation, $title, $backendclass) {
		$this->invoice = null;
		$this->backend = new $backendclass($pagesize, $orientation, $title);
	}

	public function SetInvoice($invoice) {
		$this->invoice = $invoice;
	}

	abstract function invoice_body_standard();

	abstract function invoice_body_ft0100();

	public function invoice_body() {
		if (isset($this->invoice['invoice']))
			$template = ConfigHelper::getConfig('invoices.cnote_template_file');
		else
			$template = ConfigHelper::getConfig('invoices.template_file');
		switch ($template) {
			case "standard":
				$this->invoice_body_standard();
				break;
			case "FT-0100":
				$this->invoice_body_ft0100();
				break;
		}
	}

	public function NewPage() {
		$this->backend->AppendPage();
	}

	public function WriteToBrowser($filename = null) {
		$this->backend->WriteToBrowser($filename);
	}

	public function WriteToString() {
		return $this->backend->WriteToString();
	}
}

?>
