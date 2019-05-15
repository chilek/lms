<?php

/*
 *  LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

if (defined('USERPANEL_SETUPMODE')) {
    function module_setup()
    {
        global $SMARTY,$LMS;

        $SMARTY->assign('disable_transferform', ConfigHelper::getConfig('userpanel.disable_transferform'));
        $SMARTY->assign('disable_invoices', ConfigHelper::getConfig('userpanel.disable_invoices'));
        $SMARTY->assign('invoice_duplicate', ConfigHelper::getConfig('userpanel.invoice_duplicate'));
        $SMARTY->assign('show_tariffname', ConfigHelper::getConfig('userpanel.show_tariffname'));
        $SMARTY->assign('show_speeds', ConfigHelper::getConfig('userpanel.show_speeds'));
        $SMARTY->assign('show_last_years', ConfigHelper::getConfig('userpanel.show_last_years'));
        $SMARTY->assign('aggregate_documents', ConfigHelper::checkConfig('userpanel.aggregate_documents'));
        $SMARTY->assign('speed_unit_type', ConfigHelper::getConfig('userpanel.speed_unit_type'));
        $SMARTY->assign('speed_unit_aggregation_threshold', ConfigHelper::getConfig('userpanel.speed_unit_aggregation_threshold'));
        $SMARTY->display('module:finances:setup.html');
    }

    function module_submit_setup()
    {
        global $SMARTY,$DB;
        if ($_POST['disable_transferform']) {
            $DB->Execute('UPDATE uiconfig SET value = \'1\' WHERE section = \'userpanel\' AND var = \'disable_transferform\'');
        } else {
            $DB->Execute('UPDATE uiconfig SET value = \'0\' WHERE section = \'userpanel\' AND var = \'disable_transferform\'');
        }
        if ($_POST['disable_invoices']) {
            $DB->Execute('UPDATE uiconfig SET value = \'1\' WHERE section = \'userpanel\' AND var = \'disable_invoices\'');
        } else {
            $DB->Execute('UPDATE uiconfig SET value = \'0\' WHERE section = \'userpanel\' AND var = \'disable_invoices\'');
        }
        if ($_POST['invoice_duplicate']) {
            $DB->Execute('UPDATE uiconfig SET value = \'1\' WHERE section = \'userpanel\' AND var = \'invoice_duplicate\'');
        } else {
            $DB->Execute('UPDATE uiconfig SET value = \'0\' WHERE section = \'userpanel\' AND var = \'invoice_duplicate\'');
        }
        if ($_POST['show_tariffname']) {
            $DB->Execute('UPDATE uiconfig SET value = \'1\' WHERE section = \'userpanel\' AND var = \'show_tariffname\'');
        } else {
            $DB->Execute('UPDATE uiconfig SET value = \'0\' WHERE section = \'userpanel\' AND var = \'show_tariffname\'');
        }
        if ($_POST['show_speeds']) {
            $DB->Execute('UPDATE uiconfig SET value = \'1\' WHERE section = \'userpanel\' AND var = \'show_speeds\'');
        } else {
            $DB->Execute('UPDATE uiconfig SET value = \'0\' WHERE section = \'userpanel\' AND var = \'show_speeds\'');
        }
        $DB->Execute(
            'UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?',
            array(str_replace(',', '.', floatval(str_replace(',', '.', $_POST['show_last_years']))),
            'userpanel',
            'show_last_years')
        );
        if ($_POST['aggregate_documents']) {
            $DB->Execute('UPDATE uiconfig SET value = \'1\' WHERE section = \'userpanel\' AND var = \'aggregate_documents\'');
        } else {
            $DB->Execute('UPDATE uiconfig SET value = \'0\' WHERE section = \'userpanel\' AND var = \'aggregate_documents\'');
        }

        if ($_POST['speed_unit_type']) {
            $speed_unit_type = intval($_POST['speed_unit_type']);
            if ($speed_unit_type != 1000 && $speed_unit_type != 1024) {
                $speed_unit_type = 1000;
            }
            $DB->Execute(
                'UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?',
                array($speed_unit_type, 'userpanel', 'speed_unit_type')
            );
        }

        if ($_POST['speed_unit_aggregation_threshold']) {
            $speed_unit_aggregation_threshold = intval($_POST['speed_unit_aggregation_threshold']);
            if ($speed_unit_aggregation_threshold < 1 || $speed_unit_aggregation_threshold > 100) {
                $speed_unit_aggregation_threshold = 5;
            }
            $DB->Execute(
                'UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?',
                array($speed_unit_aggregation_threshold, 'userpanel', 'speed_unit_aggregation_threshold')
            );
        }

        header('Location: ?m=userpanel&module=finances');
    }
}

function module_transferform()
{
    include 'transferform.php';
}

function module_invoice()
{
    include 'invoice.php';
}

function module_main()
{
    include 'main.php';
}
