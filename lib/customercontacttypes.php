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

function format_customer_phone($contact) {
	return '<a class="phone_number" href="tel:' . $contact . '">' . $contact . '</a>';
}

function format_customer_email($contact) {
	return '<a href="mailto:' . $contact . '">' . $contact . '</a>';
}

function format_customer_account($contact) {
	return format_bankaccount($contact);
}

function format_customer_url($contact) {
	return '<a href="' . $contact . '">' . $contact . '</a>';
}

function validate_customer_phones(&$customerdata, &$contacts, &$error) {
	foreach ($customerdata['phones'] as $idx => &$val) {
		$phone = trim($val['contact']);
		$name = trim($val['name']);
		$type = !empty($val['type']) ? array_sum($val['type']) : NULL;
		if ($type == CONTACT_DISABLED)
			$type |= CONTACT_LANDLINE;

		$val['type'] = $type;

		if ($name && !$phone)
			$error['contact' . $idx] = trans('Phone number is required!');
		elseif ($phone)
			$contacts[] = array('name' => $name, 'contact' => $phone, 'type' => empty($type) ? CONTACT_LANDLINE : $type);
	}
}

function validate_customer_emails(&$customerdata, &$contacts, &$error) {
	foreach ($customerdata['emails'] as $idx => &$val) {
		$email = trim($val['contact']);
		$name = trim($val['name']);
		$type = !empty($val['type']) ? array_sum($val['type']) : NULL;
		$type |= CONTACT_EMAIL;

		if ($type & (CONTACT_INVOICES | CONTACT_DISABLED))
			$emaileinvoice = true;

		$val['type'] = $type;

		if ($email != '' && !check_email($email))
			$error['email' . $idx] = trans('Incorrect email!');
		elseif ($name && !$email)
			$error['email' . $idx] = trans('Email address is required!');
		elseif ($email)
			$contacts[] = array('name' => $name, 'contact' => $email, 'type' => $type);
	}
}

function validate_customer_accounts(&$customerdata, &$contacts, &$error) {
	foreach ($customerdata['accounts'] as $idx => &$val) {
		$account = trim($val['contact']);
		$name = trim($val['name']);
		$type = !empty($val['type']) ? array_sum($val['type']) : NULL;
		$type |= CONTACT_BANKACCOUNT;

		$val['type'] = $type;

		if ($account != '' && !check_bankaccount($account))
			$error['account' . $idx] = trans('Incorrect bank account!');
		elseif ($name && !$account)
			$error['account' . $idx] = trans('Bank account is required!');
		elseif ($account)
			$contacts[] = array('name' => $name, 'contact' => $account, 'type' => $type);
	}
}

function validate_customer_urls(&$customerdata, &$contacts, &$error) {
	foreach ($customerdata['urls'] as $idx => &$val) {
		$url = trim($val['contact']);
		$name = trim($val['name']);
		$type = !empty($val['type']) ? array_sum($val['type']) : NULL;
		$type |= CONTACT_URL;

		$val['type'] = $type;

		if ($url != '' && !check_url($url))
			$error['url' . $idx] = trans('Incorrect URL address!');
		elseif ($name && !$url)
			$error['url' . $idx] = trans('URL address is required!');
		elseif ($url)
			$contacts[] = array('name' => $name, 'contact' => $url, 'type' => $type);
	}
}

$CUSTOMERCONTACTTYPES = array(
	'phone' => array(
		'ui' => array(
			'legend' => array(
				'icon' => 'img/phone.gif',
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
				'icon' => 'img/mail.gif',
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
				'icon' => 'img/card.gif',
				'text' => trans('Alternative bank accounts'),
			),
			'inputtype' => 'text',
			'size' => 50,
			'tip' => trans('Enter bank account (optional)'),
			'flags' => array(
				CONTACT_MOBILE => array(
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
				'icon' => 'img/network.gif',
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
);

global $SMARTY;

if (isset($SMARTY))
	$SMARTY->assign('_CUSTOMERCONTACTTYPES', $CUSTOMERCONTACTTYPES);

?>
