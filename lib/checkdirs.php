<?php

/*
 * LMS version 1.5-cvs
 *
 *  (C) Copyright 2001-2004 LMS Developers
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
	die('Brakuje mi katalogu <B>'.$_SMARTY_COMPILE_DIR.'</B> - bêdê wdziêczny jak kto¶ go utworzy.');

if(!is_writable($_SMARTY_COMPILE_DIR))
	die('Nie mogê zapisywaæ do katalogu <B>'.$_SMARTY_COMPILE_DIR.'</B> - móg³by¶ wykonaæ komendê:<BR><pre>chown '.posix_geteuid().'.'.posix_getegid().' '.$_SMARTY_COMPILE_DIR."\nchmod 755 ".$_SMARTY_COMPILE_DIR.'</pre><BR>Pomo¿e mi to poprawnie dzia³aæ. Dziêki.');

if(!is_dir($_BACKUP_DIR))
	die('Brakuje mi katalogu <B>'.$_BACKUP_DIR.'</B> - bêdê wdziêczny jak kto6 go utworzy.');
	
if(!is_writable($_BACKUP_DIR))
	die('Nie mogê zapisywaæ do katalogu <B>'.$_BACKUP_DIR.'</B> - móg³by¶ wykonaæ komendê:<BR><pre>chown '.posix_geteuid().'.'.posix_getegid().' '.$_BACKUP_DIR."\nchmod 755 ".$_BACKUP_DIR.'</pre><BR>Pomo¿e mi to poprawnie dzia³aæ. Dziêki.');

?>
