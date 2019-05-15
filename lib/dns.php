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
# http://data.iana.org/TLD/tlds-alpha-by-domain.txt
# Version 2015022800, Last Updated Sat Feb 28 07:07:02 2015 UTC
$valid_tlds = array(
'abogado','ac','academy','accountants','active','actor','ad','adult','ae','aero',
'af','ag','agency','ai','airforce','al','allfinanz','alsace','am','amsterdam','an',
'android','ao','apartments','aq','aquarelle','ar','archi','army','arpa','as','asia',
'associates','at','attorney','au','auction','audio','autos','aw','ax','axa','az','ba',
'band','bank','bar','barclaycard','barclays','bargains','bayern','bb','bd','be','beer',
'berlin','best','bf','bg','bh','bi','bid','bike','bingo','bio','biz','bj','black',
'blackfriday','bloomberg','blue','bm','bmw','bn','bnpparibas','bo','boats','boo',
'boutique','br','brussels','bs','bt','budapest','build','builders','business','buzz',
'bv','bw','by','bz','bzh','ca','cab','cal','camera','camp','cancerresearch','canon',
'capetown','capital','caravan','cards','care','career','careers','cartier','casa',
'cash','casino','cat','catering','cbn','cc','cd','center','ceo','cern','cf','cg',
'ch','channel','chat','cheap','christmas','chrome','church','ci','citic','city','ck',
'cl','claims','cleaning','click','clinic','clothing','club','cm','cn','co','coach',
'codes','coffee','college','cologne','com','community','company','computer','condos',
'construction','consulting','contractors','cooking','cool','coop','country','courses',
'cr','credit','creditcard','cricket','crs','cruises','cu','cuisinella','cv','cw','cx',
'cy','cymru','cz','dabur','dad','dance','dating','day','dclk','de','deals','degree',
'delivery','democrat','dental','dentist','desi','design','dev','diamonds','diet',
'digital','direct','directory','discount','dj','dk','dm','dnp','do','docs','domains',
'doosan','durban','dvag','dz','eat','ec','edu','education','ee','eg','email','emerck',
'energy','engineer','engineering','enterprises','equipment','er','es','esq','estate',
'et','eu','eurovision','eus','events','everbank','exchange','expert','exposed','fail',
'fans','farm','fashion','feedback','fi','finance','financial','firmdale','fish',
'fishing','fit','fitness','fj','fk','flights','florist','flowers','flsmidth','fly',
'fm','fo','foo','football','forsale','foundation','fr','frl','frogans','fund',
'furniture','futbol','ga','gal','gallery','garden','gb','gbiz','gd','gdn','ge','gent',
'gf','gg','ggee','gh','gi','gift','gifts','gives','gl','glass','gle','global','globo',
'gm','gmail','gmo','gmx','gn','goldpoint','goog','google','gop','gov','gp','gq','gr',
'graphics','gratis','green','gripe','gs','gt','gu','guide','guitars','guru','gw','gy',
'hamburg','hangout','haus','healthcare','help','here','hermes','hiphop','hiv','hk',
'hm','hn','holdings','holiday','homes','horse','host','hosting','house','how','hr',
'ht','hu','ibm','id','ie','ifm','il','im','immo','immobilien','in','industries','info',
'ing','ink','institute','insure','int','international','investments','io','iq','ir',
'irish','is','it','iwc','jcb','je','jetzt','jm','jo','jobs','joburg','jp','juegos',
'kaufen','kddi','ke','kg','kh','ki','kim','kitchen','kiwi','km','kn','koeln','kp','kr',
'krd','kred','kw','ky','kyoto','kz','la','lacaixa','land','lat','latrobe','lawyer',
'lb','lc','lds','lease','legal','lgbt','li','lidl','life','lighting','limited','limo',
'link','lk','loans','london','lotte','lotto','lr','ls','lt','ltda','lu','luxe','luxury',
'lv','ly','ma','madrid','maison','management','mango','market','marketing','marriott',
'mc','md','me','media','meet','melbourne','meme','memorial','menu','mg','mh','miami',
'mil','mini','mk','ml','mm','mn','mo','mobi','moda','moe','monash','money','mormon',
'mortgage','moscow','motorcycles','mov','mp','mq','mr','ms','mt','mu','museum','mv',
'mw','mx','my','mz','na','nagoya','name','navy','nc','ne','net','network','neustar',
'new','nexus','nf','ng','ngo','nhk','ni','nico','ninja','nl','no','np','nr','nra',
'nrw','ntt','nu','nyc','nz','okinawa','om','one','ong','onl','ooo','org','organic',
'osaka','otsuka','ovh','pa','paris','partners','parts','party','pe','pf','pg','ph',
'pharmacy','photo','photography','photos','physio','pics','pictures','pink','pizza',
'pk','pl','place','plumbing','pm','pn','pohl','poker','porn','post','pr','praxi',
'press','pro','prod','productions','prof','properties','property','ps','pt','pub',
'pw','py','qa','qpon','quebec','re','realtor','recipes','red','rehab','reise','reisen',
'reit','ren','rentals','repair','report','republican','rest','restaurant','reviews',
'rich','rio','rip','ro','rocks','rodeo','rs','rsvp','ru','ruhr','rw','ryukyu','sa',
'saarland','sale','samsung','sarl','saxo','sb','sc','sca','scb','schmidt','school',
'schule','schwarz','science','scot','sd','se','services','sew','sexy','sg','sh',
'shiksha','shoes','shriram','si','singles','sj','sk','sky','sl','sm','sn','so',
'social','software','sohu','solar','solutions','soy','space','spiegel','sr','st',
'study','style','su','sucks','supplies','supply','support','surf','surgery','suzuki',
'sv','sx','sy','sydney','systems','sz','taipei','tatar','tattoo','tax','tc','td',
'technology','tel','temasek','tennis','tf','tg','th','tienda','tips','tires','tirol',
'tj','tk','tl','tm','tn','to','today','tokyo','tools','top','toshiba','town','toys',
'tr','trade','training','travel','trust','tt','tui','tv','tw','tz','ua','ug','uk',
'university','uno','uol','us','uy','uz','va','vacations','vc','ve','vegas','ventures',
'versicherung','vet','vg','vi','viajes','video','villas','vision','vlaanderen','vn',
'vodka','vote','voting','voto','voyage','vu','wales','wang','watch','webcam','website',
'wed','wedding','wf','whoswho','wien','wiki','williamhill','wme','work','works','world',
'ws','wtc','wtf','xn--1qqw23a','xn--3bst00m','xn--3ds443g','xn--3e0b707e','xn--45brj9c',
'xn--45q11c','xn--4gbrim','xn--55qw42g','xn--55qx5d','xn--6frz82g','xn--6qq986b3xl',
'xn--80adxhks','xn--80ao21a','xn--80asehdb','xn--80aswg','xn--90a3ac','xn--90ais',
'xn--b4w605ferd','xn--c1avg','xn--cg4bki','xn--clchc0ea0b2g2a9gcd','xn--czr694b',
'xn--czrs0t','xn--czru2d','xn--d1acj3b','xn--d1alf','xn--fiq228c5hs','xn--fiq64b',
'xn--fiqs8s','xn--fiqz9s','xn--flw351e','xn--fpcrj9c3d','xn--fzc2c9e2c','xn--gecrj9c',
'xn--h2brj9c','xn--hxt814e','xn--i1b6b1a6a2e','xn--io0a7i','xn--j1amh','xn--j6w193g',
'xn--kprw13d','xn--kpry57d','xn--kput3i','xn--l1acc','xn--lgbbat1ad8j','xn--mgb9awbf',
'xn--mgba3a4f16a','xn--mgbaam7a8h','xn--mgbab2bd','xn--mgbayh7gpa','xn--mgbbh1a71e',
'xn--mgbc0a9azcg','xn--mgberp4a5d4ar','xn--mgbx4cd0ab','xn--ngbc5azd','xn--node',
'xn--nqv7f','xn--nqv7fs00ema','xn--o3cw4h','xn--ogbpf8fl','xn--p1acf','xn--p1ai',
'xn--pgbs0dh','xn--q9jyb4c','xn--qcka1pmc','xn--rhqv96g','xn--s9brj9c','xn--ses554g',
'xn--unup4y','xn--vermgensberater-ctb','xn--vermgensberatung-pwb','xn--vhquv',
'xn--wgbh1c','xn--wgbl6a','xn--xhq521b','xn--xkc2al3hye2a','xn--xkc2dl3a5ee0h',
'xn--yfro4i67o','xn--ygbi2ammx','xn--zfr164b','xxx','xyz','yachts','yandex','ye',
'yodobashi','yoga','yokohama','youtube','yt','za','zip','zm','zone','zuerich','zw'
 );


function check_hostname_fqdn($hostname, $wildcard = false, $dns_strict_tld_check = false)
{

        global $valid_tlds;
        $hostname = trim($hostname, '.');

    if (strlen($hostname) > 255) {
            return trans('The hostname is too long!');
    }

        $hostname_labels = explode('.', $hostname);
        $label_count = count($hostname_labels);

    foreach ($hostname_labels as $hostname_label) {
        if ($wildcard && !isset($first)) {
            if (!preg_match('/^(\*|[a-zA-Z0-9-\/_]+)$/', $hostname_label)) {
                return trans('You have invalid characters in your hostname!');
            }
                $first = 1;
        } else {
            if (!preg_match('/^[a-zA-Z0-9-\/_]+$/', $hostname_label)) {
                return trans('You have invalid characters in your hostname!');
            }
        }
        if ($hostname_label[0] == '-' || substr($hostname_label, -1, 1) == '-') {
            return trans('A hostname can not start or end with a dash!');
        }
        if (strlen($hostname_label) < 1 || strlen($hostname_label) > 63) {
            return trans('Given hostname or one of the labels is too short or too long!');
        }
    }

    if ($hostname_labels[$label_count-1] == 'arpa'
        && (substr_count($hostname_labels[0], '/') == 1 xor substr_count($hostname_labels[1], '/') == 1)
    ) {
        if (substr_count($hostname_labels[0], '/') == 1) {
                $array = explode('/', $hostname_labels[0]);
        } else {
                $array = explode('/', $hostname_labels[1]);
        }
        if (count($array) != 2) {
            return trans('Invalid hostname!');
        }
        if (!is_numeric($array[0]) || $array[0] < 0 || $array[0] > 255) {
            return trans('Invalid hostname!');
        }
        if (!is_numeric($array[1]) || $array[1] < 25 || $array[1] > 31) {
            return trans('Invalid hostname!');
        }
    } else {
        if (substr_count($hostname, '/') > 0) {
            return trans('Given hostname has too many slashes!');
        }
    }

    if ($dns_strict_tld_check && !in_array($hostname_labels[$label_count-1], $valid_tlds)) {
           return trans('You are using an invalid top level domain!');
    }

        return false;
}


function update_soa_serial($did)
{
    global $DB;

    $record = $DB->GetRow("SELECT * from records where domain_id = ? and type='SOA'", array($did));

    $soa = explode(' ', $record['content']);

    if ($soa[2] == '0') {
                return true;
    } elseif ($soa[2] == date('Ymd') . '99') {
            return true;
    } else {
            $today = date('Ymd');

            // Determine revision.
        if (strncmp($today, $soa[2], 8) === 0) {
                // Current serial starts with date of today, so we need to update
                // the revision only. To do so, determine current revision first,
                // then update counter.
                $revision = (int) substr($soa[2], -2);
                ++$revision;
        } else {
                // Current serial did not start of today, so it's either an older
                // serial or a serial that does not adhere the recommended syntax
                // of RFC-1912. In either way, set a fresh serial
                $revision = '00';
        }

            $serial = $today . str_pad($revision, 2, '0', STR_PAD_LEFT);
        ;

            // Change serial in SOA array.
            $soa[2] = $serial;

            // Build new SOA record content and update the database
            $DB->Execute(
                'UPDATE records SET content = ? WHERE id = ?',
                array(implode(' ', $soa), $record['id'])
            );
    }
}

/*
 * Parses record content (from DB) into separate form fields
 */
function parse_dns_record(&$record)
{
    $record['name'] = substr($record['name'], 0, -(strlen($record['domainname']) + 1));

    switch ($record['type']) {
        case 'A':
        case 'AAAA':
            $record['ipdst'] = $record['content'];
            break;
        case 'NS':
            $record['ns'] = $record['content'];
            break;
        case 'MX':
            $record['mailserver'] = $record['content'];
            break;
        case 'CNAME':
            $record['alias'] = $record['name'];
            $record['domain'] = $record['content'];
            break;
        case 'TXT':
        case 'SPF':
            $record['desc'] = $record['content'];
            break;
        case 'PTR':
            $record['domain'] = $record['content'];
            break;
        case 'SOA':
            $cnt = preg_split('/[\s\t]+/', $record['content']);
            $record['ns'] = $cnt[0];
            $record['email'] = $cnt[1];
            $record['serial'] = $cnt[2];
            $record['refresh'] = $cnt[3];
            $record['retry'] = $cnt[4];
            $record['expire'] = $cnt[5];
            $record['minttl'] = $cnt[6];
            break;
        case 'SSHFP':
            $cnt = preg_split('/[\s\t]+/', $record['content']);
            $record['algo'] = $cnt[0];
            $record['ftype'] = $cnt[1];
            $record['fingerprint'] = $cnt[2];
            break;
        case 'SRV':
            $cnt = preg_split('/[\s\t]+/', $record['content']);
            $record['weight'] = $cnt[0];
            $record['port'] = $cnt[1];
            $record['domain'] = $cnt[2];
            break;
        case 'HINFO':
            $cnt = preg_split('/[\s\t]+/', $record['content']);
            $record['cpu'] = $cnt[0];
            $record['os'] = $cnt[1];
            break;
    }
}

/*
 * Validates record data (from html form)
 * Errors are returned by reference in 4th argument
 */
function validate_dns_record(&$record, &$error)
{
    $arpa_records = array('PTR','SOA','NS','TXT','CNAME','MX','SPF','NAPTR','URL','MBOXFW','CURL','SSHFP');

    // domena in-addr.arpa
    if (preg_match('/in-addr\.arpa$/', $record['domainname'])) {
        if (!in_array($record['type'], $arpa_records)) {
            $error['type'] = trans('Wrong record type!');
        }
    }

    if ($error) {
        return;
    }

    if (!in_array($record['type'], array('SOA', 'CNAME')) && !empty($record['name'])) {
        if ($errorname = check_hostname_fqdn($record['name'], true, false)) {
            $error['name'] = $errorname;
        }
    }

    switch ($record['type']) {
        case 'A':
            if (empty($record['ipdst'])) {
                $error['ipdst'] = trans('Field cannot be empty!');
            } else if (!check_ip($record['ipdst'])) {
                $error['ipdst'] = trans('Invalid IP address!');
            }
            break;
        case 'AAAA':
            if (empty($record['ipdst'])) {
                $error['ipdst'] = trans('Field cannot be empty!');
            } else if (!check_ipv6($record['ipdst'])) {
                $error['ipdst'] = trans('Invalid IP address!');
            }
            break;
        case 'NS':
            if ($errorcontent = check_hostname_fqdn($record['ns'], false, true)) {
                    $error['ns'] = $errorcontent;
            }
            if (preg_match('/in-addr\.arpa$/', $record['domainname'])) {
                if ($errorcontent = check_hostname_fqdn($record['ns'], false, true)) {
                        $error['ns'] = $errorcontent;
                }
            }
            break;
        case 'MX':
            if (empty($record['mailserver'])) {
                $error['mailserver'] = trans('Field cannot be empty!');
            } else if ($errorcontent = check_hostname_fqdn($record['mailserver'], false, true)) {
                                $error['mailserver'] = $errorcontent;
            }

            if (empty($record['prio'])) {
                $error['prio'] = trans('Field cannot be empty!');
            } else if (!preg_match('/^[0-9]+$/', $record['prio'])) {
                $error['prio'] = trans('Invalid format!');
            }
            break;
        case 'CNAME':
            if ($errorname = check_hostname_fqdn($record['alias'], true, false)) {
                $error['alias'] = $errorname;
            }
            break;
        case 'TXT':
        case 'SPF':
            if (empty($record['desc'])) {
                $error['desc'] = trans('Field cannot be empty!');
            }
            break;
        case 'PTR':
            if ($errorcontent = check_hostname_fqdn($record['domain'], false, true)) {
                $error['domain'] = $errorcontent;
            }
            break;
        case 'SOA':
            foreach (array('serial', 'refresh', 'retry', 'expire', 'minttl') as $idx) {
                if (empty($record[$idx])) {
                    $error[$idx] = trans('Field cannot be empty!');
                } else if (!preg_match('/^[0-9]+$/', $record[$idx])) {
                    $error[$idx] = trans('Invalid format!');
                }
            }
            break;
        case 'SSHFP':
            foreach (array('algo', 'ftype', 'fingerprint') as $idx) {
                if (empty($record[$idx])) {
                    $error[$idx] = trans('Field cannot be empty!');
                } else if ($idx != 'fingerprint' && !preg_match('/^[0-9]+$/', $record[$idx])) {
                    $error[$idx] = trans('Invalid format!');
                }
            }
            break;
        case 'HINFO':
            foreach (array('cpu', 'os') as $idx) {
                if (empty($record[$idx])) {
                    $error[$idx] = trans('Field cannot be empty!');
                }
                // @TODO: RFC1010 data format checking
            }
            break;
        case 'SRV':
            foreach (array('port', 'weight') as $idx) {
                if (empty($record[$idx])) {
                    $error[$idx] = trans('Field cannot be empty!');
                } else if (!preg_match('/^[0-9]+$/', $record[$idx])) {
                    $error[$idx] = trans('Invalid format!');
                }
            }
            break;
        default: // NAPTR
            if (empty($record['content'])) {
                $error['content'] = trans('Field cannot be empty!');
            }
    }

    if ($error) {
        return;
    }

    // set 'name' and 'content', 'prio' fields to write into DB
    switch ($record['type']) {
        case 'A':
        case 'AAAA':
            $record['content'] = $record['ipdst'];
            break;
        case 'NS':
            $record['content'] = $record['ns'];
            break;
        case 'MX':
            $record['content'] = $record['mailserver'];
            break;
        case 'CNAME':
            $record['name'] = $record['alias'];
            $record['content'] = $record['domain'];
            break;
        case 'TXT':
        case 'SPF':
            $record['content'] = $record['desc'];
            break;
        case 'PTR':
            $record['content'] = $record['domain'];
            break;
        case 'SOA':
            $record['name'] = '';
            $record['content'] = $record['ns'].' '.str_replace('@', '.', $record['email'])
                .' '.$record['serial'].' '.$record['refresh'].' '.$record['retry']
                .' '.$record['expire'].' '.$record['minttl'];
            break;
        case 'SSHFP':
            $record['content'] = $record['algo'].' '.$record['ftype'].' '.$record['fingerprint'];
            break;
        case 'HINFO':
            $record['content'] = $record['cpu'].' '.$record['os'];
            break;
        case 'SRV':
            $record['content'] = $record['weight'].' '.$record['port'].' '.$record['domain'];
            break;
    }

    if ($record['type'] != 'MX') {
        $record['prio'] = 0;
    }
}
