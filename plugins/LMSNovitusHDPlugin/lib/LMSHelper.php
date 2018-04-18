<?php

namespace plugins\LMSNovitusHDPlugin\lib;


/**
 * Class LMSHelper
 * @package plugins\LMSNovitusHDPlugin\lib
 */
class LMSHelper
{

	/**
	 * @param $datefrom
	 * @param $dateto
	 * @param int $ctype
	 * @param int|null $customer_id
	 * @param null $division
	 * @param null $numberplan
	 * @param null $group
	 * @param null $groupexclude
	 * @param true $onlyNotFiscalized
	 * @return mixed
	 */
	public static function getInvoices($datefrom, $dateto, $ctype = -1, int $customer_id = null, $division = false, $group = false, $groupexclude = false, $onlyNotFiscalized = true){
		global $DB;

		return $DB->GetCol('SELECT id FROM documents d
				WHERE cdate >= ? AND cdate <= ? AND (type = ? OR type = ?) AND d.cancelled = 0'
			.($ctype !=  -1 ? ' AND d.customerid IN (SELECT id FROM customers WHERE type = ' . intval($ctype) .')' : '')
			.(($division) ? ' AND d.divisionid = ' . intval($division) : '')
			.(($customer_id) ? ' AND d.customerid = '.intval($customer_id) : '')
			.(($group) ?
				' AND '.(($groupexclude) ? 'NOT' : '').'
					EXISTS (SELECT 1 FROM customerassignments a
					WHERE a.customergroupid = '.intval($group).'
						AND a.customerid = d.customerid)' : '')
			.' AND NOT EXISTS (
					SELECT 1 FROM customerassignments a
					JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
					WHERE e.userid = lms_current_user() AND a.customerid = d.customerid)'

			.($onlyNotFiscalized ? 'AND NOT EXISTS (SELECT 1 FROM novitus_fiscalized_invoices ni WHERE ni.doc_id = d.id)' : '')

			.' ORDER BY CEIL(cdate/86400), id',
			array($datefrom, $dateto, DOC_INVOICE, DOC_CNOTE));

	}

	/**
	 * @param $id
	 * @return mixed
	 */
	public static function isInvoiceFiscalized($id){
		global $DB;

		return $DB->Execute('SELECT 1 FROM novitus_fiscalized_invoices WHERE doc_id = ?', [(int)$id]);
	}

}