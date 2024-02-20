<?php

$minibilling_groups = array(
/*
    'ME' => 'międzystrefowe',
    'KOM' => 'sieci komórkowe',
    'ALRM' => 'alarmowe',
    'SPL4' => 'infolinia 0-800...',
    'ASI1' => 'lokalne',
    'ASI2' => 'pozostałe',
    'IN' => 'teleinformatyczne',
    'ASI3' => 'pozostałe',
    'ASI4' => 'pozostałe',
    'FPH' => 'lokalne',
    'PRZ1' => 'pozostałe',
    'PRZ2' => 'pozostałe',
    'LOK' => 'lokalne',
    'NGN1' => 'międzynarodowe',
    'NGN2' => 'międzynarodowe',
    'NGN3' => 'międzynarodowe',
    'NGN4' => 'międzynarodowe',
    'PJ0' => '0-30.../0-40.../0-70...',
    'PJ1' => '0-30.../0-40.../0-70...',
    'PJ2' => '0-30.../0-40.../0-70...',
    'PJ3' => '0-30.../0-40.../0-70...',
    'PJ4' => '0-30.../0-40.../0-70...',
    'PJ5' => '0-30.../0-40.../0-70...',
    'PJ7' => '0-30.../0-40.../0-70...',
    'PP1' => '0-30.../0-40.../0-70...',
    'PP2' => '0-30.../0-40.../0-70...',
    'PP3' => '0-30.../0-40.../0-70...',
    'PP4' => '0-30.../0-40.../0-70...',
    'PP5' => '0-30.../0-40.../0-70...',
    'PP6' => '0-30.../0-40.../0-70...',
    'PP7' => '0-30.../0-40.../0-70...',
    'PP8' => '0-30.../0-40.../0-70...',
    'PP9' => '0-30.../0-40.../0-70...',
    'SPL0' => 'infolinia 0-800...',
    'SPL1' => 'infolinia 0-800...',
    'SPL2' => 'infolinia 0-800...',
    'SPL3' => 'infolinia 0-800...',
    'Z1' => 'międzynarodowe',
    'Z2' => 'międzynarodowe',
    'Z3' => 'międzynarodowe',
    'Z4' => 'międzynarodowe',
    'Z5' => 'międzynarodowe',
    'ZK1' => 'międzynarodowe',
    'ZK2' => 'międzynarodowe',
    'ZK3' => 'międzynarodowe',
    'ZK4' => 'międzynarodowe',
    'ZK5' => 'międzynarodowe',
    'ASI5' => 'pozostałe',
    'ASI' => 'lokalne',
    'BN1' => 'pozostałe',
    'BN2' => 'pozostałe',
    'PP71' => '0-30.../0-40.../0-70...',
    'PP72' => '0-30.../0-40.../0-70...',
    'PP73' => '0-30.../0-40.../0-70...',
    'PP74' => '0-30.../0-40.../0-70...',
    'PP75' => '0-30.../0-40.../0-70...',
    'PP76' => '0-30.../0-40.../0-70...',
    'MIE1' => 'międzystrefowe',
    'MIE' => 'międzystrefowe',
    'PJ6' => '0-30.../0-40.../0-70...',
    'KOM1' => 'sieci komórkowe',
*/
);

$DB = LMSDB::getInstance();

$cdr = $DB->GetAll(
    'SELECT cdr.*
    FROM voip_cdr cdr
    WHERE 1 = 1'
    . (isset($params['frangefrom']) && !empty($params['frangefrom']) ? ' AND call_start_time >= ' . strtotime($params['frangefrom']) : '')
    . (isset($params['frangeto']) && !empty($params['frangeto']) ? ' AND call_start_time < ' . (strtotime($params['frangeto']) + 86400) : '')
    . (isset($params['fstatus']) ? ' AND status = ' . $params['fstatus'] : '')
    . (isset($params['fdirection']) ? ' AND direction = ' . $params['fdirection'] : '')
    . (isset($params['id']) ? ' AND callervoipaccountid IN (' . implode(',', $params['id']) . ')' : '')
    . (isset($params['phone']) ? ' AND caller = ' . $DB->Escape($params['phone']) : '')
    . ' ORDER BY cdr.caller'
);

$minibilling = array();

if (!empty($cdr)) {
    foreach ($cdr as $rec) {
        $phone = $rec['caller'];
        $group = $rec['callee_prefix_group'];
        if (!isset($minibilling[$phone])) {
            $minibilling[$phone] = array();
        }
        if (!empty($minibilling_groups)) {
            $group = $minibilling_groups[$group] ?? '(nieznane: ' . $group . ')';
        }
        if (!isset($minibilling[$phone][$group])) {
            $minibilling[$phone][$group] = array(
                'count' => 0,
                'time' => 0,
                'brutto' => 0,
            );
        }
        $minibilling[$phone][$group]['count']++;
        $minibilling[$phone][$group]['time'] += $rec['billedtime'];
        $minibilling[$phone][$group]['brutto'] += $rec['price'];
    }
}

$SMARTY->assign('datefrom', $params['frangefrom']);
$SMARTY->assign('dateto', $params['frangeto']);
$SMARTY->assign('minibilling', $minibilling);
$SMARTY->display('module:minibilling.html');
