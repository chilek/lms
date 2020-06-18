<?php

/*
 *  LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

class LMSHtmlTransferForm extends LMSHtmlDocument
{
    const VALUE_BALANCE = 1;
    const VALUE_ASSIGNMENTS = 2;
    const VALUE_CUSTOM = 3;

    private $value_property;

    public function __construct($value_property)
    {
        $this->value_property = $value_property;
    }

    protected function form_draw($SHIFT)
    {
        $posx = 60 + $SHIFT;
        $this->contents .= '<div style="position: absolute; top: ' . $posx . 'px; left: 10px"><img src="img/transferform.png" border="0" alt="wpłata gotówkowa"></div>';
        $posx = 63 + $SHIFT;
        $this->contents .= '<span style="position: absolute; top: ' . $posx . 'px; left: 62px; font-family: Arial, Helvetica;color: red; font-size: 6pt;">nazwa odbiorcy</span>';
        $posx = 96 + $SHIFT;
        $this->contents .= '<span style="position: absolute; top: ' . $posx . 'px; left: 62px; font-family: Arial, Helvetica; color: red; font-size: 6pt;">nazwa odbiorcy cd.</span>';
        $posx = 131 + $SHIFT;
        $this->contents .= '<span style="position: absolute; top: ' . $posx . 'px; left: 62px; font-family: Arial, Helvetica;color: red; font-size: 6pt;">l.k.</span>';
        $posx = 131 + $SHIFT;
        $this->contents .= '<span style="position: absolute; top: ' . $posx . 'px; left: 102px; font-family: Arial, Helvetica; color: red; font-size: 6pt;">nr rachunku odbiorcy</span>';
        $posx = 163 + $SHIFT;
        $this->contents .= '<span style="position: absolute; top: ' . $posx . 'px; left: 352px; font-family: Arial, Helvetica; color: red; font-size: 6pt;">kwota</span>';
        $posx = 194 + $SHIFT;
        $this->contents .= '<span style="position: absolute; top: ' . $posx . 'px; left: 72px; font-family: Arial, Helvetica; color: red; font-size: 6pt;">kwota słownie</span>';
        $posx = 222 + $SHIFT;
        $this->contents .= '<span style="position: absolute; top: ' . $posx . 'px; left: 72px; font-family: Arial, Helvetica; color: red; font-size: 6pt;">nazwa zleceniodawcy</span>';
        $posx = 253 + $SHIFT;
        $this->contents .= '<span style="position: absolute; top: ' . $posx . 'px; left: 72px; font-family: Arial, Helvetica; color: red; font-size: 6pt;">nazwa zleceniodawcy cd.</span>';
        $posx = 284 + $SHIFT;
        $this->contents .= '<span style="position: absolute; top: ' . $posx . 'px; left: 72px; font-family: Arial, Helvetica; color: red; font-size: 6pt;">tytułem</span>';
        $posx = 317 + $SHIFT;
        $this->contents .= '<span style="position: absolute; top: ' . $posx . 'px; left: 72px; font-family: Arial, Helvetica; color: red; font-size: 6pt;">tytułem cd.</span>';
        $posx = 395 + $SHIFT;
        $this->contents .= '<span style="position: absolute; top: ' . $posx . 'px; left: 337px; font-family: Arial, Helvetica; color: red; font-size: 6pt;">Opłata</span>';
        $posx = 425 + $SHIFT;
        $this->contents .= '<span style="position: absolute; top: ' . $posx . 'px; left: 337px; font-family: Arial, Helvetica; color: red; font-size: 6pt;">Podpis</span>';
    }

    protected function form_fill($SHIFT)
    {
        // waluta:
        $posx = 174 + $SHIFT;
        for ($i = 0, $len = min(mb_strlen($this->data['CURR']), 27); $i < $len; $i++) {
            $posy = 272 + $i * 19;
            $this->contents .= '<span style="position: absolute; top: ' . $posx . 'px; '
                . 'left: ' . $posy . 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'
                . $this->data['CURR'][$i] . '</span>';
        }

        // nazwa beneficjenta:
        if (mb_strlen($this->data['ISP1_DO']) > 27) { // jeżeli nazwa 27 znaki _nie_ wpisujemy w kratki
            $posx = 75 + $SHIFT;
            $this->contents .= '<span style="position: absolute; top: ' . $posx . 'px; '
                . 'left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'
                . mb_substr($this->data['ISP1_DO'], 0, 50) . '</span>';
        } else {
            $posx = 75 + $SHIFT;
            for ($i = 0; $i < 27; $i++) {
                $posy = 62 + $i * 19;
                $this->contents .= '<span style="position: absolute; top: ' . $posx . 'px; '
                    . 'left: ' . $posy . 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'
                    . mb_substr($this->data['ISP1_DO'], $i, 1) . '</span>';
            }
        }

        if (mb_strlen($this->data['ISP2_DO']) > 27) { // jeżeli nazwa 27 znaki _nie_ wpisujemy w kratki
            $posx = 109 + $SHIFT;
            $this->contents .= '<span style="position: absolute; top: ' . $posx . 'px; '
                . 'left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'
                . mb_substr($this->data['ISP2_DO'], 0, 50) . '</span>';
        } else {
            $posx = 109 + $SHIFT;
            for ($i = 0; $i < 27; $i++) {
                $posy = 62 + $i * 19;
                $this->contents .= '<span style="position: absolute; top: ' . $posx . 'px; '
                    . 'left: ' . $posy . 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'
                    . mb_substr($this->data['ISP2_DO'], $i, 1) . '</span>';
            }
        }

        // numer konta beneficjenta:
        $posx = 141 + $SHIFT;
        for ($i = 0, $len = min(mb_strlen($this->data['KONTO_DO']), 26); $i < $len; $i++) {
            $posy = 62 + $i * 19;
            $this->contents .= '<span style="position: absolute; top: ' . $posx . 'px; '
                . 'left: ' . $posy . 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'
                . $this->data['KONTO_DO'][$i] . '</span>';
        }

        // kwota cyfrowo:
        $posx = 174 + $SHIFT;
        $KWOTA_SL = sprintf("%0'12.2f", $this->data['KWOTA_NR']);
        $KWOTA_SL = str_replace('.', ',', $KWOTA_SL);
        for ($i = 0, $len = min(mb_strlen($KWOTA_SL), 12); $i < $len; $i++) {
            $posy = 347 + $i * 19;
            $this->contents .= '<span style="position: absolute; top: ' . $posx . 'px; '
                . 'left: ' . $posy . 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'
                . mb_substr($KWOTA_SL, $i, 1) . '</span>';
        }

        // kwota słownie:
        $posx = 205 + $SHIFT;
        $this->contents .= '<span style="position: absolute; top: ' . $posx . 'px; '
            . 'left: 62px; font-family: Courier, Arial, Helvetica; font-size: 8pt; font-weight: bold;">'
            . $this->data['KWOTA_X'] . ' gr</span>';

        // dane płatnika:
        if (mb_strlen($this->data['USER_OD']) > 27) { // jeżeli nazwa 27 znaki _nie_ wpisujemy w kratki
            $posx = 235 + $SHIFT;
            $this->contents .= '<span style="position: absolute; top: ' . $posx . 'px; '
                . 'left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'
                . mb_substr($this->data['USER_OD'], 0, 50) . '</span>';
        } else {               // jeżeli nazwa+adres zmieszczą się w kratkach to wpisujemy w kratkach
            $posx = 235 + $SHIFT;
            for ($i = 0, $len = min(mb_strlen($this->data['USER_OD']), 27); $i < $len; $i++) {
                $posy = 62 + $i * 19;
                $this->contents .= '<span style="position: absolute; top: ' . $posx . 'px; '
                    . 'left: ' . $posy . 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'
                    . mb_substr($this->data['USER_OD'], $i, 1) . '</span>';
            }
        }

        if (mb_strlen($this->data['USER_ADDR']) > 27) { // jeżeli adres jest dłuższy niz 27 znaków _nie_ wpisujemy w kratki
            $posx = 265 + $SHIFT;
            $this->contents .= '<span style="position: absolute; top: ' . $posx . 'px; '
                . 'left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'
                . mb_substr($this->data['USER_ADDR'], 0, 50) . '</span>';
        } else {               // jeżeli nazwa+adres zmieszczą się w kratkach to wpisujemy w kratkach
            $posx = 265 + $SHIFT;
            for ($i = 0, $len = min(mb_strlen($this->data['USER_ADDR']), 27); $i < $len; $i++) {
                $posy = 62 + $i * 19;
                $this->contents .= '<span style="position: absolute; top: ' . $posx . 'px; '
                    . 'left: ' . $posy . 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'
                    . mb_substr($this->data['USER_ADDR'], $i, 1) . '</span>';
            }
        }

        // tytułem:
        $posx = 298 + $SHIFT;
        for ($i = 0, $len = min(mb_strlen($this->data['USER_TY']), 27); $i < $len; $i++) {
            $posy = 62 + $i * 19;
            $this->contents .= '<span style="position: absolute; top: ' . $posx . 'px; left: ' . $posy . 'px; '
                . 'font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'
                . mb_substr($this->data['USER_TY'], $i, 1) . '</span>';
        }

        $posx = 329 + $SHIFT;   // wolna linijka
        $this->contents .= '<span style="position: absolute; top: ' . $posx . 'px; '
            . 'left: 62px; height: 15px; background-color: white; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'
            . '<img src="data:image/png;base64,' . $this->data['barcode_image'] . '"></span>';
    }

    private function GetData($data)
    {
        global $LMS;

        $DB = LMSDB::getInstance();

        $this->data['customer'] = $customer = $LMS->GetCustomer($data['customerid']);
        $this->data['division'] = $division = $DB->GetRow('SELECT account, name, address, zip, city
            FROM vdivisions
            WHERE id = ?', array($customer['divisionid']));

        //  NRB 26 cyfr: 2 kontrolne, 8 nr banku, 16 nr konta
        $this->data['KONTO_DO'] = $KONTO_DO = bankaccount($customer['id'], $division['account']);

        if ($division) {
            list ($division['name']) = explode("\n", $division['name']);
            $this->data['ISP1_DO'] = $ISP1_DO = $division['name'];
            $this->data['ISP2_DO'] = $ISP2_DO = trim($division['zip'] . ' ' . $division['city'] . ', ' . $division['address']);
        } else {
            $line_1 = ConfigHelper::getConfig('finances.line_1');
            if (!empty($line_1)) {
                $this->dats['ISP1_DO'] = $ISP1_DO = $line_1;
            }
            $line_2 = ConfigHelper::getConfig('finances.line_2');
            if (!empty($line_2)) {
                $this->data['ISP2_DO'] = $ISP2_DO = $line_2;
            }
        }

        $this->data['USER_T1'] = $USER_T1 =
            ConfigHelper::getConfig('finances.pay_title', 'Abonament - ID:%CID% %LongCID%');
        $this->data['CURR'] = $CURR = 'PLN';

        $SHORT_TO_WORDS = ConfigHelper::checkConfig('phpui.to_words_short_version');

        $Before = array ("%CID%", "%LongCID%");
        $After = array ($customer['id'], sprintf('%04d', $customer['id']));
        $this->data['USER_TY'] = $USER_TY = str_replace($Before, $After, $USER_T1);

        switch ($this->value_property) {
            case self::VALUE_BALANCE:
                $KWOTA = trim($customer['balance'] * -1);
                break;
            case self::VALUE_ASSIGNMENTS:
                $KWOTA = $LMS->GetCustomerAssignmentValue($customer['id']);
                break;
            case self::VALUE_CUSTOM:
                $KWOTA = isset($data['value']) ? $data['value'] : 0;
                break;
            default:
                $KWOTA = 0;
        }

        $this->data['USER_OD'] = $USER_OD = trim($customer['customername']);
        $this->data['USER_ADDR'] = $USER_ADDR = trim($customer['zip'] . ' ' . $customer['city'] . ', ' . $customer['address']);
        $this->data['KWOTA_NR'] = $KWOTA_NR = str_replace(',', '.', $KWOTA);  // na wszelki wypadek
        $this->data['KWOTA_GR'] = $KWOTA_GR = sprintf('%02d', round(($KWOTA_NR - floor($KWOTA_NR)) * 100));

        if ($SHORT_TO_WORDS) {
            $this->data['KWOTA_ZL'] = $KWOTA_ZL = to_words(floor($KWOTA_NR), 0, '', 1);
            $this->data['KWOTA_X'] = $KWOTA_X = $KWOTA_ZL .' '. $KWOTA_GR. '/100';
        } else {
            $this->data['KWOTA_ZL'] = $KWOTA_ZL = floor($KWOTA_NR);
            $this->data['KWOTA_X'] = $KWOTA_X = moneyf_in_words($KWOTA_ZL + ($KWOTA_GR / 100));
        }
        if (mb_strlen($KWOTA_X) > 60) {
            $this->data['KWOTA_ZL'] = $KWOTA_ZL = to_words(floor($KWOTA_NR), 0, '', 1);
            $this->data['KWOTA_X'] = $KWOTA_X = $KWOTA_ZL .' '. $KWOTA_GR. '/100';
        }

        $barcode = new \Com\Tecnick\Barcode\Barcode();
        $bobj = $barcode->getBarcodeObj('C128', iconv('UTF-8', 'ASCII//TRANSLIT', $USER_TY), -1, -15, 'black');
        $this->data['barcode_image'] = base64_encode($bobj->getPngData());
    }

    public function Draw($data)
    {
        parent::Draw($data);

        $this->GetData($data);

        $this->form_draw(0);
        $this->form_fill(0);
        $this->form_draw(394);
        $this->form_fill(394);

        return $this->contents;
    }
}
