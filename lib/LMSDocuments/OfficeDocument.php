<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2025 LMS Developers
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

namespace Lms;

class OfficeDocument
{
    const TYPE_DOCX = 1;
    const TYPE_ODT = 2;
    const TYPE_RTF = 3;

    private $fileName;
    private $za;
    private $archivedFiles = array();
    private $mainFileContent = null;
    private $mainDocumentName = null;
    private $mainDocumentContent = null;
    private $type = null;
    private $clone = true;
    private $tempFileName;
    private $variablePrefix = '';

    public function __construct($fileName, $clone = true)
    {
        $this->fileName = $fileName;
        $this->clone = $clone;

        if (preg_match('/^.+\.(?<type>[[:alnum:]+]+)$/i', $this->fileName, $m)) {
            switch (strtolower($m['type'])) {
                case 'docx':
                    $this->type = self::TYPE_DOCX;
                    break;
                case 'odt':
                    $this->type = self::TYPE_ODT;
                    break;
                case 'rtf':
                    $this->type = self::TYPE_RTF;
                    break;
            }
        } else {
            throw new \Exception('Unsupported file type!');
        }

        if ($this->clone) {
            $this->tempFileName = tempnam(sys_get_temp_dir(), 'lms-document-attachment-');
            @copy($this->fileName, $this->tempFileName);
            $this->fileName = $this->tempFileName;
        }

        if ($this->type === self::TYPE_RTF) {
            $this->mainDocumentContent = file_get_contents($this->fileName);
            return;
        }

        $this->za = new \ZipArchive();
        if ($this->za->open($fileName) === false) {
            throw new \Exception('Could not open archived office file!');
        }

        for ($i = 0; $i < $this->za->numFiles; $i++) {
            $properties = $this->za->statIndex($i);
            $this->archivedFiles[$properties['name']] = $properties;
            if ($this->type == self::TYPE_DOCX && $properties['name'] == '[Content_Types].xml'
                || $this->type == self::TYPE_ODT && $properties['name'] == 'manifest.rdf') {
                $this->mainFileContent = $this->za->getFromIndex($properties['index']);
            }
        }

        if (empty($this->mainFileContent)) {
            throw new \Exception('Cannot find main file!');
        }

        if ($this->type == self::TYPE_ODT) {
            $this->mainFileContent = preg_replace("/\s*\r?\n\s*/", '', $this->mainFileContent);
        }

        if ($this->type == self::TYPE_DOCX && !preg_match('#<Override PartName="(?<filename>[^"]+)" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main\+xml"/>#i', $this->mainFileContent, $matches)
            || $this->type == self::TYPE_ODT && !preg_match('#<rdf:Description rdf:about="(?<filename>[^"]+)"><rdf:type rdf:resource="http://docs.oasis-open.org/ns/office/1.2/meta/odf\#ContentFile"/>#i', $this->mainFileContent, $matches)) {
            throw new \Exception('Cannot find main document file name!');
        }

        $this->mainDocumentName = $matches['filename'];
        if (strpos($this->mainDocumentName, '/') === 0) {
            $this->mainDocumentName = substr($this->mainDocumentName, 1);
        }

        if (!isset($this->archivedFiles[$this->mainDocumentName])) {
            throw new \Exception('Cannot find main document file content!');
        }

        $this->mainDocumentContent = $this->za->getFromIndex($this->archivedFiles[$this->mainDocumentName]['index']);
    }

    public function setVariablePrefix($variablePrefix)
    {
        $this->variablePrefix = $variablePrefix;
    }

    public function replace(array $replacements)
    {
        $this->mainDocumentContent = str_ireplace(
            array_map(
                function ($variable) {
                    return $this->variablePrefix . $variable;
                },
                array_keys($replacements)
            ),
            $replacements,
            $this->mainDocumentContent
        );
    }

    public function save($remove = false)
    {
        if ($this->type == self::TYPE_RTF) {
            file_put_contents($this->fileName, $this->mainDocumentContent);
        } else {
            $this->za->deleteIndex($this->archivedFiles[$this->mainDocumentName]['index']);
            $this->za->addFromString($this->mainDocumentName, $this->mainDocumentContent, ZipArchive::FL_OVERWRITE);

            $this->za->close();
        }

        if ($remove) {
            $content = file_get_contents($this->fileName);
            $this->removeTempFile();
            return $content;
        }

        return true;
    }

    public function getContent()
    {
        return $this->save(true);
    }

    private function removeTempFile()
    {
        if (!empty($this->tempFileName)) {
            @unlink($this->tempFileName);
        }
    }

    public function __destructor()
    {
        $this->removeTempFile();
    }
}
