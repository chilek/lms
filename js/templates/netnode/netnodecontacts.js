/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2026 LMS Developers
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

(function() {
	var netNodeContactIndex = 0;

	$('#netnode-contact-groups').find('.netnode-contact').each(function() {
		var index = parseInt($(this).attr('data-contact-index'));
		if (!isNaN(index)) {
			netNodeContactIndex = Math.max(netNodeContactIndex, index + 1);
		}
	});

	function addNetNodeContact(type, trigger) {
		var template = $('#netnode-' + type + '-contact-template').html()
			.replace(/__INDEX__/g, netNodeContactIndex++);
		if (trigger) {
			$(trigger).closest('.netnode-contact').after(template);
		} else {
			$('#netnode-' + type + '-contacts').append(template);
		}
	}

	$('#netnode-contact-groups').on('click', '.add-netnode-contact', function() {
		addNetNodeContact($(this).attr('data-contact-type'), this);
	});

	$('#netnode-contact-groups').on('click', '.delete-netnode-contact', function() {
		var contact = $(this).closest('.netnode-contact');
		var contacts = contact.closest('.netnode-contacts');
		contact.remove();

		if (!contacts.find('.netnode-contact').length) {
			addNetNodeContact(
				contacts.is('#netnode-phone-contacts') ? 'phone' : 'email'
			);
		}
	});

	if (!$('#netnode-phone-contacts').find('.netnode-contact').length) {
		addNetNodeContact('phone');
	}
	if (!$('#netnode-email-contacts').find('.netnode-contact').length) {
		addNetNodeContact('email');
	}
})();
