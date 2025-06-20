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

function GetCategories($queueid)
{
    global $LMS;

    $result = new xajaxResponse();

    if (empty($queueid)) {
        return $result;
    }

    $categories = $LMS->GetUserCategories(Auth::GetCurrentUser());
    if (empty($categories)) {
        return $result;
    }

    $queuecategories = $LMS->GetQueueCategories($queueid);

    foreach ($categories as $category) {
        $result->assign('cat' . $category['id'], 'checked', isset($queuecategories[$category['id']]));
    }

    return $result;
}

function select_location($customerid, $address_id)
{
    global $LMS;

    $JSResponse = new xajaxResponse();
    $nodes = $LMS->GetNodeLocations($customerid, !empty($address_id) && intval($address_id) > 0 ? $address_id : null);
    if (empty($nodes)) {
        $nodes = array();
    }
    $JSResponse->call('update_nodes', array_values($nodes));
    return $JSResponse;
}

function netnode_changed(
    $netnodeid,
    $netdevid,
    $form = 'ticket',
    $target_div = 'ticketnetdevs',
    $target_selectid = 'ticketnetdevid'
) {
    global $LMS, $SMARTY;

    $JSResponse = new xajaxResponse();

    $search = array();
    if (!empty($netnodeid)) {
        $search['netnode'] = $netnodeid;
    }
    $netdevlist = $LMS->GetNetDevList('name', $search);
    unset($netdevlist['total'], $netdevlist['order'], $netdevlist['direction']);

    $SMARTY->assign('netdevlist', $netdevlist);
    $SMARTY->assign($form, array('netdevid' => $netdevid));
    $SMARTY->assign('form', $form);

    $content = $SMARTY->fetch('rt' . DIRECTORY_SEPARATOR . 'rtnetdevs.html');

    $JSResponse->assign(
        $target_div,
        'innerHTML',
        $content
    );
    $JSResponse->script('initAdvancedSelectsTest("#' . $target_selectid . '");');

    return $JSResponse;
}

function queue_changed($queue)
{
    global $LMS, $SMARTY;

    $JSResponse = new xajaxResponse();
    if (empty($queue)) {
        return $JSResponse;
    }

    $templates = $LMS->GetMessageTemplatesByQueueAndType($queue, RTMESSAGE_REGULAR);
    if ($templates) {
        $SMARTY->assign('templates', $templates);
        $SMARTY->assign('tip', 'Select message template');
        $SMARTY->assign('target', '[name="ticket[body]"]');
        $JSResponse->assign('message-templates', 'innerHTML', $SMARTY->fetch('rt/rtmessagetemplates.html'));
        $JSResponse->assign('message-template-row', 'style', '');
    } else {
        $JSResponse->assign('message-template-row', 'style', 'display: none;');
    }

    $templates = $LMS->GetMessageTemplatesByQueueAndType($queue, RTMESSAGE_NOTE);
    if ($templates) {
        $SMARTY->assign('templates', $templates);
        $SMARTY->assign('tip', 'Select note template');
        $SMARTY->assign('target', '[name="ticket[note]"]');
        $JSResponse->assign('note-templates', 'innerHTML', $SMARTY->fetch('rt/rtmessagetemplates.html'));
        $JSResponse->assign('note-template-row', 'style', '');
    } else {
        $JSResponse->assign('note-template-row', 'style', 'display: none;');
    }

    $vid = $LMS->GetQueueVerifier($queue);

    if (empty($vid)) {
        return $JSResponse;
    }

    $userlist = $LMS->GetUserNames();

    $SMARTY->assign('userlist', $userlist);
    $SMARTY->assign('ticket', array('verifierid'=>$vid));
    $content = $SMARTY->fetch('rt/rtverifiers.html');

    $JSResponse->assign('rtverifiers', 'innerHTML', $content);

    $JSResponse->script('initAdvancedSelectsTest("#rtverifiers select")');

    return $JSResponse;
}

function update_contacts($customerid)
{
    global $LMS;

    $JSResponse = new xajaxResponse();

    $emails = array_filter(
        $LMS->getCustomerContacts($customerid, CONTACT_EMAIL),
        function ($contact) {
            return !($contact['type'] & CONTACT_DISABLED);
        }
    );
    usort(
        $emails,
        function ($a, $b) {
            return ($a['contact'] < $b['contact']) ? -1 : 1;
        }
    );

    $phones = array_filter(
        $LMS->getCustomerContacts($customerid, CONTACT_LANDLINE | CONTACT_MOBILE),
        function ($contact) {
            return !($contact['type'] & CONTACT_DISABLED);
        }
    );
    usort(
        $phones,
        function ($a, $b) {
            return ($a['contact'] < $b['contact']) ? -1 : 1;
        }
    );

    $JSResponse->call('update_contacts', compact('emails', 'phones'));

    return $JSResponse;
}

$LMS->RegisterXajaxFunction(array('GetCategories', 'select_location', 'netnode_changed', 'queue_changed', 'update_contacts'));
