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

function format_customer_phone($contact)
{
    $call_phone_url = ConfigHelper::getConfig('phpui.call_phone_url', '');
    if (!empty($call_phone_url)) {
        $call_phone_url = str_replace('%phone', $contact['contact'], $call_phone_url);
    }
    return '<a href="?m=messageadd&customerid='  . $contact['customerid'] . '&type=' . MSG_SMS . '&contactid=' . $contact['id'] . '">'
        . '<i class="lms-ui-icon-quick-send"></i></a>'
        . '&nbsp;<a class="phone_number" href="tel:' . $contact['contact'] . '">' . $contact['contact'] . '</a>&nbsp;'
        . (isset($call_phone_url) ? '<a href="' . $call_phone_url . '"><i class="fas fa-phone"></i></a>' : '');
}

function format_customer_email($contact)
{
    return '<a href="?m=messageadd&customerid='  . $contact['customerid'] . '&type=' . MSG_MAIL . '&contactid=' . $contact['id'] . '">'
        . '<i class="lms-ui-icon-quick-send"></i></a>'
        . '&nbsp;<a href="mailto:' . $contact['contact'] . '">' . $contact['contact'] . '</a>';
}

function format_customer_account($contact)
{
    return format_bankaccount($contact['contact']);
}

function format_customer_url($contact)
{
    return '<a href="' . $contact['contact'] . '">' . $contact['contact'] . '</a>';
}

function format_customer_im($contact)
{
    switch ($contact['type'] & CONTACT_IM) {
        case CONTACT_IM_GG:
            return trans('Gadu-Gadu') . ': ' . '<IMG src="http://status.gadu-gadu.pl/users/status.asp?id=' . $contact['contact'] . '&styl=1" alt=""> '
                . '<a href="gg:' . $contact['contact'] . '">' . $contact['contact'] . '</a>';
            break;
        case CONTACT_IM_YAHOO:
            return trans('Yahoo') . ': ' . '<IMG src="http://opi.yahoo.com/online?u=' . $contact['contact'] . '&m=g&t=5"  alt=""> '
                . '<a href="ymsgr:sendIM?' . $contact['contact'] . '">' . $contact['contact'] . '</a>';
            break;
        case CONTACT_IM_SKYPE:
//          return trans('Skype') . ': ' . '<IMG src="http://mystatus.skype.com/smallicon/' . $contact['contact'] . '"  alt=""> '
            return trans('Skype') . ': '
                . '<a href="skype:' . $contact['contact'] . '">' . $contact['contact'] . '</a>';
            break;
        case CONTACT_IM_FACEBOOK:
            return trans('Facebook') . ': '
                . '<a href="https://m.me/' . $contact['contact'] . '">' . $contact['contact'] . '</a>';
            break;
    }
}

function format_customer_representative($contact)
{
    return '<span class="bold">' . $contact['contact'] . '</span>';
}

function validate_customer_phones(&$customerdata, &$contacts, &$error)
{
    if (!isset($customerdata['phones'])) {
        return;
    }
    foreach ($customerdata['phones'] as $idx => &$val) {
        $phone = trim($val['contact']);
        $name = trim($val['name']);
        $type = !empty($val['type']) ? array_sum($val['type']) : null;
        if (!($type & CONTACT_MOBILE)) {
            $type |= CONTACT_LANDLINE;
        }

        $val['type'] = $type;

        if ($name && !$phone) {
            $error['contact' . $idx] = trans('Phone number is required!');
        } elseif ($phone) {
            $contacts[] = array('name' => $name, 'contact' => $phone, 'type' => empty($type) ? CONTACT_LANDLINE : $type);
        }
    }
}

function validate_customer_emails(&$customerdata, &$contacts, &$error)
{
    if (!isset($customerdata['emails'])) {
        return;
    }
    foreach ($customerdata['emails'] as $idx => &$val) {
        $email = trim($val['contact']);
        $name = trim($val['name']);
        $type = !empty($val['type']) ? array_sum($val['type']) : null;
        $type |= CONTACT_EMAIL;

        if ($type & (CONTACT_INVOICES | CONTACT_DISABLED)) {
            $emaileinvoice = true;
        }

        $val['type'] = $type;

        if ($email != '' && !check_email($email)) {
            $error['email' . $idx] = trans('Incorrect email!');
        } elseif ($name && !$email) {
            $error['email' . $idx] = trans('Email address is required!');
        } elseif ($email) {
            $contacts[] = array('name' => $name, 'contact' => $email, 'type' => $type);
        }
    }
}

function validate_customer_accounts(&$customerdata, &$contacts, &$error)
{
    if (!isset($customerdata['accounts'])) {
        return;
    }
    foreach ($customerdata['accounts'] as $idx => &$val) {
        $account = trim($val['contact']);
        $name = trim($val['name']);
        $type = !empty($val['type']) ? array_sum($val['type']) : null;
        $type |= CONTACT_BANKACCOUNT;

        $val['type'] = $type;

        if ($account != '' && !check_bankaccount($account)) {
            $error['account' . $idx] = trans('Incorrect bank account!');
        } elseif ($name && !$account) {
            $error['account' . $idx] = trans('Bank account is required!');
        } elseif ($account) {
            $contacts[] = array('name' => $name, 'contact' => $account, 'type' => $type);
        }
    }
}

function validate_customer_urls(&$customerdata, &$contacts, &$error)
{
    if (!isset($customerdata['urls'])) {
        return;
    }
    foreach ($customerdata['urls'] as $idx => &$val) {
        $url = trim($val['contact']);
        $name = trim($val['name']);
        $type = !empty($val['type']) ? array_sum($val['type']) : null;
        $type |= CONTACT_URL;

        $val['type'] = $type;

        if ($url != '' && !check_url($url)) {
            $error['url' . $idx] = trans('Incorrect URL address!');
        } elseif ($name && !$url) {
            $error['url' . $idx] = trans('URL address is required!');
        } elseif ($url) {
            $contacts[] = array('name' => $name, 'contact' => $url, 'type' => $type);
        }
    }
}

function validate_customer_ims(&$customerdata, &$contacts, &$error)
{
    if (!isset($customerdata['ims'])) {
        return;
    }
    foreach ($customerdata['ims'] as $idx => &$val) {
        $im = trim($val['contact']);
        $name = trim($val['name']);
        $type = !empty($val['type']) ? array_sum($val['type']) : 0;
        $type |= $val['typeselector'];

        $val['type'] = $type;

        $imtype = $type & CONTACT_IM;
        if ($im != '' && (($imtype == CONTACT_IM_GG && !check_gg($im))
            || ($imtype == CONTACT_IM_YAHOO && !check_yahoo($im))
            || ($imtype == CONTACT_IM_SKYPE && !check_skype($im))
            || ($imtype == CONTACT_IM_FACEBOOK && !check_facebook($im)))) {
            $error['im' . $idx] = trans('Incorrect IM uin!');
        } elseif ($name && !$im) {
            $error['im' . $idx] = trans('IM uid is required!');
        } elseif ($im) {
            $contacts[] = array('name' => $name, 'contact' => $im, 'type' => $type);
        }
    }
}

function validate_customer_representatives(&$customerdata, &$contacts, &$error)
{
    if (!isset($customerdata['representatives'])) {
        return;
    }
    foreach ($customerdata['representatives'] as $idx => &$val) {
        $name = trim($val['contact']);
        $data = trim($val['name']);
        $type = !empty($val['type']) ? array_sum($val['type']) : null;
        $type |= CONTACT_REPRESENTATIVE;

        $val['type'] = $type;

        if ($name) {
            $contacts[] = array('name' => $data, 'contact' => $name, 'type' => $type);
        }
    }
}

$CUSTOMERCONTACTTYPES = array(
    'phone' => array(
        'ui' => array(
            'legend' => array(
                'icon' => 'lms-ui-icon-phone fa-fw',
                'text' => trans('Contact phones'),
            ),
            'inputtype' => 'tel',
            'size' => 16,
            'tip' => trans('Enter contact phone'),
            'flags' => array(
                CONTACT_MOBILE => array(
                    'label' => $CONTACTTYPES[CONTACT_MOBILE],
                ),
                CONTACT_FAX => array(
                    'label' => $CONTACTTYPES[CONTACT_FAX],
                ),
                CONTACT_NOTIFICATIONS => array(
                    'label' => $CONTACTTYPES[CONTACT_NOTIFICATIONS],
                    'tip' => trans('Check if send notification'),
                ),
                CONTACT_DOCUMENTS => array(
                    'label' => $CONTACTTYPES[CONTACT_DOCUMENTS],
                    'tip' => trans('Check if contact should be printed on documents'),
                ),
                CONTACT_DISABLED => array(
                    'label' => $CONTACTTYPES[CONTACT_DISABLED],
                    'tip' => trans('Not visible by the customer in electronic Customer Service Representative'),
                ),
            ),
        ),
        'flagmask' => CONTACT_MOBILE | CONTACT_FAX | CONTACT_LANDLINE,
        'formatter' => 'format_customer_phone',
        'validator' => 'validate_customer_phones',
    ),
    'email' => array(
        'ui' => array(
            'legend' => array(
                'icon' => 'lms-ui-icon-mail fa-fw',
                'text' => trans('Email addresses'),
            ),
            'inputtype' => 'email',
            'size' => 23,
            'tip' => trans('Enter e-mail address (optional)'),
            'flags' => array(
                CONTACT_INVOICES => array(
                    'label' => $CONTACTTYPES[CONTACT_INVOICES],
                    'tip' => trans('Check if sent electronic invoices on this email'),
                ),
                CONTACT_NOTIFICATIONS => array(
                    'label' => $CONTACTTYPES[CONTACT_NOTIFICATIONS],
                    'tip' => trans('Check if send notification'),
                ),
                CONTACT_TECHNICAL => array(
                    'label' => $CONTACTTYPES[CONTACT_TECHNICAL],
                    'tip' => trans('Check if send technical notification'),
                ),
                CONTACT_DOCUMENTS => array(
                    'label' => $CONTACTTYPES[CONTACT_DOCUMENTS],
                    'tip' => trans('Check if contact should be printed on documents'),
                ),
                CONTACT_DISABLED => array(
                    'label' => $CONTACTTYPES[CONTACT_DISABLED],
                    'tip' => trans('Not visible by the customer in electronic Customer Service Representative'),
                ),
            ),
        ),
        'flagmask' => CONTACT_EMAIL,
        'formatter' => 'format_customer_email',
        'validator' => 'validate_customer_emails',
    ),
    'account' => array(
        'ui' => array(
            'legend' => array(
                'icon' => 'lms-ui-icon-cash fa-fw',
                'text' => trans('Alternative bank accounts'),
            ),
            'inputtype' => 'text',
            'size' => 50,
            'tip' => trans('Enter bank account (optional)'),
            'flags' => array(
                CONTACT_INVOICES => array(
                    'label' => $CONTACTTYPES[CONTACT_INVOICES],
                    'tip' => trans('Check if bank account should be visible on invoice'),
                ),
                CONTACT_DISABLED => array(
                    'label' => $CONTACTTYPES[CONTACT_DISABLED],
                    'tip' => trans('Check if bank account should be disabled'),
                ),
            ),
        ),
        'flagmask' => CONTACT_BANKACCOUNT,
        'formatter' => 'format_customer_account',
        'validator' => 'validate_customer_accounts',
    ),
    'url' => array(
        'ui' => array(
            'legend' => array(
                'icon' => 'lms-ui-icon-www fa-fw',
                'text' => trans('URL addresses'),
            ),
            'inputtype' => 'text',
            'size' => 50,
            'tip' => trans('Enter URL address (optional)'),
            'flags' => array(
                CONTACT_DISABLED => array(
                    'label' => $CONTACTTYPES[CONTACT_DISABLED],
                    'tip' => trans('Check if URL address should be disabled'),
                ),
            ),
        ),
        'flagmask' => CONTACT_URL,
        'formatter' => 'format_customer_url',
        'validator' => 'validate_customer_urls',
    ),
    'im' => array(
        'ui' => array(
            'legend' => array(
                'icon' => 'lms-ui-icon-chat fa-fw',
                'text' => trans('Instant messengers'),
            ),
            'inputtype' => 'text',
            'size' => 16,
            'tip' => trans('Enter IM uid (optional)'),
            'typeselectors' => array(CONTACT_IM_GG, CONTACT_IM_YAHOO, CONTACT_IM_SKYPE, CONTACT_IM_FACEBOOK),
            'flags' => array(
                CONTACT_DISABLED => array(
                    'label' => $CONTACTTYPES[CONTACT_DISABLED],
                    'tip' => trans('Check if IM uid should be disabled'),
                ),
                CONTACT_NOTIFICATIONS => array(
                    'label' => $CONTACTTYPES[CONTACT_NOTIFICATIONS],
                    'tip' => trans('Check if send notification'),
                ),
            ),
        ),
        'flagmask' => CONTACT_IM_GG | CONTACT_IM_YAHOO | CONTACT_IM_SKYPE | CONTACT_IM_FACEBOOK,
        'formatter' => 'format_customer_im',
        'validator' => 'validate_customer_ims',
    ),
    'representative' => array(
        'ui' => array(
            'legend' => array(
                'icon' => 'lms-ui-icon-user fa-fw',
                'text' => trans('Representatives'),
            ),
            'inputtype' => 'text',
            'size' => 40,
            'tip' => trans('Enter representative name (optional)'),
            'flags' => array(
                CONTACT_DISABLED => array(
                    'label' => $CONTACTTYPES[CONTACT_DISABLED],
                    'tip' => trans('Check if representative should be disabled'),
                ),
            ),
        ),
        'flagmask' => CONTACT_REPRESENTATIVE,
        'formatter' => 'format_customer_representative',
        'validator' => 'validate_customer_representatives',
    ),
);

global $SMARTY;

if (isset($SMARTY)) {
    $SMARTY->assign('_CUSTOMERCONTACTTYPES', $CUSTOMERCONTACTTYPES);
}
