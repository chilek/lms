<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2017 LMS Developers
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
    public function GetDocuments($customerid = NULL, $limit = NULL);

	public function GetDocumentList(array $params);

	public function GetNumberPlans($properties);

    public function GetNewDocumentNumber($properties);

    public function DocumentExists($properties);

	public function CommitDocuments(array $ids);

	public function UpdateDocumentPostAddress($docid, $customerid);

	public function DeleteDocumentAddresses($docid);

	public function AddDocumentFileAttachments(array $files);

	public function DocumentAttachmentExists($md5sum);

	public function GetDocumentFullContents($id);

	public function SendDocuments($docs, $type, $params);
}
