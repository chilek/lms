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

$selinux_active = @is_readable('/sys/fs/selinux');
$selinux_error = false;

if (!is_dir(SMARTY_COMPILE_DIR)) {
    $startup_errors[] = 'mkdir ' . SMARTY_COMPILE_DIR;
}

if (!is_writable(SMARTY_COMPILE_DIR)) {
    $startup_errors[] = 'chown -R ' . posix_geteuid() . ':' . posix_getegid() . ' ' . SMARTY_COMPILE_DIR ;
    $startup_errors[] = 'chmod -R 755 ' . SMARTY_COMPILE_DIR;
    if ($selinux_active) {
        $startup_errors[] = 'semanage fcontext -a -t httpd_sys_rw_content_t "' . rtrim(SMARTY_COMPILE_DIR, '/') . '(/.*)?"';
        $selinux_error = true;
    }
}

if (!is_dir(BACKUP_DIR)) {
    $startup_errors[] = 'mkdir ' . BACKUP_DIR;
}

if (!is_writable(BACKUP_DIR)) {
    $startup_errors[] = 'chown -R ' . posix_geteuid() . ':' . posix_getegid() . ' ' . BACKUP_DIR;
    $startup_errors[] = 'chmod -R 755 ' . BACKUP_DIR;
    if ($selinux_active) {
        $startup_errors[] = 'semanage fcontext -a -t httpd_sys_rw_content_t "' . rtrim(BACKUP_DIR, '/') . '(/.*)?"';
        $selinux_error = true;
    }
}

$rt_dir = STORAGE_DIR . DIRECTORY_SEPARATOR . 'rt';

if (!is_dir($rt_dir)) {
    $startup_errors[] = 'mkdir ' . $rt_dir;
}

if (!is_writable($rt_dir)) {
    $startup_errors[] = 'chown -R ' . posix_geteuid() . ':' . posix_getegid() . ' ' . $rt_dir;
    $startup_errors[] = 'chmod -R 755 ' . $rt_dir;
    if ($selinux_active) {
        $startup_errors[] = 'semanage fcontext -a -t httpd_sys_rw_content_t "' . rtrim($rt_dir, '/') . '(/.*)?"';
        $selinux_error = true;
    }
}

$voip_call_dir = STORAGE_DIR . DIRECTORY_SEPARATOR . 'voipcalls';

if (!is_dir($voip_call_dir)) {
    $startup_errors[] = 'mkdir ' . $voip_call_dir;
}

if (!is_writable($voip_call_dir)) {
    $startup_errors[] = 'chown -R ' . posix_geteuid() . ':' . posix_getegid() . ' ' . $voip_call_dir;
    $startup_errors[] = 'chmod -R 755 ' . $voip_call_dir;
    if ($selinux_active) {
        $startup_errors[] = 'semanage fcontext -a -t httpd_sys_rw_content_t "' . rtrim($voip_call_dir, '/') . '(/.*)?"';
        $selinux_error = true;
    }
}

$customer_call_dir = STORAGE_DIR . DIRECTORY_SEPARATOR . 'customercalls';

if (!is_dir($customer_call_dir)) {
    $startup_errors[] = 'mkdir ' . $customer_call_dir;
}

if (!is_writable($customer_call_dir)) {
    $startup_errors[] = 'chown -R ' . posix_geteuid() . ':' . posix_getegid() . ' ' . $customer_call_dir;
    $startup_errors[] = 'chmod -R 755 ' . $customer_call_dir;
    if ($selinux_active) {
        $startup_errors[] = 'semanage fcontext -a -t httpd_sys_rw_content_t "' . rtrim($customer_call_dir, '/') . '(/.*)?"';
        $selinux_error = true;
    }
}

$promotion_dir = STORAGE_DIR . DIRECTORY_SEPARATOR . 'promotions';

if (!is_dir($promotion_dir)) {
    $startup_errors[] = 'mkdir ' . $promotion_dir;
}

if (!is_writable($promotion_dir)) {
    $startup_errors[] = 'chown -R ' . posix_geteuid() . ':' . posix_getegid() . ' ' . $promotion_dir;
    $startup_errors[] = 'chmod -R 755 ' . $promotion_dir;
    if ($selinux_active) {
        $startup_errors[] = 'semanage fcontext -a -t httpd_sys_rw_content_t "' . rtrim($promotion_dir, '/') . '(/.*)?"';
        $selinux_error = true;
    }
}

$promotionschema_dir = STORAGE_DIR . DIRECTORY_SEPARATOR . 'promotionschemas';

if (!is_dir($promotionschema_dir)) {
    $startup_errors[] = 'mkdir ' . $promotionschema_dir;
}

if (!is_writable($promotionschema_dir)) {
    $startup_errors[] = 'chown -R ' . posix_geteuid() . ':' . posix_getegid() . ' ' . $promotionschema_dir;
    $startup_errors[] = 'chmod -R 755 ' . $promotionschema_dir;
    if ($selinux_active) {
        $startup_errors[] = 'semanage fcontext -a -t httpd_sys_rw_content_t "' . rtrim($promotionschema_dir, '/') . '(/.*)?"';
        $selinux_error = true;
    }
}

$message_template_dir = STORAGE_DIR . DIRECTORY_SEPARATOR . 'messagetemplates';

if (!is_dir($message_template_dir)) {
    $startup_errors[] = 'mkdir ' . $message_template_dir;
}

if (!is_writable($message_template_dir)) {
    $startup_errors[] = 'chown -R ' . posix_geteuid() . ':' . posix_getegid() . ' ' . $message_template_dir;
    $startup_errors[] = 'chmod -R 755 ' . $message_template_dir;
    if ($selinux_active) {
        $startup_errors[] = 'semanage fcontext -a -t httpd_sys_rw_content_t "' . rtrim($message_template_dir, '/') . '(/.*)?"';
        $selinux_error = true;
    }
}

if (!is_dir(DOC_DIR)) {
    $startup_errors[] = 'mkdir ' . DOC_DIR;
}

if (!is_writable(DOC_DIR)) {
    $startup_errors[] = 'chown -R ' . posix_geteuid() . ':' . posix_getegid() . ' ' . DOC_DIR;
    $startup_errors[] = 'chmod -R 755 ' . DOC_DIR;
    if ($selinux_active) {
        $startup_errors[] = 'semanage fcontext -a -t httpd_sys_rw_content_t "' . rtrim(DOC_DIR, '/') . '(/.*)?"';
        $selinux_error = true;
    }
}

if (!is_dir(CACHE_DIR)) {
    $startup_errors[] = 'mkdir ' . CACHE_DIR;
}

if (!is_writable(CACHE_DIR)) {
    $startup_errors[] = 'chown -R ' . posix_geteuid() . ':' . posix_getegid() . ' ' . CACHE_DIR;
    $startup_errors[] = 'chmod -R 755 ' . CACHE_DIR;
    if ($selinux_active) {
        $startup_errors[] = 'semanage fcontext -a -t httpd_sys_rw_content_t "' . rtrim(CACHE_DIR, '/') . '(/.*)?"';
        $selinux_error = true;
    }
}

$__xajax_deferred_dir = SYS_DIR . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'xajax_js' . DIRECTORY_SEPARATOR . 'deferred';

if (!is_dir($__xajax_deferred_dir)) {
    $startup_errors[] = 'mkdir ' . $__xajax_deferred_dir;
}

if (!is_writable($__xajax_deferred_dir)) {
    $startup_errors[] = 'chown -R ' . posix_geteuid() . ':' . posix_getegid() . ' ' . $__xajax_deferred_dir;
    $startup_errors[] = 'chmod -R 755 ' . $__xajax_deferred_dir;
    if ($selinux_active) {
        $startup_errors[] = 'semanage fcontext -a -t httpd_sys_rw_content_t "' . rtrim($__xajax_deferred_dir, '/') . '(/.*)?"';
        $selinux_error = true;
    }
}

if ($selinux_error) {
    $startup_errors[] = 'restorecon -R ' . SYS_DIR;
}

if (count($startup_errors) > 0) {
    print('Can not start because detected some problems. Please run:<PRE>');
    foreach ($startup_errors as &$err) {
            print ($err . PHP_EOL);
    }
    print('</PRE>This helps me to work. Thanks.');
    die();
}
