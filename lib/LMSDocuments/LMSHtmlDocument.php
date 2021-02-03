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

class LMSHtmlDocument extends LMSDocument
{
    protected $smarty;
    protected $contents;
    protected $config_section;
    private $header_file;

    public function __construct($smarty, $config_section, $header_file)
    {
        $this->smarty = $smarty;
        $this->contents = '';
        $this->config_section = $config_section;
        $this->header_file = $header_file;
    }

    public function NewPage()
    {
    }

    private function PrepareFullContents()
    {
        $this->smarty->assign('css', file(SYS_DIR . DIRECTORY_SEPARATOR
            . 'img' . DIRECTORY_SEPARATOR . 'style_print.css'));
        $this->contents = $this->smarty->fetch($this->header_file) . $this->contents
            . $this->smarty->fetch('clearfooter.html');
    }

    public function WriteToBrowser($filename = null)
    {
        $this->PrepareFullContents();
        header('Content-Type: ' . ConfigHelper::getConfig($this->config_section . '.content_type'));
        if (!is_null($filename)) {
            header('Content-Disposition: inline; filename=' . $filename);
        }
        echo $this->contents;
    }

    public function WriteToString()
    {
        $this->PrepareFullContents();
        return $this->contents;
    }
}
