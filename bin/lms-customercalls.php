#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2024 LMS Developers
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

$script_parameters = array(
    'section:' => 's:',
);

$script_help = <<<EOF
-s, --section=<section-name>    section name from lms configuration where settings
                                are stored
EOF;

require_once('script-options.php');

$SYSLOG = SYSLOG::getInstance();

// Initialize Session, Auth and LMS classes
$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

$config_section = isset($options['section']) && preg_match('/^[a-z0-9-]+$/i', $options['section'])
    ? $options['section']
    : 'customercalls';

$src_dir = ConfigHelper::getConfig($config_section . '.source_directory', '.');
$customer_call_dir = STORAGE_DIR . DIRECTORY_SEPARATOR . 'customercalls';
$storage_dir_permission = intval(ConfigHelper::getConfig('storage.dir_permission', '0700'), 8);
$storage_dir_owneruid = ConfigHelper::getConfig('storage.dir_owneruid', 'root');
$storage_dir_ownergid = ConfigHelper::getConfig('storage.dir_ownergid', 'root');
$convert_command = ConfigHelper::getConfig($config_section . '.call_convert_command', 'sox %i %o');
$duration_command = ConfigHelper::getConfig($config_section . '.call_duration_command', 'soxi -D %o');
$file_name_pattern = ConfigHelper::getConfig(
    $config_section . '.file_name_pattern',
    '^(?<year>[0-9]{4})-(?<month>[0-9]{2})-(?<day>[0-9]{2})_+(?<hour>[0-9]{2})-(?<minute>[0-9]{2})-(?<second>[0-9]{2})'
        . '_+(?<src>[0-9]+)_+(?<dst>[0-9]+)_+(?:(?<durationh>[0-9]+)h)?(?:(?<durationm>[0-9]{1,2})m)?(?:(?<durations>[0-9]{1,2})s)?\.wav$'
);
$file_extension = ConfigHelper::getConfig($config_section . '.file_extension', 'ogg');
$local_number_pattern = ConfigHelper::getConfig(
    $config_section . '.local_number_pattern',
    '^(?<prefix>48)?(?<number>[0-9]{9})$'
);
$operator_number_pattern = ConfigHelper::getConfig($config_section . '.operator_number_pattern');
$utc_datetime = ConfigHelper::checkConfig($config_section . '.utc_datetime');

if (!is_dir($customer_call_dir)) {
    die('Fatal error: customer call directory does not exist!' . PHP_EOL);
}

if (!is_dir($src_dir)) {
    die('Fatal error: source directory does not exist!' . PHP_EOL);
}

$dirs = getdir($src_dir, '^[^\.].*$');
if (empty($dirs)) {
    $dirs[] = '';
} else {
    $_dirs = array();
    foreach ($dirs as $dir) {
        if (is_dir($src_dir . DIRECTORY_SEPARATOR . $dir)) {
            $_dirs[] = $dir;
        }
    }
    $dirs = $_dirs;
    unset($_dirs);
    if (empty($dirs)) {
        $dirs = array('');
    }
}

function normalizePhoneNumber($number)
{
    return preg_replace(
        array(
            '/[^0-9]/',
            '/^0*/',
        ),
        array(
            '',
            '',
        ),
        $number
    );
}

$contacts = $DB->GetAll(
    'SELECT contact AS phone, customerid
    FROM customercontacts
    WHERE (type & ?) > 0',
    array(CONTACT_MOBILE | CONTACT_LANDLINE)
);
if (empty($contacts)) {
    die('Fatal error: customer contact database is empty!' . PHP_EOL);
}

$customers = array();
foreach ($contacts as $contact) {
    $phone = normalizePhoneNumber($contact['phone']);

    if (preg_match('/' . $local_number_pattern . '/', $phone, $m) && isset($m['prefix'])) {
        $phone = $m['number'];
    }

    if (!isset($customers[$phone])) {
        $customers[$phone] = array();
    }
    $customers[$phone][] = $contact['customerid'];
}
unset($contacts);

$users = array();
$user_phones = $DB->GetAll(
    'SELECT u.id, u.phone
    FROM users u
    WHERE u.phone <> ?',
    array('')
);
if (!empty($user_phones)) {
    foreach ($user_phones as $user_phone) {
        $phone = normalizePhoneNumber($user_phone['phone']);

        $users[$phone] = $user_phone['id'];

        if (preg_match('/' . $local_number_pattern . '/', $phone, $m) && isset($m['prefix'])) {
            $users[$m['number']] = $user_phone['id'];
        }
    }
    unset($user_phones);
}

foreach ($dirs as $dir) {
    $src_file_dir = $src_dir . ($dir == '' ? '' : DIRECTORY_SEPARATOR . $dir);

    $files = getdir($src_file_dir, $file_name_pattern);
    if (empty($files)) {
        continue;
    }

    if (!$quiet && !empty($dir)) {
        echo 'Processing files in \'' . $dir . '\' directory ...' . PHP_EOL;
    }

    $file_count = count($files);
    $progress_part = round(0.01 * $file_count);
    if (empty($progress_part)) {
        $progress_part = 1;
    }

    foreach ($files as $file_idx => $src_file_name) {
        if (!$quiet && !($file_idx % $progress_part)) {
            echo 'Progress: ' . round((($file_idx + 1) * 100) / $file_count) . '%' . PHP_EOL;
        }

        if (!preg_match('/' . $file_name_pattern . '/', $src_file_name, $m)) {
            echo 'Warning: file name \'' . $src_file_name . '\' does not match to pattern!' . PHP_EOL;
            continue;
        }

        $src_file = $src_file_dir . DIRECTORY_SEPARATOR . $src_file_name;

        if (isset($m['timestamp'])) {
            $dt = intval($m['timestamp']);
        } elseif (isset($m['datetime'])) {
            $dt = strtotime($m['datetime']);
        } else {
            $dt = mktime($m['hour'], $m['minute'], $m['second'], $m['month'], $m['day'], $m['year']);
        }

        if ($utc_datetime) {
            $dt = strtotime(date('Y/m/d H:i:s', $dt) . ' UTC');
        }

        $duration = -1;
        if (isset($m['durationh'])) {
            $duration = (empty($m['durationh']) ? 0 : intval($m['durationh'])) * 3600
                + (empty($m['durationm']) ? 0 : intval($m['durationm'])) * 60
                + (empty($m['durations']) ? 0 : intval($m['durations']));
        } elseif (isset($m['duration'])) {
            $duration = intval($m['duration']);
        }

        $src = normalizePhoneNumber($m['src']);
        $dst = normalizePhoneNumber($m['dst']);

        $userid = null;
        $outgoing = null;

        if (preg_match('/' . $local_number_pattern . '/', $src, $mn) && isset($mn['prefix'])) {
            $src_prefix = $mn['prefix'];
            $src_number = $mn['number'];
        } else {
            $src_prefix = '';
            $src_number = $src;
        }

        if (preg_match('/' . $local_number_pattern . '/', $dst, $mn) && isset($mn['prefix'])) {
            $dst_prefix = $mn['prefix'];
            $dst_number = $mn['number'];
        } else {
            $dst_prefix = '';
            $dst_number = $dst;
        }

        if (!empty($operator_number_pattern)) {
            if (preg_match('/' . $operator_number_pattern . '/', $src)) {
                $outgoing = true;
            } elseif (preg_match('/' . $operator_number_pattern . '/', $dst)) {
                $outgoing = false;
            }
        }

        if (!empty($src_prefix) && isset($users[$src_prefix . $src_number])) {
            $userid = $users[$src_prefix . $src_number];
            if (!isset($outgoing)) {
                $outgoing = true;
            }
        } elseif (isset($users[$src_number])) {
            $userid = $users[$src_number];
            if (!isset($outgoing)) {
                $outgoing = true;
            }
        } elseif (!empty($dst_prefix) && isset($users[$dst_prefix . $dst_number])) {
            $userid = $users[$dst_prefix . $dst_number];
            if (!isset($outgoing)) {
                $outgoing = false;
            }
        } elseif (isset($users[$dst_number])) {
            $userid = $users[$dst_number];
            if (!isset($outgoing)) {
                $outgoing = false;
            }
        }

        if (!isset($outgoing)) {
            if (preg_match('/' . $local_number_pattern . '/', $src, $m) && isset($m['prefix'])) {
                $number = $m['number'];
            } else {
                $number = $src;
            }
            if (isset($customers[$number])) {
                $outgoing = false;
                $phone = $src;
            }
            if (preg_match('/' . $local_number_pattern . '/', $dst, $m) && isset($m['prefix'])) {
                $number = $m['number'];
            } else {
                $number = $dst;
            }
            if (!isset($outgoing) || isset($customers[$number])) {
                $outgoing = true;
                $phone = $dst;
            }
        } else {
            $phone = $outgoing ? $dst : $src;
        }

        $dst_file_name = preg_replace('/\.[^\.]+$/', '.' . $file_extension, $src_file_name);

        if ($LMS->isCustomerCallExists(array(
            'filename' => $dst_file_name,
        ))) {
            @unlink($src_file);
            continue;
        }

        $dst_dir = $customer_call_dir . DIRECTORY_SEPARATOR . date('Y-m-d', $dt);

        if (!is_dir($dst_dir)) {
            mkdir($dst_dir, $storage_dir_permission, true);
            chown($dst_dir, $storage_dir_owneruid);
            chgrp($dst_dir, $storage_dir_ownergid);
        }

        $dst_file = $dst_dir . DIRECTORY_SEPARATOR . $dst_file_name;

        if (preg_match('/\.(?<ext>[^\.]+)$/', $src_file_name, $m) && $m['ext'] == $file_extension
            || empty($convert_command)) {
            if (!@rename($src_file, $dst_file)) {
                die('Fatal error: error during file ' . $src_file . ' rename!' . PHP_EOL);
            }
        } else {
            $cmd = str_replace(
                array('%i', '%o'),
                array(
                    str_replace(
                        array(
                            '(',
                            ')',
                        ),
                        array(
                            '\\(',
                            '\\)',
                        ),
                        $src_file
                    ),
                    str_replace(
                        array(
                            '(',
                            ')',
                        ),
                        array(
                            '\\(',
                            '\\)',
                        ),
                        $dst_file
                    ),
                ),
                $convert_command
            );
            $ret = 0;
            $output = array();
            $res = exec($cmd, $output, $ret);
            if (!empty($ret) || $res === false) {
                die('Fatal error: error during file ' . $src_file . ' conversion!' . PHP_EOL);
            }

            touch($dst_file, filemtime($src_file));

            if (!@unlink($src_file)) {
                die('Fatal error: error during file ' . $src_file . ' deletion!' . PHP_EOL);
            }
        }

        if ($duration == -1) {
            if (empty($duration_command)) {
                echo 'Warning: cannot find duration field for file \'' . $src_file_name . '\'!' . PHP_EOL;
            } else {
                $cmd = str_replace(
                    array('%i', '%o'),
                    array(
                        str_replace(
                            array(
                                '(',
                                ')',
                            ),
                            array(
                                '\\(',
                                '\\)',
                            ),
                            $src_file
                        ),
                        str_replace(
                            array(
                                '(',
                                ')',
                            ),
                            array(
                                '\\(',
                                '\\)',
                            ),
                            $dst_file
                        ),
                    ),
                    $duration_command
                );
                $ret = 0;
                $output = array();
                $res = exec($cmd, $output, $ret);
                if (!empty($ret) || $res === false) {
                    echo 'Warning: error during duration determination for file ' . $src_file . '!' . PHP_EOL;
                } else {
                    $duration = str_replace(',', '.', round(floatval($res)));
                }
            }
        }

        chmod($dst_file, $storage_dir_permission & 0666);
        chown($dst_file, $storage_dir_owneruid);
        chgrp($dst_file, $storage_dir_ownergid);

        $LMS->addCustomerCall(
            array(
                'dt' => $dt,
                'userid' => $userid,
                'filename' => $dst_file_name,
                'outgoing' => $outgoing,
                'phone' => $phone,
                'duration' => $duration,
            )
        );
    }
}

$calls = $LMS->getCustomerCalls(array(
    'order' => 'id,asc'
));
if (empty($calls)) {
    die('Warning: the are no customer calls in database!' . PHP_EOL);
}

foreach ($calls as $call) {
    $phone = $call['phone'];
    if (preg_match('/' . $local_number_pattern . '/', $phone, $m) && isset($m['prefix'])) {
        $phone = $m['number'];
    }

    if (isset($customers[$phone])) {
        foreach ($customers[$phone] as $customerid) {
            $LMS->addCustomerCallAssignment(
                $customerid,
                $call['id']
            );
        }
    }
}
