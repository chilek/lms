#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2024 LMS Developers
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

$script_parameters = array(
    'stdout' => 'c',
    'section:' => 's:',
);

$script_help = <<<EOF
-c, --stdout                    write cash import file contents to stdout
-s, --section=<section-name>    section name from lms configuration where settings
                                are stored
EOF;

require_once('script-options.php');

// Initialize Session, Auth and LMS classes

$stdout = isset($options['stdout']);
$config_section = isset($options['section']) && preg_match('/^[a-z0-9-_]+$/i', $options['section'])
    ? $options['section']
    : 'cashimport';

$cashimport_server = ConfigHelper::getConfig($config_section . '.server');
$cashimport_username = ConfigHelper::getConfig($config_section . '.username');
$cashimport_password = ConfigHelper::getConfig($config_section . '.password');
$cashimport_filename_pattern = ConfigHelper::getConfig($config_section . '.filename_pattern', '', true);

if (empty($cashimport_server) || empty($cashimport_username) || empty($cashimport_password)) {
    die("Fatal error: mailbox credentials are not set!" . PHP_EOL);
}

$cashimport_use_seen_flag = ConfigHelper::checkConfig($config_section . '.use_seen_flag', true);
$cashimport_sender_email = ConfigHelper::getConfig($config_section . '.sender_email', '', true);
$cashimport_folder = ConfigHelper::getConfig($config_section . '.folder', 'INBOX');

$ih = @imap_open("{" . $cashimport_server . "}" . $cashimport_folder, $cashimport_username, $cashimport_password);
if (!$ih) {
    die('Cannot connect to mail server: ' . imap_last_error() . '!' . PHP_EOL);
}

$posts = imap_search(
    $ih,
    ($cashimport_use_seen_flag ? 'UNSEEN' : 'ALL')
        . ($cashimport_sender_email ? ' FROM "' . $cashimport_sender_email . '"' : '')
);
if (empty($posts)) {
    imap_close($ih);
    die;
}

function decode_filename($encoded_filename)
{
    $filename = '';

    if (preg_match('/^=\?/', $encoded_filename)) {
        $elems = imap_mime_header_decode($encoded_filename);
        if (!empty($elems)) {
            foreach ($elems as $elem) {
                if ($elem->charset != 'default') {
                    $filename .= iconv($elem->charset, 'utf-8', $elem->text);
                } else {
                    $filename .= $elem->text;
                }
            }
        }
    } else {
        $filename = $encoded_filename;
    }

    return $filename;
}

foreach ($posts as $postid) {
    $files = array();
    $post = imap_fetchstructure($ih, $postid);
    //print_r($post);
    if ($post->type == 1) {
        $parts = $post->parts;
        //print_r($parts);

        foreach ($parts as $partid => $part) {
            if ($part->ifdisposition) {
                if (in_array(strtolower($part->disposition), array('attachment', 'inline'))
                    && $part->ifdparameters) {
                    foreach ($part->dparameters as $dparameter) {
                        if (strtolower($dparameter->attribute) == 'filename') {
                            $body = imap_fetchbody($ih, $postid, $partid + 1);
                            if ($part->encoding == 3) {
                                $body = imap_base64($body);
                            }
                            $files[] = array(
                                'name' => decode_filename($dparameter->value),
                                'contents' => $body,
                            );
                        }
                    }
                }
            } elseif ($part->ifsubtype) {
                if (strtolower($part->subtype) == 'octet-stream' && $part->ifparameters) {
                    foreach ($part->parameters as $parameter) {
                        if (strtolower($parameter->attribute) == 'name') {
                            $body = imap_fetchbody($ih, $postid, $partid + 1);
                            if ($part->encoding == 3) {
                                $body = imap_base64($body);
                            }
                            $files[] = array(
                                'name' => decode_filename($parameter->value),
                                'contents' => $body,
                            );
                        }
                    }
                } elseif (strtolower($part->subtype) == 'mixed' && isset($part->parts)) {
                    foreach ($part->parts as $subpartid => $subpart) {
                        if ($subpart->type == 3
                            && (($subpart->ifdisposition
                                && in_array(strtolower($subpart->disposition), array('attachment', 'inline'))
                                && $subpart->ifdparameters)
                            || (!$subpart->ifdisposition && !$subpart->ifdparameters && $subpart->ifparameters))) {
                            if ($subpart->ifdparameters) {
                                $parameters = $subpart->dparameters;
                            } else {
                                $parameters = $subpart->parameters;
                            }
                            foreach ($parameters as $parameter) {
                                if (strtolower($parameter->attribute) == 'filename' || strtolower($parameter->attribute) == 'name') {
                                    $body = imap_fetchbody($ih, $postid, ($partid + 1) . '.' . ($subpartid + 1));
                                    if ($subpart->encoding == 3) {
                                        $body = imap_base64($body);
                                    }
                                    $files[] = array(
                                        'name' => decode_filename($parameter->value),
                                        'contents' => $body,
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
    } elseif ($post->type == 3 && $post->ifdisposition
        && in_array(strtolower($post->disposition), array('attachment', 'inline'))
        && $post->ifdparameters) {
        foreach ($post->dparameters as $dparameter) {
            if (strtolower($dparameter->attribute) == 'filename') {
                $body = imap_fetchbody($ih, $postid, '1');
                if ($post->encoding == 3) {
                    $body = imap_base64($body);
                }
                $files[] = array(
                    'name' => decode_filename($dparameter->value),
                    'contents' => $body,
                );
            }
        }
    }

    if ($cashimport_use_seen_flag) {
        imap_setflag_full($ih, $postid, "\\Seen");
    } else {
        imap_clearflag_full($ih, $postid, "\\Seen");
    }

    if (empty($files)) {
        continue;
    }

    foreach ($files as $file) {
        if (!empty($cashimport_filename_pattern) && !preg_match('/' . $cashimport_filename_pattern . '/', $file['name'])) {
            continue;
        }

        if ($stdout) {
            $import_file = 'php://stdout';
        } else {
            $import_file = $file['name'];
            if (file_exists($import_file)) {
                if (preg_match('/^(?<name>.+)\.(?<extension>[^\.]+)$/', $import_file, $m)) {
                    $name = $m['name'];
                    $extension = $m['extension'];
                } else {
                    $name = $import_file;
                    $extension = '';
                }
                $nr = 1;
                while (file_exists($name . '_' . $nr . (empty($extension) ? '' : '.' . $extension))) {
                    $nr++;
                }
                $import_file = $name . '_' . $nr . (empty($extension) ? '' : '.' . $extension);
            }
        }
        $fh = fopen($import_file, "w");
        if (empty($fh)) {
            echo "Couldn't write contents to $import_file file!" . PHP_EOL;
            continue;
        }
        fwrite($fh, $file['contents']);
        fclose($fh);
    }
}

imap_close($ih);
