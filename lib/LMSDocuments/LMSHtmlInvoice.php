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

class LMSHtmlInvoice extends LMSHtmlDocument {
	public function __construct($smarty) {
		parent::__construct($smarty, 'invoices', 'invoice' . DIRECTORY_SEPARATOR . 'invoiceheader.html');
	}

	public function Draw($data) {
		parent::Draw($data);
		if(isset($this->data['invoice']))
			$template_file = ConfigHelper::getConfig('invoices.cnote_template_file');
		else
			$template_file = ConfigHelper::getConfig('invoices.template_file');
		if (!$this->smarty->templateExists('file:' . $template_file))
			$template_file = 'invoice' . DIRECTORY_SEPARATOR . $template_file;
		$this->smarty->assign('type', $this->data['type']);
		$this->smarty->assign('duplicate', $this->data['type'] == trans('DUPLICATE'));
		$this->smarty->assign('invoice', $this->data);
		$this->contents .= $this->smarty->fetch('file:' . $template_file);
	}
}

?>
