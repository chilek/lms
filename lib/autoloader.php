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

// set cache directory
if (!defined('CACHE_DIR')) {
    $CONFIG['directories']['cache_dir'] = (!isset($CONFIG['directories']['cache_dir']) ? $CONFIG['directories']['sys_dir'].'/cache' : $CONFIG['directories']['cache_dir']);
    define('CACHE_DIR', $CONFIG['directories']['cache_dir']);
}

/**
 * Autoloader function.
 * 
 * Loads classes "on the fly". Require or include statements are no longer needed.
 * At the beginning this function checks if class is one of LMS base class and 
 * have well known localisation. If that check failed, next step ius to check 
 * cached paths. At the end, if path has not been found LIB_DIR is being checked.
 * Class files should match one of patterns (example for Example class):
 * Example.php
 * Example.class.php
 * example.php
 * example.class.php
 * EXAMPLE.php
 * EXAMPLE.class.php
 * 
 * 
 * Already known class paths are stored in special cache file. That cache file 
 * is stored in CACHE_DIR path, by default in SYS_DIR/cache, so that path should 
 * be writeable for apache. You can change that path in lms.ini file in 
 * directories section. You should clear cache file each time you move class file 
 * to another location.
 * 
 * This function should be registered with spl_autoload_register function before
 * first usage.
 * 
 * @param string $class Class name
 * @package LMS
 */
function application_autoloader($class) {

    $namespace = explode('\\', $class);
    
    $class = $namespace[count($namespace) - 1];
    
    $base_classes = array(
        'LMSDB_common' => 'LMSDB_common.class.php',
        'LMSDB_driver_mysql' => 'LMSDB_driver_mysql.class.php',
        'LMSDB_driver_mysqli' => 'LMSDB_driver_mysqli.class.php',
        'LMSDB_driver_postgres' => 'LMSDB_driver_postgres.class.php',
        'LMS' => 'LMS.class.php',
        'Auth' => 'Auth.class.php',
        'ExecStack' => 'ExecStack.class.php',
        'Session' => 'Session.class.php',
        'Sysinfo' => 'Sysinfo.class.php',
        'TCPDFpl' => 'tcpdf.php',
        'Smarty' => 'Smarty/Smarty.class.php',
        'SmartyBC' => 'Smarty/SmartyBC.class.php',
        'Cezpdf' => 'ezpdf/class.ezpdf.php',
        'Cpdf' => 'ezpdf/class.pdf.php',
        'HTML2PDF' => 'html2pdf/html2pdf.class.php',
        'TCPDF' => 'tcpdf/tcpdf.php'
    );

    if (array_key_exists($class, $base_classes)) {
        require_once LIB_DIR . DIRECTORY_SEPARATOR . $base_classes[$class];
    } else {
        // set cache file path
        $cache_file = CACHE_DIR . "/classpaths.cache";
        // read cache
        $path_cache = (file_exists($cache_file)) ? unserialize(file_get_contents($cache_file)) : array();
        // create empty cache container if cache is empty
        if (!is_array($path_cache)) {
            $path_cache = array();
        }

        // check if class path exists in cache
        if (array_key_exists($class, $path_cache)) {
            // try to load file
            if (file_exists($path_cache[$class])) {
                require_once $path_cache[$class];
            }
        } else {
            // try to find class file in LIB_DIR, PLUGINS_DIR and VENDOR_DIR
            $suspicious_file_names = array(
                $class . '.php',
                $class . '.class.php',
                strtolower($class) . '.php',
                strtolower($class) . '.class.php',
                strtoupper($class) . '.php',
                strtoupper($class) . '.class.php',
            );
            $search_paths = array(LIB_DIR, PLUGINS_DIR);
            $file_found = false;
            foreach ($search_paths as $search_path) {
                if ($file_found === true) {
                    break;
                }
                $directories = new RecursiveDirectoryIterator($search_path);
                foreach (new RecursiveIteratorIterator($directories) as $file) {
                    if (in_array($file->getFilename(), $suspicious_file_names)) {
                        // get class file path
                        $full_path = $file->getRealPath();
                        // store path in cache
                        $path_cache[$class] = $full_path;
                        // load class file
                        require_once $full_path;
                        $file_found = true;
                        break;
                    }
                }
            }
        }
        // serialize cache
        $serialized_paths = serialize($path_cache);
        // if cache changed save it
        if ($serialized_paths != $path_cache) {
            file_put_contents($cache_file, $serialized_paths);
        }
    }
}

// register autoloader
spl_autoload_register('application_autoloader');
