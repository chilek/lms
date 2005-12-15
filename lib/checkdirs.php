<?php

/*
 * LMS version 1.9-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

if(!is_dir($_SMARTY_COMPILE_DIR))
	die('Missing directory <B>'.$_SMARTY_COMPILE_DIR.'</B>. Can anybody make them?');

if(!is_writable($_SMARTY_COMPILE_DIR))
	die('Can\'t write to directory <B>'.$_SMARTY_COMPILE_DIR.'</B>. Run: <BR><PRE>chown '.posix_geteuid().':'.posix_getegid().' '.$_SMARTY_COMPILE_DIR."\nchmod 755 ".$_SMARTY_COMPILE_DIR.'</PRE>This helps me to work. Thanks.');

if(!is_dir($_BACKUP_DIR))
	die('Missing directory <B>'.$_BACKUP_DIR.'</B>. Can anybody make them?');
	
if(!is_writable($_BACKUP_DIR))
	die('Can\'t write to directory <B>'.$_BACKUP_DIR.'</B>. Run: <BR><PRE>chown '.posix_geteuid().':'.posix_getegid().' '.$_BACKUP_DIR."\nchmod 755 ".$_BACKUP_DIR.'</PRE>This helps me to work. Thanks.');

if(!is_dir($_DOC_DIR))
	die('Missing directory <B>'.$_DOC_DIR.'</B>. Can anybody make them?');
	
if(!is_writable($_DOC_DIR))
	die('Can\'t write to directory <B>'.$_DOC_DIR.'</B>. Run: <BR><PRE>chown '.posix_geteuid().':'.posix_getegid().' '.$_DOC_DIR."\nchmod 755 ".$_DOC_DIR.'</PRE>This helps me to work. Thanks.');

?>
