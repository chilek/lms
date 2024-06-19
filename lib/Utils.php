<?php

/**
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2021 LMS Developers
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

use GusApi\GusApi;
use GusApi\RegonConstantsInterface;
use GusApi\Exception\InvalidUserKeyException;
use GusApi\ReportTypes;
use GusApi\ReportTypeMapper;
use proj4php\Proj4php;
use proj4php\Proj;
use proj4php\Point;
use Ramsey\Uuid\Uuid;

class Utils
{
    public const GUS_REGON_API_RESULT_BAD_KEY = 1;
    public const GUS_REGON_API_RESULT_NO_DATA = 2;
    public const GUS_REGON_API_RESULT_AMBIGUOUS = 3;
    public const GUS_REGON_API_RESULT_UNKNOWN_ERROR = 4;

    public const GUS_REGON_API_SEARCH_TYPE_TEN = 1;
    public const GUS_REGON_API_SEARCH_TYPE_REGON = 2;
    public const GUS_REGON_API_SEARCH_TYPE_RBE = 3;

    public const PESEL_STATUS_RESERVED = 'ZASTRZEZONY';
    public const PESEL_STATUS_NOT_RESERVED = 'NIEZASTRZEZONY';

    public static function filterIntegers(array $params)
    {
        return array_filter($params, function ($value) {
            if (!isset($value)) {
                return false;
            }
            $string = strval($value);
            if (strlen($string) && $string[0] == '-') {
                $string = ltrim($string, '-');
            }
            return ctype_digit($string);
        });
    }

    public static function filterArrayByKeys(array $array, array $keys, $reverse = false)
    {
        $result = array();
        $keys = array_flip($keys);
        array_walk($array, function ($item, $key) use ($reverse, $keys, &$result) {
            if ($reverse) {
                if (!isset($keys[$key])) {
                    $result[$key] = $item;
                }
            } elseif (isset($keys[$key])) {
                $result[$key] = $item;
            }
        });
        return $result;
    }

    public static function array_column(array $array, $column_key, $index_key = null)
    {
        if (!is_array($array)) {
            return $array;
        }
        $result = array();
        foreach ($array as $idx => $item) {
            if (isset($index_key)) {
                $result[$item[$index_key]] = empty($column_key) ? $item : $item[$column_key];
            } else {
                $result[$idx] = empty($column_key) ? $item : $item[$column_key];
            }
        }
        return $result;
    }

    public static function array_keys_add_prefix(array $array)
    {
        if (!is_array($array)) {
            return $array;
        }
        $result = array();

        function addkeyprefix($k)
        {
            return 'old_'.$k;
        }

        $result = array_combine(array_map('addkeyprefix', array_keys($array)), $array);
        return $result;
    }

    // taken from RoundCube
    /**
     * Generate a random string
     *
     * @param int  $length String length
     * @param bool $raw    Return RAW data instead of ascii
     *
     * @return string The generated random string
     */
    public static function randomBytes($length, $raw = false)
    {
        $hextab  = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $tabsize = strlen($hextab);

        // Use PHP7 true random generator
        if ($raw && function_exists('random_bytes')) {
            return random_bytes($length);
        }

        if (!$raw && function_exists('random_int')) {
            $result = '';
            while ($length-- > 0) {
                $result .= $hextab[random_int(0, $tabsize - 1)];
            }

            return $result;
        }

        $random = openssl_random_pseudo_bytes($length);

        if ($random === false && $length > 0) {
            throw new Exception("Failed to get random bytes");
        }

        if (!$raw) {
            for ($x = 0; $x < $length; $x++) {
                $random[$x] = $hextab[ord($random[$x]) % $tabsize];
            }
        }

        return $random;
    }

    public static function isAllowedIP($ip, $allow_from)
    {
        if (empty($allow_from)) {
            return true;
        }

        $allowedlist = explode(',', $allow_from);

        foreach ($allowedlist as $value) {
            $mask = '';

            if (strpos($value, '/') === false) {
                $net = $value;
            } else {
                [$net, $mask] = explode('/', $value);
            }

            $net = trim($net);
            $mask = trim($mask);

            if ($mask == '') {
                $mask = '255.255.255.255';
            } elseif (is_numeric($mask)) {
                if ($mask == '0') {
                    return true;
                }
                $mask = prefix2mask($mask);
            }

            if (isipinstrict($ip, $net, $mask)) {
                return true;
            }
        }

        return false;
    }

    public static function docEntityCount($entities)
    {
        return substr_count(sprintf("%b", $entities), '1');
    }

    public static function triggerError($error_msg, $error_type = E_USER_NOTICE, $context = 1)
    {
        $stack = debug_backtrace();
        for ($i = 0; $i < $context; $i++) {
            if (false === ($frame = next($stack))) {
                break;
            }
            $error_msg .= ", from " . $frame['function'] . ':' . $frame['file'] . ' line ' . $frame['line'];
        }
        return trigger_error($error_msg, $error_type);
    }

    public static function LoadMarkdownDocumentation($variable_name = null)
    {
        $markdown_documentation_file = ConfigHelper::getConfig(
            'phpui.markdown_documentation_file',
            SYS_DIR . DIRECTORY_SEPARATOR . 'doc' . DIRECTORY_SEPARATOR . 'Zmienne-konfiguracyjne-LMS-Plus.md'
        );
        if (!file_exists($markdown_documentation_file)) {
            return null;
        }

        if (isset($variable_name)) {
            $content = file_get_contents($markdown_documentation_file);
            if (($startpos = strpos($content, '## ' . $variable_name)) === false) {
                return null;
            }
            $endpos = strpos($content, '## ', $startpos + 1);
            if ($endpos === false) {
                $chunk = substr($content, $startpos);
            } else {
                $chunk = substr($content, $startpos, $endpos - $startpos);
            }
            if (($endpos = strpos($chunk, '***')) !== false) {
                $chunk = substr($chunk, 0, $endpos);
            }
            $lines = explode("\n", $chunk);
            array_shift($lines);
            foreach ($lines as &$line) {
                $line = trim($line);
            }
            unset($line);
            return preg_replace_callback(
                '#\[([^]]+)\]\(([^)]*)\)#',
                function ($matches) {
                    if (preg_match('#https?://#', $matches[2])) {
                        return $matches[0];
                    } elseif (preg_match('#\[([^]]+)\]\(([^/][^\)]*)\)#', $matches[0])) {
                        return '[' . $matches[1] . '](https://wiki.lms.plus/' . $matches[2] . ')';
                    } elseif (preg_match('#\[([^]]+)\]\((/[^\)]*)\)#', $matches[0])) {
                        return '[' . $matches[1] . '](https://github.com' . $matches[2] . ')';
                    }
                },
                implode("\n", $lines)
            );
        }

        $result = array();

        if (empty($markdown_documentation_file) || !file_exists($markdown_documentation_file)) {
            return $result;
        }

        $content = file_get_contents($markdown_documentation_file);
        if (empty($content)) {
            return $result;
        }

        $content = explode(
            "\n",
            preg_replace_callback(
                '#\[([^]]+)\]\(([^)]*)\)#',
                function ($matches) {
                    if (preg_match('#https?://#', $matches[2])) {
                        return $matches[0];
                    } elseif (preg_match('#\[([^]]+)\]\(([^/][^\)]*)\)#', $matches[0])) {
                        return '[' . $matches[1] . '](https://wiki.lms.plus/' . $matches[2] . ')';
                    } elseif (preg_match('#\[([^]]+)\]\((/[^\)]*)\)#', $matches[0])) {
                        return '[' . $matches[1] . '](https://github.com' . $matches[2] . ')';
                    }
                },
                $content
            )
        );

        $variable = null;
        $buffer = '';
        foreach ($content as $line) {
            if (preg_match('/^##\s+(?<variable>.+)\r?/', $line, $m)) {
                if ($variable && $buffer) {
                    [$section, $var] = explode('.', $variable);
                    if (!isset($result[$section])) {
                        $result[$section] = array();
                    }
                    $result[$section][$var] = $buffer;
                }
                $variable = $m['variable'];
                $buffer = '';
            } elseif (preg_match('/^\*\*\*/', $line)) {
                if ($variable && $buffer) {
                    [$section, $var] = explode('.', $variable);
                    if (!isset($result[$section])) {
                        $result[$section] = array();
                    }
                    $result[$section][$var] = $buffer;
                }
                $variable = null;
                $buffer = '';
            } elseif ($variable) {
                $buffer .= ($buffer != '' ? "\n" : '') . $line;
            }
        }
        if ($variable && $buffer) {
            [$section, $var] = explode('.', $variable);
            if (!isset($result[$section])) {
                $result[$section] = array();
            }
            $result[$section][$var] = $buffer;
        }

        return $result;
    }

    public static function MarkdownToHtml($markdown)
    {
        static $markdown_parser = null;
        if (!isset($markdown_parser)) {
            $markdown_parser = new Parsedown();
        }
        return $markdown_parser->Text($markdown);
    }

    public static function getDefaultCustomerConsents()
    {
        global $CCONSENTS;

        $result = array();

        $value = ConfigHelper::getConfig('phpui.default_customer_consents', 'data_processing,transfer_form', true);
        if (!empty($value)) {
            $values = array_flip(preg_split('/[\s\.,;]+/', $value, -1, PREG_SPLIT_NO_EMPTY));
            foreach ($CCONSENTS as $consent_id => $consent) {
                if (is_array($consent)) {
                    if ($consent['type'] == 'selection') {
                        foreach ($consent['values'] as $sub_consent_id => $subconsent) {
                            if (isset($subconsent['name']) && isset($values[$subconsent['name']])) {
                                $result[$consent_id] = $sub_consent_id;
                            }
                        }
                    } else {
                        if (isset($values[$consent['name']])) {
                            $result[$consent_id] = $consent_id;
                        }
                    }
                }
            }
        }

        return $result;
    }

    public static function parseCssProperties($text)
    {
        $result = array();
        $text = preg_replace('/\s/', '', $text);
        $properties = explode(';', $text);
        if (!empty($properties)) {
            foreach ($properties as $property) {
                [$name, $value] = explode(':', $property);
                $result[$name] = $value;
            }
        }
        return $result;
    }

    public static function findNextBusinessDay($date = null)
    {
        $holidaysByYear = array();

        [$year, $month, $day, $weekday] = explode('/', date('Y/m/j/N', $date ?: time()));
        $date = mktime(0, 0, 0, $month, $day, $year);

        while (true) {
            if (!isset($holidaysByYear[$year])) {
                $holidaysByYear[$year] = getHolidays($year);
            }
            if ($weekday < 6 && !isset($holidaysByYear[$year][$date])) {
                return $date;
            }
            $date = strtotime('+1 day', $date);
            [$year, $weekday] = explode('/', date('Y/N', $date));
        }
    }

    public static function validateVat($trader_country, $trader_id, $requester_country, $requester_id)
    {
        static $vies = null;

        $trader_id = strpos($trader_id, $trader_country) == 0
            ? preg_replace('/^' . $trader_country . '/', '', $trader_id) : $trader_id;
        $requester_id = strpos($requester_id, $requester_country) == 0
            ? preg_replace('/^' . $requester_country . '/', '', $requester_id) : $requester_id;

        if (!isset($vies)) {
            $vies = new \DragonBe\Vies\Vies();
            if (!$vies->getHeartBeat()->isAlive()) {
                throw new Exception('VIES service is not available at the moment, please try again later.');
            }
        }

        $vatResult = $vies->validateVat(
            $trader_country,    // Trader country code
            $trader_id,         // Trader VAT ID
            $requester_country, // Requester country code
            $requester_id       // Requester VAT ID
        );

        return $vatResult->isValid();
    }

    public static function validatePlVat($trader_country, $trader_id)
    {
        static $curl = null;

        if (!isset($curl)) {
            if (!function_exists('curl_init')) {
                throw new Exception(trans('Curl extension not loaded!'));
            }
            $curl = curl_init();
        }

        $trader_id = strpos($trader_id, $trader_country) == 0
            ? preg_replace('/^' . $trader_country . '/', '', $trader_id) : $trader_id;

        curl_setopt($curl, CURLOPT_URL, 'https://wl-api.mf.gov.pl/api/search/nip/' . $trader_id . '?date=' . date('Y-m-d'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/json'));

        $result = curl_exec($curl);
        if (curl_error($curl)) {
            throw new Exception('Communication error: ' . curl_error($curl));
        }

/*
        $info = curl_getinfo($curl);
        if ($info['http_code'] != '200') {
            throw new Exception('Communication error. Http code: ' . $info['http_code']);
        }
*/

        if (empty($result)) {
            return false;
        }

        $result = json_decode($result, true);
        if (empty($result) || !isset($result['result']['subject']['statusVat'])) {
            return false;
        }

        return $result['result']['subject']['statusVat'] == 'Czynny';
    }

    public static function determineAllowedCustomerStatus($value, $default = null)
    {
        global $CSTATUSES;

        if (!empty($value)) {
            $value = preg_replace('/\s+/', ',', $value);
            $value = preg_split('/\s*[,;]\s*/', $value, -1, PREG_SPLIT_NO_EMPTY);
        }
        if (empty($value)) {
            if (empty($default) || !is_array($default)) {
                if ($default === -1) {
                    return null;
                } else {
                    return array(
                        CSTATUS_CONNECTED,
                        CSTATUS_DEBT_COLLECTION,
                    );
                }
            } else {
                return $default;
            }
        } else {
            $all_statuses = self::array_column($CSTATUSES, 'alias');

            $normal = array();
            $negated = array();
            foreach ($value as $status) {
                if (strpos($status, '!') === 0) {
                    $negated[] = substr($status, 1);
                } else {
                    $normal[] = $status;
                }
            }

            if (empty($normal)) {
                $statuses = array_diff($all_statuses, $negated);
            } else {
                $statuses = array_diff(array_intersect($all_statuses, $normal), $negated);
            }
            if (empty($statuses)) {
                return array(
                    CSTATUS_CONNECTED,
                    CSTATUS_DEBT_COLLECTION,
                );
            }

            return array_keys($statuses);
        }
    }

    public static function removeInsecureHtml($html)
    {
        static $hm_purifier;
        if (!isset($hm_purifier)) {
            $hm_config = HTMLPurifier_Config::createDefault();
            $hm_config->set('URI.AllowedSchemes', array(
                'http' => true,
                'https' => true,
                'mailto' => true,
                'ftp' => true,
                'nntp' => true,
                'news' => true,
                'tel' => true,
                'data' => true,
            ));
            $hm_config->set('Attr.ForbiddenClasses', array(
                'lms-ui-datatable' => true,
            ));
            $hm_config->set('CSS.MaxImgLength', null);
            $hm_config->set('HTML.MaxImgLength', null);
            if (defined('CACHE_DIR')) {
                $hm_config->set('Cache.SerializerPath', CACHE_DIR . DIRECTORY_SEPARATOR . 'htmlpurifier');
            }
            $hm_purifier = new HTMLPurifier($hm_config);
        }

        return $hm_purifier->purify($html);
    }

    public static function getGusRegonData($type, $id)
    {
        global $LMS;

        static $gus = null;

        if (!isset($gus)) {
            $apikey = ConfigHelper::getConfig('phpui.gusapi_key', 'abcde12345abcde12345');
            if ($apikey == 'abcde12345abcde12345') {
                $env = 'dev';
            } else {
                $env = 'prod';
            }

            $gus = new GusApi(
                $apikey, // your user key / twój klucz użytkownika
                $env
            );

            try {
                if (!$gus->login()) {
                    throw new Exception(trans('Bad REGON API user key'));
                }
            } catch (InvalidUserKeyException $e) {
                return self::GUS_REGON_API_RESULT_BAD_KEY;
            } catch (\GusApi\Exception\NotFoundException $e) {
                return self::GUS_REGON_API_RESULT_NO_DATA;
            } catch (Exception $e) {
                return self::GUS_REGON_API_RESULT_UNKNOWN_ERROR;
            }
        }

        try {
            switch ($type) {
                case self::GUS_REGON_API_SEARCH_TYPE_TEN:
                    $gusReports = $gus->getByNip(preg_replace('/[^a-z0-9]/i', '', $id));
                    break;
                case self::GUS_REGON_API_SEARCH_TYPE_RBE:
                case self::GUS_REGON_API_SEARCH_TYPE_REGON:
                    $gusReports = $gus->getByRegon($id);
                    break;
                default:
                    throw new Exception(trans('Unsupported resource type'));
            }

            $results = array();

            foreach ($gusReports as $gusReport) {
                $personType = $gusReport->getType();

                if ($personType == \GusApi\SearchReport::TYPE_JURIDICAL_PERSON) {
                    $fullReport = $gus->getFullReport(
                        $gusReport,
                        ReportTypes::REPORT_ORGANIZATION
                    );

                    $report = reset($fullReport);

                    $details = array(
                        'lastname' => $report['praw_nazwa'],
                        'name' => '',
                        'rbename' => $report['praw_organRejestrowy_Nazwa'],
                        'rbe' => $report['praw_numerWRejestrzeEwidencji'],
                        'regon' => array_key_exists('praw_regon9', $report)
                            ? $report['praw_regon9']
                            : $report['praw_regon14'],
                        'ten' => $report['praw_nip'],
                        'addresses' => array(),
                    );

                    $addresses = array();

                    $terc = $report['praw_adSiedzWojewodztwo_Symbol']
                        . $report['praw_adSiedzPowiat_Symbol']
                        . $report['praw_adSiedzGmina_Symbol'];
                    $simc = $report['praw_adSiedzMiejscowosc_Symbol'];
                    $ulic = $report['praw_adSiedzUlica_Symbol'];
                    $location = $LMS->TerytToLocation($terc, $simc, $ulic);

                    $addresses[] = array(
                        'location_state_name' => mb_strtolower($report['praw_adSiedzWojewodztwo_Nazwa']),
                        'location_city_name' => $report['praw_adSiedzMiejscowosc_Nazwa'],
                        'location_street_name' => $report['praw_adSiedzUlica_Nazwa'],
                        'location_house' => $report['praw_adSiedzNumerNieruchomosci'],
                        'location_flat' => $report['praw_adSiedzNumerLokalu'],
                        'location_zip' => preg_replace(
                            '/^([0-9]{2})([0-9]{3})$/',
                            '$1-$2',
                            $report['praw_adSiedzKodPocztowy']
                        ),
                        'location_postoffice' => $report['praw_adSiedzMiejscowoscPoczty_Nazwa']
                        == $report['praw_adSiedzMiejscowosc_Nazwa'] ? ''
                            : $report['praw_adSiedzMiejscowoscPoczty_Nazwa'],
                        'location_state' => empty($location) ? 0 : $location['location_state'],
                        'location_city' => empty($location) ? 0 : $location['location_city'],
                        'location_street' => empty($location) ? 0 : $location['location_street'],
                    );

                    $details['addresses'] = $addresses;

                    $results[] = $details;

                    $locals = $gus->getFullReport(
                        $gusReport,
                        ReportTypes::REPORT_ORGANIZATION_LOCALS
                    );
                    if (count($locals) >= 1 && !isset($locals[0]['ErrorCode'])) {
                        foreach ($locals as $local) {
                            if (!empty($local['lokpraw_dataZakonczeniaDzialalnosci']) && strtotime($local['lokpraw_dataZakonczeniaDzialalnosci']) < time()
                                || !empty($local['lokpraw_dataSkresleniaZRegon']) && strtotime($local['lokpraw_dataSkresleniaZRegon']) < time()) {
                                continue;
                            }

                            $details['lastname'] = empty($local['lokpraw_nazwa']) ? $report['praw_nazwa'] : $local['lokpraw_nazwa'];
                            $details['regon'] = empty($local['lokpraw_regon14'])
                                ? (array_key_exists('praw_regon9', $report) ? $report['praw_regon9'] : $report['praw_regon14'])
                                : $local['lokpraw_regon14'];

                            $details['addresses'] = array();
                            $addresses = array();

                            $terc = $local['lokpraw_adSiedzWojewodztwo_Symbol']
                                . $local['lokpraw_adSiedzPowiat_Symbol']
                                . $local['lokpraw_adSiedzGmina_Symbol'];
                            $simc = $local['lokpraw_adSiedzMiejscowosc_Symbol'];
                            $ulic = $local['lokpraw_adSiedzUlica_Symbol'];
                            $location = strlen($terc) ? $LMS->TerytToLocation($terc, $simc, $ulic) : null;

                            $addresses[] = array(
                                'location_state_name' => mb_strtolower($local['lokpraw_adSiedzWojewodztwo_Nazwa']),
                                'location_city_name' => $local['lokpraw_adSiedzMiejscowosc_Nazwa'],
                                'location_street_name' => $local['lokpraw_adSiedzUlica_Nazwa'],
                                'location_house' => $local['lokpraw_adSiedzNumerNieruchomosci'],
                                'location_flat' => $local['lokpraw_adSiedzNumerLokalu'],
                                'location_zip' => preg_replace(
                                    '/^([0-9]{2})([0-9]{3})$/',
                                    '$1-$2',
                                    $local['lokpraw_adSiedzKodPocztowy']
                                ),
                                'location_postoffice' => $local['lokpraw_adSiedzMiejscowoscPoczty_Nazwa']
                                    == $report['praw_adSiedzMiejscowosc_Nazwa'] ? ''
                                        : $local['lokpraw_adSiedzMiejscowoscPoczty_Nazwa'],
                                'location_state' => empty($location) ? 0 : $location['location_state'],
                                'location_city' => empty($location) ? 0 : $location['location_city'],
                                'location_street' => empty($location) ? 0 : $location['location_street'],
                            );

                            $details['addresses'] = $addresses;

                            $results[] = $details;
                        }
                    }
                } elseif ($personType == \GusApi\SearchReport::TYPE_NATURAL_PERSON) {
                    $silo = $gusReport->getSilo();

                    $siloMapper = array(
                        1 => ReportTypes::REPORT_PERSON_CEIDG,
                        2 => ReportTypes::REPORT_PERSON_AGRO,
                        3 => ReportTypes::REPORT_PERSON_OTHER,
                        4 => ReportTypes::REPORT_PERSON_DELETED_BEFORE_20141108,
                    );

                    if (!isset($siloMapper[$silo])) {
                        die;
                    }

                    $fullReport = $gus->getFullReport(
                        $gusReport,
                        $siloMapper[$silo]
                    );

                    $report = reset($fullReport);

                    $details = array(
                        'lastname' => $report['fiz_nazwa'],
                        'name' => '',
                        'rbename' => $report['fizC_RodzajRejestru_Nazwa'] ?? '',
                        'rbe' => $report['fizC_numerWRejestrzeEwidencji'] ?? '',
                        'regon' => array_key_exists('fiz_regon9', $report)
                            ? $report['fiz_regon9']
                            : $report['fiz_regon14'],
                        'addresses' => array(),
                    );

                    $personReports = $gus->getFullReport(
                        $gusReport,
                        \GusApi\ReportTypes::REPORT_PERSON
                    );

                    $personReport = reset($personReports);
                    $details['ten'] = $personReport['fiz_nip'];

                    $addresses = array();

                    $terc = $report['fiz_adSiedzWojewodztwo_Symbol']
                        . $report['fiz_adSiedzPowiat_Symbol']
                        . $report['fiz_adSiedzGmina_Symbol'];
                    $simc = $report['fiz_adSiedzMiejscowosc_Symbol'];
                    $ulic = $report['fiz_adSiedzUlica_Symbol'];
                    $location = strlen($terc) ? $LMS->TerytToLocation($terc, $simc, $ulic) : null;

                    $addresses[] = array(
                        'location_state_name' => mb_strtolower($report['fiz_adSiedzWojewodztwo_Nazwa']),
                        'location_city_name' => $report['fiz_adSiedzMiejscowosc_Nazwa'],
                        'location_street_name' => $report['fiz_adSiedzUlica_Nazwa'],
                        'location_house' => $report['fiz_adSiedzNumerNieruchomosci'],
                        'location_flat' => $report['fiz_adSiedzNumerLokalu'],
                        'location_zip' => preg_replace(
                            '/^([0-9]{2})([0-9]{3})$/',
                            '$1-$2',
                            $report['fiz_adSiedzKodPocztowy']
                        ),
                        'location_postoffice' => $report['fiz_adSiedzMiejscowoscPoczty_Nazwa']
                            == $report['fiz_adSiedzMiejscowosc_Nazwa'] ? ''
                                : $report['fiz_adSiedzMiejscowoscPoczty_Nazwa'],
                        'location_state' => empty($location) ? 0 : $location['location_state'],
                        'location_city' => empty($location) ? 0 : $location['location_city'],
                        'location_street' => empty($location) || empty($location['location_street']) ? 0 : $location['location_street'],
                    );

                    $details['addresses'] = $addresses;

                    $results[] = $details;

                    if ($silo < 4) {
                        $locals = $gus->getFullReport(
                            $gusReport,
                            ReportTypes::REPORT_PERSON_LOCALS
                        );
                        if (count($locals) >= 1 && !isset($locals[0]['ErrorCode'])) {
                            foreach ($locals as $local) {
                                if (!empty($local['lokfiz_dataZakonczeniaDzialalnosci']) && strtotime($local['lokfiz_dataZakonczeniaDzialalnosci']) < time()
                                    || !empty($local['lokfiz_dataSkresleniaZRegon']) && strtotime($local['lokfiz_dataSkresleniaZRegon']) < time()) {
                                    continue;
                                }

                                $details['lastname'] = empty($local['lokfiz_nazwa']) ? $report['fiz_nazwa'] : $local['lokfiz_nazwa'];
                                $details['regon'] = empty($local['lokfiz_regon14'])
                                    ? (array_key_exists('fiz_regon9', $report) ? $report['fiz_regon9'] : $report['fiz_regon14'])
                                    : $local['lokfiz_regon14'];

                                $details['addresses'] = array();
                                $addresses = array();

                                $terc = $local['lokfiz_adSiedzWojewodztwo_Symbol']
                                    . $local['lokfiz_adSiedzPowiat_Symbol']
                                    . $local['lokfiz_adSiedzGmina_Symbol'];
                                $simc = $local['lokfiz_adSiedzMiejscowosc_Symbol'];
                                $ulic = $local['lokfiz_adSiedzUlica_Symbol'];
                                $location = strlen($terc) ? $LMS->TerytToLocation($terc, $simc, $ulic) : null;

                                $addresses[] = array(
                                    'location_state_name' => mb_strtolower($local['lokfiz_adSiedzWojewodztwo_Nazwa']),
                                    'location_city_name' => $local['lokfiz_adSiedzMiejscowosc_Nazwa'],
                                    'location_street_name' => $local['lokfiz_adSiedzUlica_Nazwa'],
                                    'location_house' => $local['lokfiz_adSiedzNumerNieruchomosci'],
                                    'location_flat' => $local['lokfiz_adSiedzNumerLokalu'],
                                    'location_zip' => preg_replace(
                                        '/^([0-9]{2})([0-9]{3})$/',
                                        '$1-$2',
                                        $local['lokfiz_adSiedzKodPocztowy']
                                    ),
                                    'location_postoffice' => $local['lokfiz_adSiedzMiejscowoscPoczty_Nazwa']
                                        == $local['lokfiz_adSiedzMiejscowosc_Nazwa'] ? ''
                                            : $local['lokfiz_adSiedzMiejscowoscPoczty_Nazwa'],
                                    'location_state' => empty($location) ? 0 : $location['location_state'],
                                    'location_city' => empty($location) ? 0 : $location['location_city'],
                                    'location_street' => empty($location) || empty($location['location_street']) ? 0 : $location['location_street'],
                                );

                                $details['addresses'] = $addresses;

                                $results[] = $details;
                            }
                        }
                    }
                }
            }

            return $results;
        } catch (InvalidUserKeyException $e) {
            return self::GUS_REGON_API_RESULT_BAD_KEY;
        } catch (\GusApi\Exception\NotFoundException $e) {
            return self::GUS_REGON_API_RESULT_NO_DATA;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public static function createCallPhoneUrl($phone)
    {
        static $call_phone_url = null;

        if (!isset($call_phone_url)) {
            $call_phone_url = ConfigHelper::getConfig('phpui.call_phone_url', '', true);
        }

        if (empty($call_phone_url)) {
            return null;
        }

        $url = str_replace('%phone', $phone, $call_phone_url);
        return '<a href="' . $url . '"><i class="lms-ui-icon-phone"></i></a>';
    }

    public static function strftime($format, $date)
    {
        return str_replace(
            array(
                '%Y',
                '%m',
                '%d',
                '%e',
                '%u',
                '%a',
                '%A',
                '%w',
                '%b',
                '%B',
                '%y',
                '%H',
                '%I',
                '%M',
                '%S',
                '%T',
                '%F',
                '%D',
                '%s',
                '%Z',
                '%z',
                '%k',
                '%k',
                '%R',
                '%V',
                '%j',
            ),
            explode(
                '|',
                date('Y|m|d|j|N|D|l|w|', $date)
                . trans('<!month-name-short>' . date('M', $date))
                . '|'
                . trans('<!month-name-full>' . date('F', $date))
                . date('|y|H|h|i|s|H:i:s|Y-m-d|m/d/y|U|T|O|G|G|H:i|W|', $date)
                . sprintf('%03d', date('z', $date) + 1)
            ),
            $format
        );
    }

    public static function isPrivateAddress($ip)
    {
        return preg_match('/^(192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.|100\.64\.|100\.68\.)/', $ip) > 0;
    }

    public static function normalizeMac($mac)
    {
        return strtoupper(
            preg_replace(
                '/^([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i',
                '$1:$2:$3:$4:$5:$6',
                preg_replace(
                    '/[^0-9a-f]/i',
                    '',
                    $mac
                )
            )
        );
    }

    public static function smartFormatMoney($value, $currency = null)
    {
        if (is_string($value)) {
            $value = floatval($value);
        }
        if (empty($currency)) {
            $currency = Localisation::getCurrentCurrency();
        }
        return sprintf('%s %s', Localisation::smartFormatNumber($value), $currency);
    }

    public static function convertToGeoportalCoordinates($latitude, $longitude)
    {
        static $proj4 = null;

        if (!isset($proj4)) {
            $proj4 = new Proj4php();
            $projWGS84 = new Proj('EPSG:4326', $proj4); //EPSG:4326 WGS 84
            $projL93 = new Proj('EPSG:2180', $proj4); //EPSG:2154 RGF93 v1 / Lambert-93
        }

        // Create a point.
        $pointWGS84 = new Point($longitude, $latitude, $projWGS84);

        // Transform the point between datums.
        $pointL93 = $proj4->transform($projL93, $pointWGS84);

        return $pointL93->toArray();
    }

    public static function convertToSidusisCoordinates($latitude, $longitude)
    {
        $proj4 = new Proj4php();
        $projWGS84  = new Proj('EPSG:4326', $proj4); //EPSG:4326 WGS 84
        $proj3857    = new Proj('EPSG:3857', $proj4); //EPSG:3857

        // Create a point.
        $pointWGS84 = new Point($longitude, $latitude, $projWGS84);

        // Transform the point between datums.
        $point3857 = $proj4->transform($proj3857, $pointWGS84);

        return $point3857->toArray();
    }

    public static function formatMoney($value, $currency = null)
    {
        if (is_string($value)) {
            $value = floatval($value);
        }
        if (empty($currency)) {
            $currency = Localisation::getCurrentCurrency();
        }
        return sprintf('%s %s', Localisation::formatNumber(round($value, 2)), $currency);
    }

    public static function checkPeselReservationStatus($pesel)
    {
        static $api_url = null;
        static $api_key = null;
        static $api_request_reason = null;
        static $curl = null;

        if (empty($api_url)) {
            $api_url = ConfigHelper::getConfig('pesel.api_url', 'https://rejestr-zastrzezen.obywatel.gov.pl:20443/api/v1/');
            $api_key = ConfigHelper::getConfig('pesel.api_key', '', true);
            $api_request_reason = ConfigHelper::getConfig('pesel.api_request_reason', trans('telecommunication service contract'));

            if (!strlen($api_key)) {
                throw new Exception(trans('Empty PESEL Reservation Registry API secret key!'));
            }
        }

        if (preg_match('/^[0-9]{13}$/', $pesel)) {
            throw new Exception(trans('Incorrect PESEL format!'));
        }

        if (!isset($curl)) {
            if (!function_exists('curl_init')) {
                throw new Exception(trans('Curl extension not loaded!'));
            }
            $curl = curl_init();
        }

        $uuid = Uuid::uuid4();
        $stringified_uuid = $uuid->toString();
        //$stringified_uuid = '7a48c9f2-2b64-4374-ab4c-c3a68c295130';

        $data = array(
            'powodWeryfikacji' => $api_request_reason,
            'PESEL' => $pesel,
            //'PESEL' => '80103193344', // not reserved
            //'PESEL' => '70030158839', // reserved
        );

        curl_setopt($curl, CURLOPT_URL, $api_url . (substr($api_url, -1) == '/' ? '' : '/') . 'status-zastrzezenia/aktualny');
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data, JSON_FORCE_OBJECT));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            array(
                'Accept: application/json',
                'Content-Type: application/json; charset=UTF-8',
                'X-REQUEST-ID: ' . $stringified_uuid,
                'X-API-KEY: ' . $api_key,
            )
        );

        $reply = curl_exec($curl);
        if (curl_error($curl)) {
            throw new Exception('Communication error: ' . curl_error($curl));
        }

        if (empty($reply)) {
            throw new Exception(trans('Unexpected empty reply from PESEL Reservation Registry API server!'));
        }

        $reply = json_decode($reply, true);
        if (empty($reply)) {
            throw new Exception(trans('Malformed reply from PESEL Reservation Registry API server!'));
        }

        $info = curl_getinfo($curl);

        $result = array(
            'uuid' => $stringified_uuid,
            'http_code' => $info['http_code'],
            'errors' => array(),
            'reserved' => null,
        );

        switch ($result['http_code']) {
            case 200:
                switch ($reply['aktualnyStatus']['status']) {
                    case self::PESEL_STATUS_NOT_RESERVED:
                        $result['reserved'] = false;
                        break;
                    case self::PESEL_STATUS_RESERVED:
                        $result['reserved'] = true;
                        break;
                }
                if (isset($reply['aktualnyStatus']['dataICzasPoczatkuWaznosciStatusu'])) {
                    $last_change = strtotime($reply['aktualnyStatus']['dataICzasPoczatkuWaznosciStatusu']);
                } else {
                    $last_change = null;
                }
                $result['last_change'] = $last_change;
                break;
            case 400:
                $result['errors'][] = $reply['komunikat'];
                $result['errors'] = array_merge($result['errors'], self::array_column($reply['bledy'], 'komunikat'));
                break;
            case 401:
            case 403:
            case 429:
            case 500:
            case 503:
                $result['errors'][] = $reply['komunikat'];
                break;
        }

        return $result;
    }

    public static function formatStreetName(array $args)
    {
        static $teryt_street_address_format = null;

        if (!isset($teryt_street_address_format)) {
            $teryt_street_address_format = ConfigHelper::getConfig('phpui.teryt_street_address_format', '%type% %street2% %street1%');
        }

        if (preg_match('/^rynek$/i', $args['type']) &&
            (preg_match('/^rynek/i', $args['name']) || preg_match('/^rynek/i', $args['name2']))) {
            $args['type'] = '';
        } else if ((!isset($args['name2']) || !strlen($args['name2'])) && preg_match('/^[0-9]+(\.|-go)?$/', $args['name'])) {
            $args['type'] = '';
        }
        return trim(preg_replace(
            '/[ ]{2,}/',
            ' ',
            str_replace(
                array(
                    '%type%',
                    '%street1%',
                    '%street2%',
                ),
                array(
                    $args['type'],
                    $args['name'],
                    $args['name2'],
                ),
                $teryt_street_address_format
            )
        ));
    }

    public static function html2pdf(array $params)
    {
        global $layout;

        extract($params);

        if (!isset($title)) {
            $title = '';
        }

        if (!isset($orientation)) {
            $orientation = 'P';
        }

        if (!isset($margins)) {
            $margins = array(5, 10, 5, 10);
        }

        if (!isset($dest)) {
            $dest = 'I';
        }

        if (!isset($copy)) {
            $copy = false;
        }

        if (!isset($md5sum)) {
            $md5sum = '';
        }

        if (!isset($html2pdf_command)) {
            $html2pdf_command = ConfigHelper::getConfig('documents.html2pdf_command', '', true);
        }

        $DB = LMSDB::getInstance();

        if ($dest === true) {
            $dest = 'D';
        } elseif ($dest === false) {
            $dest = 'I';
        }

        if (!strlen($html2pdf_command)) {
            if (isset($margins)) {
                if (!is_array($margins)) {
                    $margins = array(5, 10, 5, 10); /* default */
                }
            }
            $html2pdf = new LMSHTML2PDF($orientation, 'A4', 'en', true, 'UTF-8', $margins);
            /* disable font subsetting to improve performance */
            $html2pdf->pdf->setFontSubsetting(false);

            if (!empty($id)) {
                $info = $DB->GetRow('SELECT di.name, di.description, d.ssn FROM divisions di
				LEFT JOIN documents d ON (d.divisionid = di.id)
				WHERE d.id = ?', array($id));
            }

            $html2pdf->pdf->SetAuthor('LMS Developers');
            $html2pdf->pdf->SetCreator('LMS ' . $layout['lmsv']);
            if (!empty($info)) {
                $html2pdf->pdf->SetAuthor($info['name']);
            }
            if ($subject) {
                $html2pdf->pdf->SetSubject($subject);
            }
            if ($title) {
                $html2pdf->pdf->SetTitle($title);
            }

            $html2pdf->pdf->SetDisplayMode('fullpage', 'SinglePage', 'UseNone');

            /* if tidy extension is loaded we repair html content */
            if (extension_loaded('tidy')) {
                $config = array(
                    'indent' => true,
                    'output-html' => true,
                    'indent-spaces' => 4,
                    'join-styles' => true,
                    'join-classes' => true,
                    'fix-bad-comments' => true,
                    'fix-backslash' => true,
                    'repeated-attributes' => 'keep-last',
                    'drop-proprietary-attribute' => true,
                    'sort-attributes' => 'alpha',
                    'hide-comments' => true,
                    'new-blocklevel-tags' => 'page, page_header, page_footer, barcode',
                    'wrap' => 200
                );

                $tidy = new tidy;
                $content = $tidy->repairString($content, $config, 'utf8');
            }

            $html2pdf->WriteHTML($content);

            if (!empty($copy)) {
                /* add watermark only for contract & annex */
                if (!empty($type) && ($type == DOC_CONTRACT || $type == DOC_ANNEX)) {
                    $html2pdf->AddFont('courier', '', 'courier.php');
                    $html2pdf->AddFont('courier', 'B', 'courierb.php');
                    $html2pdf->pdf->SetTextColor(255, 0, 0);

                    $PageWidth = $html2pdf->pdf->getPageWidth();
                    $PageHeight = $html2pdf->pdf->getPageHeight();
                    $PageCount = $html2pdf->pdf->getNumPages();
                    $txt = trim(preg_replace("/(.)/i", "\${1} ", trans('COPY')));
                    $w = $html2pdf->pdf->getStringWidth($txt, 'courier', 'B', 120);
                    $x = ($PageWidth / 2) - (($w / 2) * sin(45));
                    $y = ($PageHeight / 2) + 50;

                    for ($i = 1; $i <= $PageCount; $i++) {
                        $html2pdf->pdf->setPage($i);
                        $html2pdf->pdf->SetAlpha(0.2);
                        $html2pdf->pdf->SetFont('courier', 'B', 120);
                        $html2pdf->pdf->StartTransform();
                        $html2pdf->pdf->Rotate(45, $x, $y);
                        $html2pdf->pdf->Text($x, $y, $txt);
                        $html2pdf->pdf->StopTransform();
                    }
                    $html2pdf->pdf->SetAlpha(1);
                }
            }

            if (!empty($type) && ($type == DOC_CONTRACT || $type == DOC_ANNEX)) {
                /* set signature additional information */
                $info = array(
                    'Name' => $info['name'],
                    'Location' => $subject,
                    'Reason' => $title,
                    'ContactInfo' => $info['description'],
                );

                /* setup your cert & key file */
                $cert = 'file://' . LIB_DIR . DIRECTORY_SEPARATOR . 'tcpdf' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lms.cert';
                $key = 'file://' . LIB_DIR . DIRECTORY_SEPARATOR . 'tcpdf' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lms.key';

                /* set document digital signature & protection */
                if (file_exists($cert) && file_exists($key)) {
                    $html2pdf->pdf->setSignature($cert, $key, 'lms-documents', '', 1, $info);
                }
            }

            // cache pdf file
            if ($md5sum) {
                $html2pdf->Output(DOC_DIR . DIRECTORY_SEPARATOR . substr($md5sum, 0, 2) . DIRECTORY_SEPARATOR . $md5sum . '.pdf', 'F');
            }

            switch ($dest) {
                case 'D':
                    if (function_exists('mb_convert_encoding')) {
                        $filename = mb_convert_encoding($title, "ISO-8859-2", "UTF-8");
                    } else {
                        $filename = iconv("UTF-8", "ISO-8859-2//TRANSLIT", $title);
                    }
                    $html2pdf->Output($filename . '.pdf', 'D');
                    break;
                case 'S':
                    return $html2pdf->Output('', 'S');
                    break;
                default:
                    $html2pdf->Output();
                    break;
            }
        } else {
            $pipes = null;
            $process = proc_open(
                $html2pdf_command,
                array(
                    0 => array('pipe', 'r'),
                    1 => array('pipe', 'w'),
                    2 => array('pipe', 'w'),
                ),
                $pipes
            );
            if (is_resource($process)) {
                fwrite($pipes[0], $content);
                fclose($pipes[0]);

                $content = stream_get_contents($pipes[1]);
                fclose($pipes[1]);

                $error = stream_get_contents($pipes[2]);
                fclose($pipes[2]);

                $result = proc_close($process);

                if (!$result) {
                    // cache pdf file
                    if ($md5sum) {
                        file_put_contents(
                            DOC_DIR . DIRECTORY_SEPARATOR . substr($md5sum, 0, 2) . DIRECTORY_SEPARATOR . $md5sum . '.pdf',
                            $content
                        );
                    }

                    if (function_exists('mb_convert_encoding')) {
                        $filename = mb_convert_encoding($title, "ISO-8859-2", "UTF-8");
                    } else {
                        $filename = iconv("UTF-8", "ISO-8859-2//TRANSLIT", $title);
                    }

                    switch ($dest) {
                        case 'D':
                            header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
                            //header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
                            header('Pragma: public');
                            header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
                            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
                            // force download dialog
                            header('Content-Type: application/pdf');
                            // use the Content-Disposition header to supply a recommended filename
                            header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
                            header('Content-Transfer-Encoding: binary');

                            echo $content;

                            break;

                        case 'S':
                            return $content;

                        default:
                            header('Content-Type: application/pdf');
                            header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
                            //header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
                            header('Pragma: public');
                            header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
                            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
                            header('Content-Disposition: inline; filename="' . basename($filename) . '"');

                            echo $content;

                            break;
                    }
                }
            }
        }
    }

    public static function mazovia_to_utf8($text)
    {
        static $mazovia_regexp = array(
            '/\x86/', // ą
            '/\x92/', // ł
            '/\x9e/', // ś
            '/\x8d/', // ć
            '/\xa4/', // ń
            '/\xa6/', // ź
            '/\x91/', // ę
            '/\xa2/', // ó
            '/\xa7/', // ż
            '/\x8f/', // Ą
            '/\x9c/', // Ł
            '/\x98/', // Ś
            '/\x95/', // Ć
            '/\xa5/', // Ń
            '/\xa0/', // Ź
            '/\x90/', // Ę
            '/\xa3/', // Ó
            '/\xa1/', // Ż
        );

        static $utf8_codes = array(
            'ą', 'ł', 'ś', 'ć', 'ń', 'ź', 'ę', 'ó', 'ż',
            'Ą', 'Ł', 'Ś', 'Ć', 'Ń', 'Ź', 'Ę', 'Ó', 'Ż',
        );

        return preg_replace($mazovia_regexp, $utf8_codes, $text);
    }

    private static function check_string_national_unicode_characters($text)
    {
        static $utf8_letters = array(
            'ą', 'ł', 'ś', 'ć', 'ń', 'ź', 'ę', 'ó', 'ż',
            'Ą', 'Ł', 'Ś', 'Ć', 'Ń', 'Ź', 'Ę', 'Ó', 'Ż',
        );
        static $utf8_letter_codes = array();
        if (empty($utf8_letter_codes)) {
            foreach ($utf8_letters as $utf8_letter) {
                $utf8_letter_codes[bin2hex($utf8_letter)] = $utf8_letter;
            }
        }
        for ($i = 0; $i < mb_strlen($text); $i++) {
            $decoded_character = bin2hex(mb_substr($text, $i, 1));
            if (strlen($decoded_character) > 2 && !isset($utf8_letter_codes[$decoded_character])) {
                return false;
            }
        }

        return true;
    }

    public static function str_utf8($text)
    {
        foreach (array('WINDOWS-1250', 'ISO-8859-2',) as $encoding) {
            $decoded_text = @iconv($encoding, 'UTF-8', $text);
            if ($decoded_text !== false && self::check_string_national_unicode_characters($decoded_text)) {
                return $decoded_text;
            }
        }

        $decoded_text = self::mazovia_to_utf8($text);
        if ($decoded_text !== false && self::check_string_national_unicode_characters($decoded_text)) {
            return $decoded_text;
        }

        return false;
    }
}
