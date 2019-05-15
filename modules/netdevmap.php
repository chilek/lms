<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

function drawtext($x, $y, $text, $r, $g, $b)
{
    global $m, $font;

    if (!$text) {
        return;
    }

    // remove special characters because we haven't got proper font
    // or something else. I don't know why, but we have a problem with
    // that characters on flash map.
    $text = clear_utf($text);
    
    $t = new SWFTextField(SWFTEXTFIELD_NOEDIT | SWFTEXTFIELD_NOSELECT);
    $t->setFont($font);
    $t->setHeight(8);
    $t->setColor($r, $g, $b);
    $t->addString($text);
    $i = $m->add($t);
    $i->moveTo($x, $y);
}

function pngdrawtext($image, $font, $x, $y, $text, $color, $bgcolor)
{
    if (ConfigHelper::getConfig('phpui.gd_translate_to')) {
        $text = iconv('UTF-8', ConfigHelper::getConfig('phpui.gd_translate_to'), $text);
    }
    imagestring($image, $font, $x + 1, $y + 1, $text, $bgcolor);
    imagestring($image, $font, $x + 1, $y - 1, $text, $bgcolor);
    imagestring($image, $font, $x - 1, $y + 1, $text, $bgcolor);
    imagestring($image, $font, $x - 1, $y - 1, $text, $bgcolor);
    imagestring($image, $font, $x, $y, $text, $color);
}

function getnodearray($size)
{
    $x = 1;
    $result = array();
    $arr = array();
    
    $exceptions = array('00', '01', '11', '10', '0-1', '-10');
    
    while ($size > 0) {
        for ($i=-$x; $i<=$x; $i++) {
            for ($j=-$x; $j<=$x; $j++) {
                if (!isset($arr["x$i$j"]) && !in_array("$i$j", $exceptions)) {
                    $result[] = $arr["x$i$j"] = array('x' => $i, 'y' => $j);
                    $size--;
                    if ($size <= 0) {
                        break 2;
                    }
                }
            }
        }
        $x++;
    }
    
    return $result;
}

function overlaps(&$seen, $devid, $x1, $y1, $x2, $y2)
{
    global $devicelinks;

    // x1, y1 - wspolrz. rodzica badanego urzadzenia
    // x2, y2 - wspolrz. badanego urzadzenia

    $in = array();
    
    $minx = min($x1, $x2);
    $maxx = max($x1, $x2);
    $miny = min($y1, $y2);
    $maxy = max($y1, $y2);

    foreach ($devicelinks as $d1 => $link) {
        if (isset($seen[$d1]) || $d1 == $devid) {
            foreach ($link as $d2) {
                if ((isset($seen[$d2]) || $d2 == $devid) && !isset($in[$d2.'-'.$d1])) {
                    $a1 = $d1 != $devid ? $seen[$d1] : array('x' => $x2, 'y' => $y2);
                    $a2 = $d2 != $devid ? $seen[$d2] : array('x' => $x2, 'y' => $y2);
                
                    // zapamietujemy odcinek, aby nie przetwarzac tego samego odcinka
                    // dwukrotnie ($devicelinks zawiera odcinki zdublowane)
                    $in[$d1.'-'.$d2] = true;

                    // sprawdzamy czy na badanym odcinku lezy inne urzadzenie
                    // Det(a,b,c) = 0 - wyznacznik maciezy metoda Sarrusa
                
                    // urzadzenie $a1 lezy na odcinku |(x1,y1),(x2,y2)|
                    if (($x1*$y2 + $y1*$a1['x'] + $x2*$a1['y'] - $y2*$a1['x'] - $x1*$a1['y'] - $y1*$x2)==0) {
                        // rzut punktu zawiera sie w rzucie odcinka
                        if ((($a1['x'] != $x1 || $a1['y'] != $y1)
                        && $a1['x'] >= $minx && $a1['x'] <= $maxx)
                        &&
                        (($a1['x'] != $x2 || $a1['y'] != $y2)
                        && $a1['y'] >= $miny && $a1['y'] <= $maxy)) {
                            return true;
                        }
                    }

                    // urzadzenie $a2 lezy na odcinku |(x1,y1),(x2,y2)|
                    if (($x1*$y2 + $y1*$a2['x'] + $x2*$a2['y'] - $y2*$a2['x'] - $x1*$a2['y'] - $y1*$x2)==0) {
                        if ((($a2['x'] != $x1 || $a2['y'] != $y1)
                        && $a2['x'] >= $minx && $a2['x'] <= $maxx)
                        &&
                        (($a2['x'] != $x2 || $a2['y'] != $y2)
                        && $a2['y'] >= $miny && $a2['y'] <= $maxy)) {
                            return true;
                        }
                    }
    
                    $pminx = min($a1['x'], $a2['x']);
                    $pmaxx = max($a1['x'], $a2['x']);
                    $pminy = min($a1['y'], $a2['y']);
                    $pmaxy = max($a1['y'], $a2['y']);

                    // urzadzenie (x2,y2) lezy na odcinku |d1,d2|
                    if (($a1['x']*$a2['y'] + $a1['y']*$x2 + $a2['x']*$y2 - $a2['y']*$x2 - $a1['x']*$y2 - $a1['y']*$a2['x'])==0) {
                        if ((($x2 != $a1['x'] || $y2 != $a1['y'])
                        && $x2 >= $pminx && $x2 <= $pmaxx)
                        &&
                        (($x2 != $a2['x'] || $y2 != $a2['y'])
                            && $y2 >= $pminy && $y2 <= $pmaxy)) {
                            return true;
                        }
                    }

                    // rodzic (x1,y1) urzadzenia lezy na odcinku |d1,d2|
                    if (($a1['x']*$a2['y'] + $a1['y']*$x1 + $a2['x']*$y1 - $a2['y']*$x1 - $a1['x']*$y1 - $a1['y']*$a2['x'])==0) {
                        if ((($x1 != $a1['x'] || $y1 != $a1['y'])
                        && $x1 >= $pminx && $x1 <= $pmaxx)
                        &&
                        (($x1 != $a2['x'] || $y1 != $a2['y'])
                            && $y1 >= $pminy && $y1 <= $pmaxy)) {
                            return true;
                        }
                    }
                }
            }
        }
    }
    
    return false;
}

function makemap(&$map, &$seen, $device = 0, $x = 500, $y = 500, $parent = 0)
{
    global $DB, $nodelist, $devicelinks, $mini;

    if ($mini) {
        $in = array(0,3,-3,6,-6,9,-9,12,-12,15,-15,18,-18,21,-21);
    } else {
        $in = array(0,5,-5,10,-10,20,-20,25,-25,30,-30,35,-35,40,-40);
    }
    
    // net size: count($in)^2 - 1
    foreach ($in as $ii => $i) {
        foreach ($in as $ij => $j) {
            if (($i != 0 || $j != 0) && ($ij <= $ii)) {
                $fields["x$j$i"] = array('x' => $j, 'y' => $i);
                $fields["x$i$j"] = array('x' => $i, 'y' => $j);
            }
        }
    }

    if ($device == 0) {
        if ($device = $DB->GetOne('SELECT id FROM netdevices ORDER BY name LIMIT 1')) {
            makemap($map, $seen, $device, $x, $y);
        }
    } else {
        // remember that current device was processed
        $seen[$device] = array('x' => $x, 'y' => $y);
        
        // place device in space ...
        $map[$x][$y] = $device;
        
        // ... and connected nodes (if they wasn't processed before)
        if (isset($nodelist[$device])) {
            $nodefields = getnodearray(count($nodelist[$device]));
            $i = 0;
            foreach ($nodefields as $field) {
                if (!isset($map[$x + $field['x']][$y + $field['y']])) {
                    $ntx = $x + $field['x'];
                    $nty = $y + $field['y'];
                    $map[$ntx][$nty] = 'n'.$nodelist[$device][$i]['id'].'.'.$device.'.'.$nodelist[$device][$i]['linktype'];
                    $i++;
                }
            }

            unset($nodefields);
            unset($nodelist[$device]);
        }

        // now do recursion for connected devices
        if (isset($devicelinks[$device])) {
            foreach ($devicelinks[$device] as $deviceid) {
                if (!isset($seen[$deviceid])) {
                    if (isset($nodelist[$deviceid])) {
                        $nodefields = getnodearray(count($nodelist[$deviceid]));
                    }
        
                    foreach ($fields as $devfield) {
                        $tx = $x + $devfield['x'];
                        $ty = $y + $devfield['y'];

                        if (!isset($map[$tx][$ty])) {
                            // we don't want to overlap connection lines
                            if (overlaps($seen, $deviceid, $x, $y, $tx, $ty)) {
                                continue;
                            }

                            // try to place all connected nodes on map
                            // if there's no place, go to next field
                            if (isset($nodelist[$deviceid])) {
                                $map2 = $map;
                                $cnt = 0;
                                foreach ($nodefields as $field) {
                                    if (!isset($map2[$tx + $field['x']][$ty + $field['y']])) {
                                        $ntx = $tx + $field['x'];
                                        $nty = $ty + $field['y'];
                                        $map2[$ntx][$nty] = 'n'.$nodelist[$deviceid][$cnt]['id'].'.'.$deviceid.'.'.$nodelist[$deviceid][$cnt]['linktype'];
                                        $cnt++;
                                    }
                                }

                                // not found place for all nodes, let's try next field
                                if ($cnt < count($nodelist[$deviceid])) {
                                    continue;
                                }
                            
                                $map = $map2;
                                unset($nodelist[$deviceid]);
                                unset($nodefields);
                                unset($map2);
                            }

                            makemap($map, $seen, $deviceid, $tx, $ty, $device);
                            break;
                        }
                    }
                }
            }
        }
    }
}

$layout['pagetitle'] = trans('Network Map');

$graph = isset($_GET['graph']) ? $_GET['graph'] : '';
$start = isset($_GET['start']) ? $_GET['start'] : 0;
$mini = isset($_GET['mini']) ? true : false;

$minx = 0;
$maxx = 0;
$miny = 0;
$maxy = 0;
$nodelist = array();
$devicelinks = array();
$nodemap = array();

if (!$mini && ($nodes = $DB->GetAll('SELECT id, linktype, netdev
			FROM vnodes
			WHERE ownerid IS NOT NULL AND netdev IS NOT NULL
			ORDER BY name ASC'))) {
    foreach ($nodes as $idx => $node) {
        $nodelist[$node['netdev']][] = $node;
        unset($nodes[$idx]);
    }
}

if ($links = $DB->GetAll('SELECT src, dst FROM netlinks')) {
    foreach ($links as $idx => $link) {
        $devicelinks[$link['src']][$link['dst']] = $link['dst'];
        $devicelinks[$link['dst']][$link['src']] = $link['src'];
        unset($links[$idx]);
    }
}

$type = strtolower(ConfigHelper::getConfig('phpui.map_type', ''));

if ($type == 'openlayers') {
    include(MODULES_DIR.'/map.inc.php');

    if (isset($_GET['netdevid'])) {
        $netdevid = intval($_GET['netdevid']);
        $SMARTY->assign('lon', $devices[$netdevid]['lon']);
        $SMARTY->assign('lat', $devices[$netdevid]['lat']);
    } else if (isset($_GET['nodeid'])) {
        $nodeid = intval($_GET['nodeid']);
        $SMARTY->assign('lon', $nodes[$nodeid]['lon']);
        $SMARTY->assign('lat', $nodes[$nodeid]['lat']);
    } else {
        $SMARTY->assign('lon', $_GET['lon']);
        $SMARTY->assign('lat', $_GET['lat']);
    }

    $SMARTY->assign('type', $type);
    $SMARTY->display('netdev/netdevmap.html');
} elseif ($graph == '') {
    makemap($map, $seen, $start);
    if ($map) {
        foreach ($map as $idx => $x) {
            if (!$minx) {
                $minx = $idx;
            } elseif ($idx < $minx) {
                $minx = $idx;
            }
        
            if ($idx > $maxx) {
                $maxx = $idx;
            }
            foreach ($x as $idy => $y) {
                if (!$miny) {
                    $miny = $idy;
                } elseif ($idy < $miny) {
                    $miny = $idy;
                }

                if ($idy > $maxy) {
                    $maxy = $idy;
                }
            }
        }

        $widthx = $maxx - $minx;
        $widthy = $maxy - $miny;
        $cellw = 70;
        $cellh = 30;
        $celltmargin = 20;
        $celllmargin = 10;
        $imgwx = $cellw * ($widthx + 2);
        $imgwy = $cellh * ($widthy + 2);

        foreach ($map as $idx => $x) {
            foreach ($x as $idy => $device) {
                $celx = $idx - $minx;
                $cely = $idy - $miny;
                if (preg_match('/^n/', $device)) {
                    $device = str_replace('n', '', $device);
                    list($nodeid,$device,$linktype) = explode('.', $device);
                    $nodemap[$nodeid]['x'] = (($celx * ($cellw)) + $celllmargin) +4;
                    $nodemap[$nodeid]['y'] = (($cely * ($cellh)) + $celltmargin) +4;
                    $nodemap[$nodeid]['id'] = $nodeid;
                    $nodemap[$nodeid]['linktype'] = $linktype;
                } else {
                    $devicemap[$device]['x'] = (($celx * ($cellw)) + $celllmargin) +4;
                    $devicemap[$device]['y'] = (($cely * ($cellh)) + $celltmargin) +4;
                    $devicemap[$device]['id'] = $device;
                }
            }
            unset($map[$idx]);
        }
        
        sort($nodemap);
        sort($devicemap);
    }
    
    $deviceslist = $DB->GetAll('SELECT id, name FROM netdevices ORDER BY name ASC');
    
    $SMARTY->assign('devicemap', isset($devicemap) ? $devicemap : null);
    $SMARTY->assign('nodemap', $nodemap);
    $SMARTY->assign('deviceslist', $deviceslist);
    $SMARTY->assign('start', $start);
    $SMARTY->assign('mini', $mini);
    $SMARTY->assign('type', $type);
    $SMARTY->assign('emptydb', count($deviceslist) ? false : true);
    $SMARTY->assign('gd', function_exists('imagepng'));
    $SMARTY->assign('ming', function_exists('ming_useswfversion'));
    $SMARTY->display('netdev/netdevmap.html');
} elseif ($graph == 'flash') {
    makemap($map, $seen, $start);
    foreach ($map as $idx => $x) {
        if ($minx == null) {
            $minx = $idx;
        } elseif ($idx < $minx) {
            $minx = $idx;
        }
        
        if ($idx > $maxx) {
            $maxx = $idx;
        }
        foreach ($x as $idy => $y) {
            if ($miny == null) {
                $miny = $idy;
            } elseif ($idy < $miny) {
                $miny = $idy;
            }

            if ($idy > $maxy) {
                $maxy = $idy;
            }
        }
    }

    $widthx = $maxx - $minx;
    $widthy = $maxy - $miny;
    $cellw = 70;
    $cellh = 30;
    $celltmargin = 20;
    $celllmargin = 10;
    $imgwx = $cellw * ($widthx + 2);
    $imgwy = $cellh * ($widthy + 2);

    Ming_setScale(20.0);
    ming_useswfversion(5);
    $m = new SWFMovie();
    $m->setDimension($imgwx, $imgwy);
    $font = new SWFFont("img/Arial.fdb");
    $connections = new SWFShape();

    foreach ($map as $idx => $x) {
        foreach ($x as $idy => $device) {
            $celx = $idx - $minx;
            $cely = $idy - $miny;
            if (preg_match('/^n/', $device)) {
                $device = str_replace('n', '', $device);
                list($nodeid,$device,$linktype) = explode('.', $device);
                $nodemap[$nodeid]['x'] = $celx;
                $nodemap[$nodeid]['y'] = $cely;
                $nodemap[$nodeid]['device'] = $device;
                $nodemap[$nodeid]['linktype'] = $linktype;
            } else {
                $devicemap[$device]['x'] = $celx;
                $devicemap[$device]['y'] = $cely;
            }
        }
    }

    $links = $DB->GetAll('SELECT src, dst, type FROM netlinks');
    if ($links) {
        foreach ($links as $link) {
            if (!isset($devicemap[$link['src']]['x']) || !isset($devicemap[$link['dst']]['x'])) {
                continue;
            }
            $src_celx = $devicemap[$link['src']]['x'];
            $src_cely = $devicemap[$link['src']]['y'];
            $dst_celx = $devicemap[$link['dst']]['x'];
            $dst_cely = $devicemap[$link['dst']]['y'];
            $src_px = (($src_celx * $cellw) + $celllmargin);
            $src_py = (($src_cely * $cellh) + $celltmargin);
            $dst_px = (($dst_celx * $cellw) + $celllmargin);
            $dst_py = (($dst_cely * $cellh) + $celltmargin);
            if (! $link['type']) {
                $connections->setLine(1, 0, 128, 0);
                $connections->movePenTo($src_px+8, $src_py+8);
                $connections->drawLineTo($dst_px+8, $dst_py+8);
            } else {
                $connections->setLine(1, 0, 200, 255);
                $connections->movePenTo($src_px+8, $src_py+8);
                $connections->drawLineTo($dst_px+8, $dst_py+8);
            }
        }
    }

    if ($nodemap) {
        foreach ($nodemap as $node) {
            $src_celx = $node['x'];
            $src_cely = $node['y'];
            $dst_celx = $devicemap[$node['device']]['x'];
            $dst_cely = $devicemap[$node['device']]['y'];
            $src_px = (($src_celx * $cellw) + $celllmargin);
            $src_py = (($src_cely * $cellh) + $celltmargin);
            $dst_px = (($dst_celx * $cellw) + $celllmargin);
            $dst_py = (($dst_cely * $cellh) + $celltmargin);
            if ($node['linktype']=="0") {
                $connections->setLine(1, 255, 0, 0);
                $connections->movePenTo($src_px+4, $src_py+4);
                $connections->drawLineTo($dst_px+4, $dst_py+4);
            } else {
                $connections->setLine(1, 0, 200, 255);
                $connections->movePenTo($src_px+4, $src_py+4);
                $connections->drawLineTo($dst_px+4, $dst_py+4);
            }
        }
    }

    $m->add($connections);

    $im_n_unk = new SWFBitmap(fopen("img/node_unk.jpg", "rb"));
    $im_n_off = new SWFBitmap(fopen("img/node_off.jpg", "rb"));
    $im_n_on  = new SWFBitmap(fopen("img/node_on.jpg", "rb"));
    $im_d_unk = new SWFBitmap(fopen("img/netdev_unk.jpg", "rb"));
    $im_d_off = new SWFBitmap(fopen("img/netdev_off.jpg", "rb"));
    $im_d_on  = new SWFBitmap(fopen("img/netdev_on.jpg", "rb"));
    
    $nodes = $DB->GetAllByKey('SELECT id, name, INET_NTOA(ipaddr) AS ip, lastonline FROM vnodes', 'id');

    if ($nodemap) {
        foreach ($nodemap as $nodeid => $node) {
            $button = new SWFButton();
            $squareshape=new SWFShape();
            $celx = $node['x'];
            $cely = $node['y'];
            $px = (($celx * ($cellw)) + $celllmargin);
            $py = (($cely * ($cellh)) + $celltmargin);

            $n = $nodes[$nodeid];
        
            if ($n['lastonline']) {
                if ((time()-$n['lastonline'])>ConfigHelper::getConfig('phpui.lastonline_limit')) {
                    $myfill = $squareshape->addFill($im_n_off, SWFFILL_TILED_BITMAP);
                } else {
                    $myfill = $squareshape->addFill($im_n_on, SWFFILL_TILED_BITMAP);
                }
            } else {
                $myfill = $squareshape->addFill($im_n_unk, SWFFILL_TILED_BITMAP);
            }
            $myfill->scaleto(9, 9);
            $squareshape->setRightFill($myfill);
            $squareshape->drawLine(15, 0);
            $squareshape->drawLine(0, 15);
            $squareshape->drawLine(-15, 0);
            $squareshape->drawLine(0, -15);
            $button->addShape($squareshape, SWFBUTTON_HIT | SWFBUTTON_UP | SWFBUTTON_DOWN | SWFBUTTON_OVER);
            $button->addAction(new SWFAction("this.getURL('?m=nodeinfo&id=".$nodeid."');"), SWFBUTTON_MOUSEDOWN); // press
            $i=$m->add($button);
            $i->moveTo($px, $py);
        
            drawtext($px + 15, $py - 4, $n['ip'], 0, 0, 255);
            drawtext($px + 15, $py + 10, $n['name'], 0, 0, 0);
        }
    }

    $devices = $DB->GetAllByKey('SELECT n.id, n.name, n.location, MAX(lastonline) AS lastonline 
				    FROM netdevices n 
				    LEFT JOIN vnodes ON (n.id = netdev)
				    GROUP BY n.id, n.name, n.location', 'id');

    foreach ($devicemap as $deviceid => $device) {
        $button = new SWFButton();
        $squareshape=new SWFShape();
        $celx = $device['x'];
        $cely = $device['y'];
        $px = (($celx * ($cellw)) + $celllmargin);
        $py = (($cely * ($cellh)) + $celltmargin);
        
        $d = $devices[$deviceid];
        
        if ($d['lastonline']) {
            if ((time()-$d['lastonline'])>ConfigHelper::getConfig('phpui.lastonline_limit')) {
                $myfill = $squareshape->addFill($im_d_off, SWFFILL_TILED_BITMAP);
            } else {
                $myfill = $squareshape->addFill($im_d_on, SWFFILL_TILED_BITMAP);
            }
        } else {
            $myfill = $squareshape->addFill($im_d_unk, SWFFILL_TILED_BITMAP);
        }
        
        $myfill->scaleto(9, 9);
        $squareshape->setRightFill($myfill);
        $squareshape->drawLine(15, 0);
        $squareshape->drawLine(0, 15);
        $squareshape->drawLine(-15, 0);
        $squareshape->drawLine(0, -15);
        $button->addShape($squareshape, SWFBUTTON_HIT | SWFBUTTON_UP | SWFBUTTON_DOWN | SWFBUTTON_OVER);
        $button->addAction(new SWFAction("this.getURL('?m=netdevinfo&id=".$deviceid."');"), SWFBUTTON_MOUSEDOWN); // press
        $i=$m->add($button);
        $i->moveTo($px, $py);

        if ($devip = $DB->GetCol('SELECT INET_NTOA(ipaddr) 
				    FROM vnodes WHERE ownerid IS NULL AND netdev = ? 
				    ORDER BY ipaddr LIMIT 4', array($deviceid))) {
            if (isset($devip[0])) {
                drawtext($px + 16, $py - (isset($devip[1])?16:8), $devip[0], 0, 0, 255);
            }
            if (isset($devip[1])) {
                drawtext($px + 16, $py - 8, $devip[1], 0, 0, 255);
            }
            if (isset($devip[2])) {
                drawtext($px + 16, $py + 16, $devip[2], 0, 0, 255);
            }
            if (isset($devip[3])) {
                drawtext($px + 16, $py + 24, $devip[3], 0, 0, 255);
            }
        }
        
        drawtext($px + 16, $py + 0, $d['name'], 0, 0, 0);
        drawtext($px + 16, $py + 8, $d['location'], 0, 128, 0);
    }
        
    header("Content-type: application/x-shockwave-flash");
    // Note: this line avoids a bug in InternetExplorer that won't allow
    // downloads over https
    header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: public");
    $m->output();
} else {
    makemap($map, $seen, $start);
    foreach ($map as $idx => $x) {
        if (!$minx) {
            $minx = $idx;
        } elseif ($idx < $minx) {
            $minx = $idx;
        }
        
        if ($idx > $maxx) {
            $maxx = $idx;
        }
        foreach ($x as $idy => $y) {
            if (!$miny) {
                $miny = $idy;
            } elseif ($idy < $miny) {
                $miny = $idy;
            }

            if ($idy > $maxy) {
                $maxy = $idy;
            }
        }
    }

    header('Content-type: image/png');
    $widthx = $maxx - $minx;
    $widthy = $maxy - $miny;
    $cellw = 70;
    $cellh = 30;
    $celltmargin = 20;
    $celllmargin = 10;
    $imgwx = $cellw * ($widthx + 2);
    $imgwy = $cellh * ($widthy + 2);

    $im = imagecreatetruecolor($imgwx, $imgwy);
    $lightbrown = imagecolorallocate($im, 234, 228, 214);
    $black = imagecolorallocate($im, 0, 0, 0);
    $white = imagecolorallocate($im, 255, 255, 255);
    $red = imagecolorallocate($im, 255, 0, 0);
    $green = imagecolorallocate($im, 0, 128, 0);
    $blue = imagecolorallocate($im, 0, 0, 255);
    $lightblue = imagecolorallocate($im, 0, 200, 255);
    $darkred = imagecolorallocate($im, 128, 0, 0);

    imagefill($im, 0, 0, $lightbrown);

    foreach ($map as $idx => $x) {
        foreach ($x as $idy => $device) {
            $celx = $idx - $minx;
            $cely = $idy - $miny;
            if (preg_match('/^n/', $device)) {
                $device = str_replace('n', '', $device);
                list($nodeid,$device,$linktype) = explode('.', $device);
                $nodemap[$nodeid]['x'] = $celx;
                $nodemap[$nodeid]['y'] = $cely;
                $nodemap[$nodeid]['device'] = $device;
                $nodemap[$nodeid]['linktype'] = $linktype;
            } else {
                $devicemap[$device]['x'] = $celx;
                $devicemap[$device]['y'] = $cely;
            }
        }
    }

    imagesetthickness($im, 2);
    
    $links = $DB->GetAll('SELECT src, dst, type FROM netlinks');
    if ($links) {
        foreach ($links as $link) {
            $src_celx = isset($devicemap[$link['src']]['x']) ? $devicemap[$link['src']]['x'] : 0;
            $src_cely = isset($devicemap[$link['src']]['y']) ? $devicemap[$link['src']]['y'] : 0;
            $dst_celx = isset($devicemap[$link['dst']]['x']) ? $devicemap[$link['dst']]['x'] : 0;
            $dst_cely = isset($devicemap[$link['dst']]['y']) ? $devicemap[$link['dst']]['y'] : 0;
            $src_px = (($src_celx * $cellw) + $celllmargin);
            $src_py = (($src_cely * $cellh) + $celltmargin);
            $dst_px = (($dst_celx * $cellw) + $celllmargin);
            $dst_py = (($dst_cely * $cellh) + $celltmargin);
    
            $color = $link['type'] ? $lightblue : $green;
            imageline($im, $src_px+8, $src_py+8, $dst_px+8, $dst_py+8, $color);
        }
    }

    imagesetthickness($im, 1);
    
    if ($nodemap) {
        foreach ($nodemap as $node) {
            $src_celx = $node['x'];
            $src_cely = $node['y'];
            $dst_celx = $devicemap[$node['device']]['x'];
            $dst_cely = $devicemap[$node['device']]['y'];
            $src_px = (($src_celx * $cellw) + $celllmargin);
            $src_py = (($src_cely * $cellh) + $celltmargin);
            $dst_px = (($dst_celx * $cellw) + $celllmargin);
            $dst_py = (($dst_cely * $cellh) + $celltmargin);

            $color = $node['linktype'] ? $lightblue : $red;
            imageline($im, $src_px+4, $src_py+4, $dst_px+4, $dst_py+4, $color);
        }
    }

    $im_n_unk = imagecreatefrompng('img/node_unk.png');
    $im_n_off = imagecreatefrompng('img/node_off.png');
    $im_n_on = imagecreatefrompng('img/node_on.png');
    $im_d_unk = imagecreatefrompng('img/netdev_unk.png');
    $im_d_off = imagecreatefrompng('img/netdev_off.png');
    $im_d_on = imagecreatefrompng('img/netdev_on.png');

    $nodes = $DB->GetAllByKey('SELECT id, name, INET_NTOA(ipaddr) AS ip, lastonline FROM vnodes', 'id');

    if ($nodemap) {
        foreach ($nodemap as $nodeid => $node) {
            $celx = $node['x'];
            $cely = $node['y'];
            $px = (($celx * ($cellw)) + $celllmargin);
            $py = (($cely * ($cellh)) + $celltmargin);

            $n = $nodes[$nodeid];

            if ($n['lastonline']) {
                if ((time()-$n['lastonline'])>ConfigHelper::getConfig('phpui.lastonline_limit')) {
                    imagecopy($im, $im_n_off, $px, $py, 0, 0, 16, 16);
                } else {
                    imagecopy($im, $im_n_on, $px, $py, 0, 0, 16, 16);
                }
            } else {
                imagecopy($im, $im_n_unk, $px, $py, 0, 0, 16, 16);
            }
        
            pngdrawtext($im, 1, $px + 15, $py - 8, $n['ip'], $blue, $lightbrown);
            pngdrawtext($im, 1, $px + 15, $py + 2, $n['name'], $black, $lightbrown);
        }
    }

    $devices = $DB->GetAllByKey('SELECT n.id, n.name, n.location, MAX(lastonline) AS lastonline 
				    FROM netdevices n
				    LEFT JOIN vnodes ON (n.id = netdev)
				    GROUP BY n.id, n.name, n.location', 'id');

    foreach ($devicemap as $deviceid => $device) {
        $celx = $device['x'];
        $cely = $device['y'];
        $px = (($celx * ($cellw)) + $celllmargin);
        $py = (($cely * ($cellh)) + $celltmargin);

        $d = $devices[$deviceid];
        
        if ($d['lastonline']) {
            if ((time()-$d['lastonline'])>ConfigHelper::getConfig('phpui.lastonline_limit')) {
                imagecopy($im, $im_d_off, $px, $py, 0, 0, 16, 16);
            } else {
                imagecopy($im, $im_d_on, $px, $py, 0, 0, 16, 16);
            }
        } else {
            imagecopy($im, $im_d_unk, $px, $py, 0, 0, 16, 16);
        }
        
        if ($devip = $DB->GetCol('SELECT INET_NTOA(ipaddr) FROM vnodes
				    WHERE ownerid IS NULL AND netdev = ?
				    ORDER BY ipaddr LIMIT 4', array($deviceid))) {
            if (isset($devip[0])) {
                pngdrawtext($im, 1, $px + 20, $py - (isset($devip[1])?17:8), $devip[0], $blue, $lightbrown);
            }
            if (isset($devip[1])) {
                pngdrawtext($im, 1, $px + 20, $py - 8, $devip[1], $blue, $lightbrown);
            }
            if (isset($devip[2])) {
                pngdrawtext($im, 1, $px + 20, $py + 17, $devip[2], $blue, $lightbrown);
            }
            if (isset($devip[3])) {
                pngdrawtext($im, 1, $px + 20, $py + 26, $devip[3], $blue, $lightbrown);
            }
        }
        
        pngdrawtext($im, 3, $px + 20, $py + 2, $d['name'], $black, $lightbrown);
        pngdrawtext($im, 2, $px + 20, $py + 18, $d['location'], $green, $lightbrown);
    }
        
    imagepng($im);
}
