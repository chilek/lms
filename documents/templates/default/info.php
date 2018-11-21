<?php

/*
 * LMS version 1.11-git
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

$engine = array(
	'name' => 'default', 	// template directory
	'engine' => 'default', 	// engine.php directory
				// you can use other engine
	'type' => DOC_CONTRACT,			// it's also possible to use array of document types in this field
	'template' => 'template.html', 		// template file (in 'name' dir)
	'title' => trans('Default document'), 	// description for UI
	'content_type' => 'text/html', 		// output file type
	'output' => 'default.html', 		// output file name
	'plugin' => 'plugin',			// form plugin (in 'name' dir)
	'post-action' => 'post-action',		// action file executed after document addition (in transaction)
	'attachments' => array(),		// associative array with directly pointed optional attachment files (key = label; value = full path file name)
)

?>
