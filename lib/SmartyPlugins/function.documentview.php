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
    static $vars = array('type', 'name', 'url', 'id', 'text');
    static $types = array(
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

    $result = '';
    foreach ($vars as $var) {
        if (isset($params[$var])) {
            $$var = $params[$var];
        } else {
            return $result;
        }
    }
    $external = isset($params['external']) && $params['external'] == 'true';

    if (isset($types[$type])) {
        $type = $types[$type];
    } else {
        $type = '';
    }

    $result .= '<div class="documentviewdialog" id="documentviewdialog-' . $id . '" title="' . $name . '" style="display: none;"
		data-url="' . $url . '"></div>';

    $result .= '<A href="' . $url . '"';
    if (empty($type)) {
        $result .=  ' class="lms-ui-button-view" ' . ($external ? ' rel="external"' : '');
    } else {
        $result .= ' id="documentview-' . $id . '" data-dialog-id="documentviewdialog-' . $id . '" '
            . 'class="lms-ui-button-view documentview documentview-' . $type . '"';
    }
    $result .= '>' . $text . '</A>';

    return $result;
}
