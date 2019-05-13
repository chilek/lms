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

function toggle_all_attachments(docid) {
	var elem = $('#allattachments-' + docid);
	var toggle = $('#allattachments-toggle-' + docid);
	elem.toggle();
	if (elem.is(':visible'))
		toggle.html('<img src="img/desc_order.gif">');
	else
		toggle.html('<img src="img/asc_order.gif">');
}

$(function() {
	$('#send-documents').click(function() {
		if (!$(this).closest('div.lms-ui-multi-check').find('input.lms-ui-multi-check:checked').length) {
			return;
		}
		confirmDialog($t('Are you sure, you want to send documents to customer?'), this).done(function() {
			document.customerdocuments.action="?m=documentsend";
			document.customerdocuments.target="_blank";
			document.customerdocuments.submit();
		});
	});

	$('#delete-docs').click(function() {
		if (!$(this).closest('div.lms-ui-multi-check').find('input.lms-ui-multi-check:checked').length) {
			return;
		}
		confirmDialog($t("Are you sure, you want to delete selected documents?"), this).done(function() {
			document.customerdocuments.action = "?m=documentdel";
			document.customerdocuments.target = "";
			document.customerdocuments.submit();
		});
	});

	$('#print-docs').click(function() {
		if (!$(this).closest('div.lms-ui-multi-check').find('input.lms-ui-multi-check:checked').length) {
			return;
		}
		document.customerdocuments.action="?m=documentview";
		document.customerdocuments.target="_blank";
		document.customerdocuments.submit();
	});

	$('#archive-docs').click(function() {
		if (!$(this).closest('div.lms-ui-multi-check').find('input.lms-ui-multi-check:checked').length) {
			return;
		}
		document.customerdocuments.action="?m=documentedit&action=archive";
		document.customerdocuments.target="";
		document.customerdocuments.submit();
	});

	$('#confirm-docs').click(function() {
		if (!$(this).closest('div.lms-ui-multi-check').find('input.lms-ui-multi-check:checked').length) {
			return;
		}
		document.customerdocuments.action="?m=documentedit&action=confirm";
		document.customerdocuments.target="";
		document.customerdocuments.submit();
	});

	$('.archive-doc').click(function() {
		confirmDialog($t('Are you sure, you want to archive that document?'), this).done(function() {
			location.href = $(this).attr('href');
		});
		return false;
	});

	$('.delete-doc').click(function() {
		confirmDialog($t('Are you sure, you want to remove that document?'), this).done(function() {
			location.href = $(this).attr('href');
		});
		return false;
	});

	$('.send-doc').click(function () {
		confirmDialog($t("Are you sure, you want to send document to customer?"), this).done(function() {
			window.open($(this).attr('href'));
		});
		return false;
	});
});
