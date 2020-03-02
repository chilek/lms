<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

/*  To improve performance of TCPDF
 *  install and configure a PHP opcode cacher like XCache
 *  http://xcache.lighttpd.net/
 *  This reduces execution time by ~30-50%
 */

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'tcpdf' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . 'pol.php');

class LMSTcpdfBackend extends LMSTCPDF
{
    public function __construct($pagesize, $orientation, $title)
    {
        global $layout;

        parent::__construct($orientation, PDF_UNIT, $pagesize, true, 'UTF-8', false, false);

        $this->SetProducer('LMS Developers');
        $this->SetSubject($title);
        $this->SetCreator('LMS ' . $layout['lmsv']);
        $this->SetDisplayMode('fullwidth', 'SinglePage', 'UseNone');

        $this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
        $this->SetHeaderMargin(PDF_MARGIN_HEADER);
        $this->SetFooterMargin(PDF_MARGIN_FOOTER);

        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $this->setLanguageArray($l);

        /* disable font subsetting to improve performance */
        $this->setFontSubsetting(true);

        $this->AppendPage();
    }

    public function AppendPage()
    {
        $this->AddPage();
    }

    public function WriteToBrowser($filename = null)
    {
        ob_clean();
        header('Pragma: private');
        header('Cache-control: private, must-revalidate');
        if (!is_null($filename)) {
            $this->Output($filename);
        } else {
            $this->Output();
        }
    }

    public function WriteToString()
    {
        return $this->Output(null, 'S');
    }
}
