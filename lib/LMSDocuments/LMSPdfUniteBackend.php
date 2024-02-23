<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2023 LMS Developers
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

class LMSPdfUniteBackend
{
    public const MAX_PDF_INPUT_FILES = 100;

    private $input_files;
    private $output_file;

    public function __construct()
    {
        $this->input_files = array();
        $this->output_file = null;
    }

    public function AppendPage($contents = null)
    {
        if (isset($contents)) {
            $tmpfname = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('LMS-PDF-UNITE-', true) . '.pdf';
            file_put_contents($tmpfname, $contents);
            $this->input_files[] = $tmpfname;
        }
    }

    private function prepareOutputFile()
    {
        if (!empty($this->input_files) && empty($this->output_file)) {
            $this->output_file = null;
            $new_output_file = null;

            $input_file_chunks = array_chunk($this->input_files, self::MAX_PDF_INPUT_FILES);
            foreach ($input_file_chunks as $input_file_chunk) {
                $cmd = 'pdfunite';
                if (isset($this->output_file)) {
                    $cmd .= ' ' . $this->output_file;
                }
                foreach ($input_file_chunk as $input_file) {
                    $cmd .= ' ' . $input_file;
                }
                $new_output_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('LMS-PDF-UNITE-', true) . '.pdf';
                $cmd .= ' ' . $new_output_file;
                $result = 0;
                system($cmd, $result);
                if (isset($this->output_file)) {
                    @unlink($this->output_file);
                }
                $this->output_file = $new_output_file;
                if (!empty($result)) {
                    @unlink($this->output_file);
                    break;
                }
            }

            foreach ($this->input_files as $input_file) {
                @unlink($input_file);
            }

            $this->input_files = array();
        }
    }

    private function removeOutputfile()
    {
        if (isset($this->output_file)) {
            @unlink($this->output_file);
            $this->output_file = null;
        }
    }

    public function WriteToBrowser($filename = null)
    {
        ob_clean();
        header('Pragma: public');
        header('Cache-control: private, must-revalidate');
        header('Content-Type: application/pdf');

        if (!is_null($filename)) {
            header('Content-Disposition: inline; filename=' . $filename);
        }

        $this->prepareOutputFile();

        if (!empty($this->output_file)) {
            echo file_get_contents($this->output_file);
            $this->removeOutputfile();
        }
    }

    public function WriteToString()
    {
        $this->prepareOutputFile();

        if (!empty($this->output_file)) {
            echo file_get_contents($this->output_file);

            $this->removeOutputfile();
        }
    }

    public function WriteToFile($filename)
    {
        $this->prepareOutputFile();

        if (!empty($this->output_file)) {
            file_put_contents($filename, file_get_contents($this->output_file));

            $this->removeOutputfile();
        }
    }
}
