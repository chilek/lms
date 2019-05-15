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

$startup_errors = array();

if (!is_dir(SMARTY_COMPILE_DIR)) {
    $startup_errors[] = 'mkdir '.SMARTY_COMPILE_DIR;
}

if (!is_writable(SMARTY_COMPILE_DIR)) {
    $startup_errors[] = 'chown -R '.posix_geteuid().':'.posix_getegid().' '.SMARTY_COMPILE_DIR."\nchmod -R 755 ".SMARTY_COMPILE_DIR;
}

if (!is_dir(BACKUP_DIR)) {
    $startup_errors[] = 'mkdir '.BACKUP_DIR;
}
    
if (!is_writable(BACKUP_DIR)) {
    $startup_errors[] = 'chown -R '.posix_geteuid().':'.posix_getegid().' '.BACKUP_DIR."\nchmod -R 755 ".BACKUP_DIR;
}

if (!is_dir(DOC_DIR)) {
    $startup_errors[] = 'mkdir '.DOC_DIR;
}
    
if (!is_writable(DOC_DIR)) {
    $startup_errors[] = 'chown -R '.posix_geteuid().':'.posix_getegid().' '.DOC_DIR."\nchmod -R 755 ".DOC_DIR;
}

$__xajax_deferred_dir = SYS_DIR . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'xajax_js' . DIRECTORY_SEPARATOR . 'deferred';

if (!is_dir($__xajax_deferred_dir)) {
    $startup_errors[] = 'mkdir ' . $__xajax_deferred_dir;
}

if (!is_writable($__xajax_deferred_dir)) {
    $startup_errors[] = 'chown -R ' . posix_geteuid() . ':' . posix_getegid() . ' ' . $__xajax_deferred_dir
        . "\nchmod -R 755 " . $__xajax_deferred_dir;
}

if (count($startup_errors) > 0) {
    print('Can not start because detected some problems. Please run:<PRE>');
    foreach ($startup_errors as &$err) {
            print ($err."\n");
    }
    print('</PRE>This helps me to work. Thanks.');
    die();
}
