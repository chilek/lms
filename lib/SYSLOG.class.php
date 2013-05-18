<?php

define('SYSLOG_RES_USER', 1);
define('SYSLOG_RES_ASSIGN', 2);
define('SYSLOG_RES_LIAB', 3);
define('SYSLOG_RES_NODEASSIGN', 4);
define('SYSLOG_RES_NODE', 5);
define('SYSLOG_RES_MAC', 6);
define('SYSLOG_RES_CUST', 7);
define('SYSLOG_RES_CUSTCONTACT', 8);
define('SYSLOG_RES_IMCONTACT', 9);
define('SYSLOG_RES_CUSTGROUP', 10);
define('SYSLOG_RES_CUSTASSIGN', 11);
define('SYSLOG_RES_TARIFF', 12);
define('SYSLOG_RES_NODEGROUP', 13);
define('SYSLOG_RES_NODEGROUPASSIGN', 14);
define('SYSLOG_RES_TAX', 15);
define('SYSLOG_RES_NUMPLAN', 16);
define('SYSLOG_RES_NUMPLANASSIGN', 17);
define('SYSLOG_RES_DIV', 18);
define('SYSLOG_RES_COUNTRY', 19);
define('SYSLOG_RES_STATE', 20);
define('SYSLOG_RES_ZIP', 21);
define('SYSLOG_RES_HOST', 22);
define('SYSLOG_RES_DAEMONINST', 23);
define('SYSLOG_RES_DAEMONCONF', 24);
define('SYSLOG_RES_CASHSOURCE', 25);
define('SYSLOG_RES_UICONF', 26);
define('SYSLOG_RES_PROMO', 27);
define('SYSLOG_RES_PROMOSCHEMA', 28);
define('SYSLOG_RES_PROMOASSIGN', 29);
define('SYSLOG_RES_EXCLGROUP', 30);
define('SYSLOG_RES_DBBACKUP', 31);
define('SYSLOG_RES_PAYMENT', 32);
define('SYSLOG_RES_CASHIMPORT', 33);
define('SYSLOG_RES_SOURCEFILE', 34);
define('SYSLOG_RES_CASH', 35);
define('SYSLOG_RES_DOC', 36);
define('SYSLOG_RES_INVOICECONT', 37);
define('SYSLOG_RES_RECEIPTCONT', 38);
define('SYSLOG_RES_DNOTECONT', 39);
define('SYSLOG_RES_CASHREG', 40);
define('SYSLOG_RES_CASHRIGHT', 41);
define('SYSLOG_RES_CASHREGHIST', 42);
define('SYSLOG_RES_NETWORK', 43);
define('SYSLOG_RES_NETDEV', 44);
define('SYSLOG_RES_NETLINK', 45);
define('SYSLOG_RES_MGMTURL', 46);
define('SYSLOG_RES_TMPL', 47);

$SYSLOG_RESOURCES = array(
	SYSLOG_RES_USER => trans('user<!syslog>'),
	SYSLOG_RES_ASSIGN => trans('assignment<!syslog>'),
	SYSLOG_RES_LIAB => trans('liability<!syslog>'),
	SYSLOG_RES_NODEASSIGN => trans('node assignment<!syslog>'),
	SYSLOG_RES_NODE => trans('node<!syslog>'),
	SYSLOG_RES_MAC => trans('mac<!syslog>'),
	SYSLOG_RES_CUST => trans('customer<!syslog>'),
	SYSLOG_RES_CUSTCONTACT => trans('customer contact<!syslog>'),
	SYSLOG_RES_IMCONTACT => trans('IM contact<!syslog>'),
	SYSLOG_RES_CUSTGROUP => trans('customer group<!syslog>'),
	SYSLOG_RES_CUSTASSIGN => trans('customer assignment<!syslog>'),
	SYSLOG_RES_TARIFF => trans('tariff<!syslog>'),
	SYSLOG_RES_NODEGROUP => trans('node group<!syslog>'),
	SYSLOG_RES_NODEGROUPASSIGN => trans('node group assignment<!syslog>'),
	SYSLOG_RES_TAX => trans('tax rate<!syslog>'),
	SYSLOG_RES_NUMPLAN => trans('number plan<!syslog>'),
	SYSLOG_RES_NUMPLANASSIGN => trans('number plan assignment<!syslog>'),
	SYSLOG_RES_DIV => trans('division<!syslog>'),
	SYSLOG_RES_COUNTRY => trans('country<!syslog>'),
	SYSLOG_RES_STATE => trans('state<!syslog>'),
	SYSLOG_RES_ZIP => trans('zip code<!syslog>'),
	SYSLOG_RES_HOST => trans('host<!syslog>'),
	SYSLOG_RES_DAEMONINST => trans('daemon instance<!syslog>'),
	SYSLOG_RES_DAEMONCONF => trans('daemon instance setting<!syslog>'),
	SYSLOG_RES_CASHSOURCE => trans('cash import source<!syslog>'),
	SYSLOG_RES_UICONF => trans('configuration setting<!syslog>'),
	SYSLOG_RES_PROMO => trans('promotion<!syslog>'),
	SYSLOG_RES_PROMOSCHEMA => trans('promotion schema<!syslog>'),
	SYSLOG_RES_PROMOASSIGN => trans('promotion schema assignment<!syslog>'),
	SYSLOG_RES_EXCLGROUP => trans('customer group exclusion<!syslog>'),
	SYSLOG_RES_DBBACKUP => trans('database backup<!syslog>'),
	SYSLOG_RES_PAYMENT => trans('payment<!syslog>'),
	SYSLOG_RES_CASHIMPORT => trans('imported financial operation<!syslog>'),
	SYSLOG_RES_SOURCEFILE => trans('imported file with financial operations<!syslog>'),
	SYSLOG_RES_CASH => trans('financial operation<!syslog>'),
	SYSLOG_RES_DOC => trans('document<!syslog>'),
	SYSLOG_RES_INVOICECONT => trans('invoice contents<!syslog>'),
	SYSLOG_RES_RECEIPTCONT => trans('receipt contents<!syslog>'),
	SYSLOG_RES_DNOTECONT => trans('debit note contents<!syslog>'),
	SYSLOG_RES_CASHREG => trans('cash registry<!syslog>'),
	SYSLOG_RES_CASHRIGHT => trans('cash registry rights<!syslog>'),
	SYSLOG_RES_CASHREGHIST => trans('cash registry history<!syslog>'),
	SYSLOG_RES_NETWORK => trans('network<!syslog>'),
	SYSLOG_RES_NETDEV => trans('network device<!syslog>'),
	SYSLOG_RES_NETLINK => trans('network link<!syslog>'),
	SYSLOG_RES_MGMTURL => trans('management url<!syslog>'),
	SYSLOG_RES_TMPL => trans('template<!syslog>'),
);

$SYSLOG_RESOURCE_KEYS = array(
	SYSLOG_RES_USER => 'userid',
	SYSLOG_RES_ASSIGN => 'assignmentid',
	SYSLOG_RES_LIAB => 'liabilityid',
	SYSLOG_RES_NODEASSIGN => 'nodeassignmentid',
	SYSLOG_RES_NODE => 'nodeid',
	SYSLOG_RES_MAC => 'macid',
	SYSLOG_RES_CUST => 'customerid',
	SYSLOG_RES_CUSTCONTACT => 'customercontactid',
	SYSLOG_RES_IMCONTACT => 'imessengerid',
	SYSLOG_RES_CUSTGROUP => 'customergroupid',
	SYSLOG_RES_CUSTASSIGN => 'customerassignmentid',
	SYSLOG_RES_TARIFF => 'tariffid',
	SYSLOG_RES_NODEGROUP => 'nodegroupid',
	SYSLOG_RES_NODEGROUPASSIGN => 'nodegroupassignmentid',
	SYSLOG_RES_TAX => 'taxrateid',
	SYSLOG_RES_NUMPLAN => 'numberplanid',
	SYSLOG_RES_NUMPLANASSIGN => 'numberplanassignmentid',
	SYSLOG_RES_DIV => 'divisionid',
	SYSLOG_RES_COUNTRY => 'countryid',
	SYSLOG_RES_STATE => 'stateid',
	SYSLOG_RES_ZIP => 'zipcodeid',
	SYSLOG_RES_HOST => 'hostid',
	SYSLOG_RES_DAEMONINST => 'daemoninstanceid',
	SYSLOG_RES_DAEMONCONF => 'daemonconfigid',
	SYSLOG_RES_CASHSOURCE => 'cashsourceid',
	SYSLOG_RES_UICONF => 'uiconfigid',
	SYSLOG_RES_PROMO => 'promotionid',
	SYSLOG_RES_PROMOSCHEMA => 'promotionschemaid',
	SYSLOG_RES_PROMOASSIGN => 'promotionassignmentid',
	SYSLOG_RES_EXCLGROUP => 'excludedgroupid',
	SYSLOG_RES_DBBACKUP => null,
	SYSLOG_RES_PAYMENT => 'paymentid',
	SYSLOG_RES_CASHIMPORT => 'importid',
	SYSLOG_RES_SOURCEFILE => 'sourcefileid',
	SYSLOG_RES_CASH => 'cashid',
	SYSLOG_RES_DOC => 'documentid',
	SYSLOG_RES_INVOICECONT => null,
	SYSLOG_RES_RECEIPTCONT => null,
	SYSLOG_RES_DNOTECONT => 'debitnotecontentid',
	SYSLOG_RES_CASHREG => 'cashregistryid',
	SYSLOG_RES_CASHRIGHT => 'cashrightid',
	SYSLOG_RES_CASHREGHIST => 'cashreghistoryid',
	SYSLOG_RES_NETWORK => 'networkid',
	SYSLOG_RES_NETDEV => 'networkdeviceid',
	SYSLOG_RES_NETLINK => 'networklinkid',
	SYSLOG_RES_MGMTURL => 'managementurlid',
	SYSLOG_RES_TMPL => 'templateid',
);

?>
