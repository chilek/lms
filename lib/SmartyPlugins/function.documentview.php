<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

function smarty_function_documentview($params, $template)
{
    static $vars = array('type', 'name', 'url', 'id');
    static $preview_types = array(
        'image/jpeg' => 'image',
        'image/png' => 'image',
        'image/gif' => 'image',
        'audio/mp3' => 'audio',
        'audio/ogg' => 'audio',
        'audio/oga' => 'audio',
        'audio/wav' => 'audio',
        'video/mp4' => 'video',
        'video/ogg' => 'video',
        'video/webm' => 'video',
        'application/pdf' => 'pdf',
    );
    static $office2pdf_command = null;

    if (!isset($office2pdf_command)) {
        $office2pdf_command = ConfigHelper::getConfig('documents.office2pdf_command', '', true);
    }

    $result = '';
    foreach ($vars as $var) {
        if (isset($params[$var])) {
            ${$var} = $params[$var];
        } else {
            return $result;
        }
    }
    $external = isset($params['external']) && $params['external'] == 'true';

    $preview_type = $preview_types[$type] ?? '';

    if (empty($params['text'])) {
        $office_document = preg_match('#^application/(rtf|.+(oasis|opendocument|openxml).+)$#i', $type);

        if (!empty($office2pdf_command) && $office_document) {
            $preview_type = 'office';
        }
    }

    $result .= '<span class="documentview">';

    $result .= '<div class="documentviewdialog" id="documentviewdialog-' . $id . '" title="' . $name . '" style="display: none;"'
        . ' data-url="' . $url . '"></div>';

    $result .= '<a href="' . $url . '" data-title="' . $name . '" data-name="' . $name . '" data-type="' . $type . '"';
    if (empty($preview_type)) {
        $result .=  ' class="lms-ui-button" ' . ($external ? ' rel="external"' : '');
    } else {
        $result .= ' id="documentview-' . $id . '" data-dialog-id="documentviewdialog-' . $id . '" '
            . 'class="lms-ui-button" data-preview-type="' . $preview_type . '"';
    }

    if (empty($params['text'])) {
        $icon_classes = array(
            'lms-ui-icon-view',
            'preview',
        );

        if (preg_match('/pdf/i', $type)) {
            $icon_classes[] = 'pdf';
        } elseif ($office_document) {
            if (preg_match('/(text|rtf|msword|msword|openxmlformats.+document)/i', $type)) {
                $icon_classes[] = 'doc';
            } elseif (preg_match('/(spreadsheet|ms-excel|openxmlformats.+sheet)/i', $type)) {
                $icon_classes[] = 'xls';
            }
        }

        $text = $name . ' <i class="' . implode(' ', $icon_classes) . '"></i>';
    } else {
        $text = $params['text'];
    }

    $result .= '>' . $text . '</a>';

    if (empty($params['text']) && $preview_type == 'office') {
        $result .= LMSSmartyPlugins::buttonFunction(
            array(
                'type' => 'link',
                'icon' => 'download',
                'class' => 'download',
            ),
            $template
        );
    }

    $result .= '</span>';

    return $result;
}
