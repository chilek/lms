<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

use setasign\Fpdi\Tcpdf\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

class LMSFpdiBackend extends Fpdi
{
    public function __construct($pagesize = 'A4', $orientation = 'portrait', $title = 'LMSFpdiBackend')
    {
        global $layout;

        parent::__construct($orientation, PDF_UNIT, $pagesize, true, 'UTF-8', false, false);

        //$this->SetProducer('LMS Developers');
        $this->SetSubject($title);
        $this->SetCreator('LMS ' . $layout['lmsv']);
        $this->SetDisplayMode('fullwidth', 'SinglePage', 'UseNone');

        $this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
        $this->SetHeaderMargin(PDF_MARGIN_HEADER);
        $this->SetFooterMargin(PDF_MARGIN_FOOTER);

        //$this->setImageScale(PDF_IMAGE_SCALE_RATIO);
        //$this->setLanguageArray($l);

        //$this->AppendPage();

        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
    }

    public function AppendPage($contents = null)
    {
        if (isset($contents)) {
            $pageCount = $this->setSourceFile(StreamReader::createByString($contents));
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                // import a page
                $templateId = $this->importPage($pageNo);
                // get the size of the imported page
                $size = $this->getTemplateSize($templateId);

                // create a page (landscape or portrait depending on the imported page size)
                $this->AddPage($size['orientation'], $size);

                // use the imported page
                $this->useTemplate($templateId);
            }
        } else {
            $this->AddPage();
        }
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
        return $this->Output('', 'S');
    }

    public function WriteToFile($filename)
    {
        return $this->Output($filename, 'F');
    }
}
