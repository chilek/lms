<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2021 LMS Developers
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

/**
 * LMSDocumentManagerInterface
 *
 */
interface LMSDocumentManagerInterface
{
    public function GetDocuments($customerid = null, $limit = null, $all = false);

    public function GetDocumentList(array $params);

    public function GetNumberPlans($properties);

    public function getSystemDefaultNumberPlan($properties);

    public function GetNewDocumentNumber($properties);

    public function DocumentExists($properties);

    public function documentCommitParseNotificationMail($string, $data);

    public function documentCommitParseNotificationRecipient($string, $data);

    public function CommitDocuments(array $ids, $userpanel = false, $check_close_flag = true);

    public function newDocumentParseNotification($string, $data);

    public function NewDocumentCustomerNotifications(array $document);

    public function ArchiveDocuments(array $ids);

    public function UpdateDocumentPostAddress($docid, $customerid);

    public function DeleteDocumentAddresses($docid);

    public function isArchiveDocument($id);

    public function AddArchiveDocument($docid, $file);

    public function GetArchiveDocument($docid);

    public function AddDocumentFileAttachments(array $files);

    public function AddDocumentAttachments($documentid, array $files);

    public function AddDocumentScans($documentid, array $files);

    public function DocumentAttachmentExists($md5sum);

    public function GetDocumentFullContents($id);

    public function SendDocuments($docs, $type, $params);

    public function deleteDocumentAttachments($docid);

    public function DeleteDocument($docid);

    public function CopyDocumentPermissions($src_userid, $dst_userid);

    public function getDocumentsByFullNumber($full_number, $all_types = false);

    public function getDocumentsByChecksum($checksum, $all_types = false);

    public function isDocumentAccessible($docid);

    public function getDocumentReferences($docid, $cashid = null);

    public function getDefaultNumberPlanID($doctype, $divisionid = null);

    public function checkNumberPlanAccess($id);

    public function getNumberPlan($id);

    public function getNumberPlanList(array $params);

    public function validateNumberPlan(array $numberplan);

    public function addNumberPlan(array $numberplan);

    public function updateNumberPlan(array $numberplan);

    public function deleteNumberPlan($id);

    public function getDocumentType($docid);

    public function getDocumentFullNumber($docid);
}
