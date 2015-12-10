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

class LMSHtmlReceipt extends LMSDocument {
	private $smarty;
	private $contents;

	public function __construct($smarty) {
		$this->smarty = $smarty;
		$this->contents = '';
	}

	public function Draw($data) {
		parent::Draw($data);

		$template_file = ConfigHelper::getConfig('receipts.template_file');
		if (!$this->smarty->templateExists($template_file))
			$template_file = 'receipt' . DIRECTORY_SEPARATOR . $template_file;
		$this->smarty->assign('type', $this->data['type']);
		$this->smarty->assign('receipt', $this->data);
		$this->contents .= $this->smarty->fetch($template_file);
	}

	public function NewPage() {
	}

	private function PrepareFullContents() {
		$this->smarty->assign('css', file(ConfigHelper::getConfig('directories.sys_dir', '', true)
			. DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'style_print.css'));
		$this->contents = $this->smarty->fetch('receipt/receiptheader.html') . $this->contents
			. $this->smarty->fetch('clearfooter.html');
	}

	public function WriteToBrowser($filename = null) {
		$this->PrepareFullContents();
		header('Content-Type: ' . ConfigHelper::getConfig('receipts.content_type'));
		if (!is_null($filename))
			header('Content-Disposition: attachment; filename=' . $filename);
		echo $this->contents;
	}

	public function WriteToString() {
		$this->PrepareFullContents();
		return $this->contents;
	}
}

?>
