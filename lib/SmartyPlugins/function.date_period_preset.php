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

function smarty_function_date_period_preset(array $params, Smarty_Internal_Template $template)
{
    $from_selector = isset($params['from']) ? $params['from'] : null;
    $to_selector = isset($params['to']) ? $params['to'] : null;
    $periods = isset($params['periods']) ? $params['periods'] : null;
    $time = isset($params['time']) && !empty($params['time']);

    if (!isset($from_selector) || !isset($to_selector)) {
        return;
    }

    if (!isset($periods)) {
        $periods = array('previous-month', 'current-month', 'next-month');
    } elseif (!is_array($periods)) {
        $periods = preg_split('/\s*[ ,|]\s*/', $periods);
    }

    $result = '';

    foreach ($periods as $period) {
        switch ($period) {
            case 'current-month':
                $label = trans('current month');
                $icon = 'lms-ui-icon-back';
                break;
            case 'current-year':
                $label = trans('current year');
                $icon = 'lms-ui-icon-current-year';
                break;
            case 'next-month':
                $label = trans('next month');
                $icon = 'lms-ui-icon-next';
                break;
            case 'next-year':
                $label = trans('next year');
                $icon = 'lms-ui-icon-fast-next';
                break;
            case 'previous-year':
                $label = trans('previous year');
                $icon = 'lms-ui-icon-fast-previous';
                break;
            case 'previous-month':
            default:
                $label = trans('previous month');
                $icon = 'lms-ui-icon-previous';
                break;
        }
        $result .= '<button type="button" class="lms-ui-button ' . $icon . ' lms-ui-button-date-period'
            . ($time ? ' time' : '') . '" data-from="'
            . htmlspecialchars($from_selector) . '" data-to="'
            . htmlspecialchars($to_selector) . '" data-period="' . $period . '" title="' . $label . '">'
            . '<i></i></button>&nbsp;';
    }

    return '<div class="lms-ui-date-period-wrapper">' . $result . '</div>';
}
