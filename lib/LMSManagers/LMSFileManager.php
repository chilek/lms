<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2020 LMS Developers
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

class LMSFileManager extends LMSManager implements LMSFileManagerInterface
{
    public function GetFileContainers($type, $id)
    {
        if (!preg_match('/^[a-z0-9_]+$/', $type)
            || !preg_match('/^[0-9]+$/', $id)) {
            return null;
        }

        $result[$type] = $this->db->GetAll('SELECT c.*, u.name AS creatorname
			FROM filecontainers c
			LEFT JOIN vusers u ON u.id = c.creatorid
			WHERE c.' . $type . ' = ?', array($id));
        if (empty($result)) {
            return null;
        }

        foreach ($result[$type] as &$container) {
            $container['description'] = wordwrap($container['description'], 120, '<br>', true);
            $container['files'] = $this->db->GetAll('SELECT * FROM files
				WHERE containerid = ?', array($container['id']));
        }
        unset($container);

        return $result;
    }

    public function GetFile($id)
    {
        if (!preg_match('/^[0-9]+$/', $id)) {
            return null;
        }

        $file = $this->db->GetRow('SELECT * FROM files WHERE id = ?', array($id));
        if (empty($file)) {
            return null;
        }

        $file['filepath'] = DOC_DIR . DIRECTORY_SEPARATOR . substr($file['md5sum'], 0, 2) . DIRECTORY_SEPARATOR . $file['md5sum'];

        return $file;
    }

    public function GetZippedFileContainer($id)
    {
        if (!preg_match('/^[0-9]+$/', $id)) {
            return null;
        }

        $container = $this->db->GetRow('SELECT *
			FROM filecontainers
			WHERE id = ?', array($id));
        if (empty($container)) {
            return null;
        }

        $files = $this->db->GetAll('SELECT *
			FROM files
			WHERE containerid = ?', array($id));
        if (empty($files)) {
            return null;
        }

        $zipname = mb_substr($container['description'], 0, 20);
        $zipname = preg_replace('/[^a-zA-Z0-9\._]/', '_', $zipname) . '.zip';

        if (!extension_loaded('zip')) {
            die('<B>Zip extension not loaded! In order to use this extension you must compile PHP with zip support by using the --enable-zip configure option. </B>');
        }

        $filename = tempnam('/tmp', 'LMS_ZIPPED_CONTAINER_') . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($filename, ZIPARCHIVE::CREATE)) {
            foreach ($files as $file) {
                $filepath = DOC_DIR . DIRECTORY_SEPARATOR . substr($file['md5sum'], 0, 2) . DIRECTORY_SEPARATOR . $file['md5sum'];
                $zip->addFile($filepath, $file['filename']);
            }
            $zip->close();
        }

        // send zip archive package to web browser
        header('Content-type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipname . '"');
        header('Pragma: public');
        readfile($filename);

        // remove already unneeded zip archive package file
        unlink($filename);
    }

    /**
     * @param array $params
     *      description - string - container description
     *      files - array() of file description structures
     *          if 'data' component of file structure is defined then we treat it as file contents
     *      type - string - type of assigned resource (customerid, netdevid, netnodeid, etc.)
     */
    public function AddFileContainer(array $params)
    {
        if (!isset($params['type']) || !preg_match('/^[a-z0-9_]+$/', $params['type'])
            || !isset($params['resourceid']) || !preg_match('/^[0-9]+$/', $params['resourceid'])) {
            return null;
        }

        $this->db->Execute(
            'INSERT INTO filecontainers (' . $params['type'] . ', creationdate, creatorid,
			description) VALUES (?, ?NOW?, ?, ?)',
            array($params['resourceid'], Auth::GetCurrentUser(), $params['description'])
        );
        $containerid = $this->db->GetLastInsertID('filecontainers');
        if (empty($containerid)) {
            return null;
        }

        $document_manager = new LMSDocumentManager($this->db, $this->auth, $this->cache, $this->syslog);

        foreach ($params['files'] as $file) {
            if (isset($file['data'])) {
                $md5sum = md5($file['data']);
            } else {
                $md5sum = md5_file($file['name']);
            }
            $filename = basename($file['name']);

            $path = DOC_DIR . DIRECTORY_SEPARATOR . substr($md5sum, 0, 2);
            $name = $path . DIRECTORY_SEPARATOR . $md5sum;

            if ($document_manager->DocumentAttachmentExists($md5sum)
                || $this->FileExists($md5sum)) {
                if (isset($file['data'])) {
                    $filesize = strlen($file['data']);
                    $sha256sum = hash('sha256', $file['data']);
                } else {
                    $filesize = filesize($file['name']);
                    $sha256sum = hash_file('sha256', $file['name']);
                }
                if (filesize($name) != $filesize
                    || hash_file('sha256', $name) != $sha256sum) {
                    die(trans('Specified file exists in database!'));
                    break;
                }
            }

            $this->db->Execute('INSERT INTO files (containerid, filename, contenttype, md5sum)
				VALUES(?, ?, ?, ?)', array($containerid, $filename, $file['type'], $md5sum));

            @mkdir($path, 0700);
            if (!file_exists($name)) {
                if (isset($file['data'])) {
                    if (file_put_contents($name, $file['data']) === false) {
                        die(trans('Can\'t save file in "$a" directory!', $path));
                    }
                } elseif (!@rename($file['name'], $name)) {
                    die(trans('Can\'t save file in "$a" directory!', $path));
                }
            }
        }
    }

    /**
     * @param array $params
     *      id - integer - container id
     *      description - string - container description
     */
    public function UpdateFileContainer(array $params)
    {
        if (!isset($params['id']) || !preg_match('/^[0-9]+$/', $params['id'])) {
            return null;
        }

        return $this->db->Execute(
            'UPDATE filecontainers SET description = ? WHERE id = ?',
            array($params['description'], $params['id'])
        );
    }

    protected function DeleteFileByMD5SUM($md5sum)
    {
        $document_manager = new LMSDocumentManager($this->db, $this->auth, $this->cache, $this->syslog);
        if (!$document_manager->DocumentAttachmentExists($md5sum)
            && $this->FileExists($md5sum) <= 1) {
            @unlink(DOC_DIR . DIRECTORY_SEPARATOR . substr($md5sum, 0, 2)
                . DIRECTORY_SEPARATOR  . $md5sum);
        }
    }

    public function DeleteFileContainer($id)
    {
        $md5sums = $this->db->GetCol('SELECT DISTINCT md5sum FROM files WHERE containerid = ?', array($id));
        if (!empty($md5sums)) {
            foreach ($md5sums as $md5sum) {
                $this->DeleteFileByMD5SUM($md5sum);
            }
        }

        return $this->db->Execute('DELETE FROM filecontainers WHERE id = ?', array($id));
    }

    public function DeleteFileContainers($type, $resourceid)
    {
        if (!preg_match('/^[a-z0-9_]+$/', $type)
            || !preg_match('/^[0-9]+$/', $resourceid)) {
            return null;
        }

        $md5sums = $this->db->GetCol('SELECT DISTINCT md5sum
			FROM files f
			JOIN filecontainers c ON c.id = f.containerid
			WHERE c.' . $type . ' = ?', array($resourceid));
        if (!empty($md5sums)) {
            foreach ($md5sums as $md5sum) {
                $this->DeleteFileByMD5SUM($md5sum);
            }
        }

        return $this->db->Execute('DELETE FROM filecontainers
			WHERE ' . $type . ' = ?', array($resourceid));
    }

    public function FileExists($md5sum)
    {
        return $this->db->GetOne('SELECT COUNT(containerid) FROM files WHERE md5sum = ?', array($md5sum));
    }
}
