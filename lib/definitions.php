<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2023 LMS Developers
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

// that definitions should be included before LMS.class.php but after Smarty

// customers and contractor type
const CTYPES_PRIVATE = 0,
    CTYPES_COMPANY = 1,
    CTYPES_CONTRACTOR = 2;

$CTYPES = array(
    CTYPES_PRIVATE  => trans('private person'),
    CTYPES_COMPANY  => trans('legal entity'),
    CTYPES_CONTRACTOR   => trans('contractor'),
);

$CTYPE_ALIASES = array(
    CTYPES_PRIVATE => 'private',
    CTYPES_COMPANY => 'company',
    CTYPES_CONTRACTOR => 'contractor',
);

// customer statuses
const CSTATUS_INTERESTED = 1,
    CSTATUS_WAITING = 2,
    CSTATUS_CONNECTED = 3,
    CSTATUS_DISCONNECTED = 4,
    CSTATUS_DEBT_COLLECTION = 5,
    CSTATUS_LAST = CSTATUS_DEBT_COLLECTION;

$CSTATUSES = array(
    CSTATUS_CONNECTED => array(
        'singularlabel' => trans('connected<!singular>'),
        'plurallabel' => trans('connected<!plural>'),
        'summarylabel' => trans('Connected:'),
        'img' => 'customer.gif',
        'alias' => 'connected'
    ),
    CSTATUS_WAITING => array(
        'singularlabel' => trans('waiting'),
        'plurallabel' => trans('waiting'),
        'summarylabel' => trans('Waiting:'),
        'img' => 'wait.gif',
        'alias' => 'awaiting'
    ),
    CSTATUS_INTERESTED => array(
        'singularlabel' => trans('interested<!singular>'),
        'plurallabel' => trans('interested<!plural>'),
        'summarylabel' => trans('Interested:'),
        'img' => 'unk.gif',
        'alias' => 'interested'
    ),
    CSTATUS_DISCONNECTED => array(
        'singularlabel' => trans('disconnected<!singular>'),
        'plurallabel' => trans('disconnected<!plural>'),
        'summarylabel' => trans('Disconnected:<!summary>'),
        'img' => 'node_off.gif',
        'alias' => 'disconnected'
    ),
    CSTATUS_DEBT_COLLECTION => array(
        'singularlabel' => trans('debt collection'),
        'plurallabel' => trans('debt collection'),
        'summarylabel' => trans('Debt Collection:<!summary>'),
        'img' => 'money.gif',
        'alias' => 'debtcollection'
    ),
);

const CUSTOMER_FLAG_RELATED_ENTITY = 1,
    CUSTOMER_FLAG_VAT_PAYER = 2,
    CUSTOMER_FLAG_SUPPLIER = 4;

$CUSTOMERFLAGS = array(
    CUSTOMER_FLAG_RELATED_ENTITY => array(
        'label' => 'related entity',
        'tip' => trans('translates into JPK TP flag'),
        'alias' => 'related-entity',
    ),
    CUSTOMER_FLAG_VAT_PAYER => array(
        'label' => 'VAT payer',
        'tip' => trans('if customer is not VAT payer, then his telecommunication services are reported with JPK EE flag'),
        'alias' => 'vat-payer',
    ),
    CUSTOMER_FLAG_SUPPLIER => array(
        'label' => 'supplier',
        'tip' => trans('check it if customer is supplier for example for warehouse purpose'),
        'alias' => 'supplier',
    ),
);

// customer consents
const CCONSENT_DATE = 1,
    CCONSENT_INVOICENOTICE = 2,
    CCONSENT_MAIL_SERVICE_INFO = 3,
    CCONSENT_MAILING_NOTICE = CCONSENT_MAIL_SERVICE_INFO,
    CCONSENT_MAILINGNOTICE = CCONSENT_MAIL_SERVICE_INFO,
    CCONSENT_EINVOICE = 4,
    CCONSENT_USERPANEL_SMS = 5,
    CCONSENT_USERPANEL_SCAN = 6,
    CCONSENT_TRANSFERFORM = 7,
    CCONSENT_SMS_SERVICE_INFO = 8,
    CCONSENT_SMS_NOTICE = CCONSENT_SMS_SERVICE_INFO,
    CCONSENT_SMSNOTICE = CCONSENT_SMS_SERVICE_INFO,
    CCONSENT_SMS_MARKETING = 9,
    CCONSENT_MAIL_MARKETING = 10,
    CCONSENT_PHONE_BILLING = 11,
    CCONSENT_NONE_PHONE_BILLING = 12,
    CCONSENT_FULL_PHONE_BILLING = 13,
    CCONSENT_SIMPLIFIED_PHONE_BILLING = 14,
    CCONSENT_PHONE_MARKETING = 15,
    CCONSENT_DIRECT_MARKETING = 16,
    CCONSENT_PHONE_SERVICE_INFO = 17,
    CCONSENT_PHONE_NOTICE = CCONSENT_PHONE_SERVICE_INFO,
    CCONSENT_PHONENOTICE = CCONSENT_PHONE_SERVICE_INFO,
    CCONSENT_SMS_3RDPARTY_MARKETING = 18,
    CCONSENT_MAIL_3RDPARTY_MARKETING = 19,
    CCONSENT_PHONE_3RDPARTY_MARKETING = 20,
    CCONSENT_DIRECT_3RDPARTY_MARKETING = 21,
    CCONSENT_SMS_COMPLAINT = 22,
    CCONSENT_MAIL_COMPLAINT = 23,
    CCONSENT_PHONE_COMPLAINT = 24;

$CCONSENTS = array(
    CCONSENT_DATE => array(
        'label' => trans('personal data processing'),
        'name' => 'data_processing',
        'type' => 'date',
    ),
    CCONSENT_EINVOICE => array(
        'label' => trans('cancellation of a traditional invoice (agreement on an electronic invoice)'),
        'name' => 'electronic_invoice',
        'type' => 'boolean',
    ),
    CCONSENT_INVOICENOTICE => array(
        'label' => trans('<!consent>delivery via e-mail'),
        'name' => 'invoice_notice',
        'type' => 'boolean',
    ),
    CCONSENT_MAIL_SERVICE_INFO => array(
        'label' => trans('<!service-info>e-mail'),
        'name' => 'mailing_notice',
        'type' => 'boolean',
    ),
    CCONSENT_SMS_SERVICE_INFO => array(
        'label' => trans('<!service-info>sms'),
        'name' => 'sms_notice',
        'type' => 'boolean',
    ),
    CCONSENT_PHONE_SERVICE_INFO => array(
        'label' => trans('<!service-info>telephone'),
        'name' => 'phone_service_info',
        'type' => 'boolean',
    ),
    CCONSENT_MAIL_MARKETING => array(
        'label' => trans('<!marketing>e-mail'),
        'name' => 'mail_marketing',
        'type' => 'boolean',
    ),
    CCONSENT_SMS_MARKETING => array(
        'label' => trans('<!marketing>sms'),
        'name' => 'sms_marketing',
        'type' => 'boolean',
    ),
    CCONSENT_PHONE_MARKETING => array(
        'label' => trans('<!marketing>telephone'),
        'name' => 'phone_marketing',
        'type' => 'boolean',
    ),
    CCONSENT_DIRECT_MARKETING => array(
        'label' => trans('<!marketing>direct'),
        'name' => 'direct_marketing',
        'type' => 'boolean',
    ),
    CCONSENT_MAIL_3RDPARTY_MARKETING => array(
        'label' => trans('<!marketing>e-mail'),
        'name' => 'mail_3rdparty_marketing',
        'type' => 'boolean',
    ),
    CCONSENT_SMS_3RDPARTY_MARKETING => array(
        'label' => trans('<!marketing>sms'),
        'name' => 'sms_3rdparty_marketing',
        'type' => 'boolean',
    ),
    CCONSENT_PHONE_3RDPARTY_MARKETING => array(
        'label' => trans('<!marketing>telephone'),
        'name' => 'phone_3rdparty_marketing',
        'type' => 'boolean',
    ),
    CCONSENT_DIRECT_3RDPARTY_MARKETING => array(
        'label' => trans('<!marketing>direct'),
        'name' => 'direct_3rdparty_marketing',
        'type' => 'boolean',
    ),
    CCONSENT_MAIL_COMPLAINT => array(
        'label' => trans('<!complaint>e-mail'),
        'name' => 'mail_complaint',
        'type' => 'boolean',
    ),
    CCONSENT_SMS_COMPLAINT => array(
        'label' => trans('<!complaint>sms'),
        'name' => 'sms_complaint',
        'type' => 'boolean',
    ),
    CCONSENT_PHONE_COMPLAINT => array(
        'label' => trans('<!complaint>telephone'),
        'name' => 'phone_complaint',
        'type' => 'boolean',
    ),
    CCONSENT_USERPANEL_SMS => array(
        'label' => trans('document form approval in customer panel using SMS authorization'),
        'name' => 'userpanel_document_sms_approval',
        'type' => 'boolean',
    ),
    CCONSENT_USERPANEL_SCAN => array(
        'label' => trans('document form approval in customer panel using scans'),
        'name' => 'userpanel_document_scan_approval',
        'type' => 'boolean',
    ),
    CCONSENT_TRANSFERFORM => array(
        'label' => trans('invoice transfer form'),
        'name' => 'transfer_form',
        'type' => 'boolean',
    ),
    CCONSENT_PHONE_BILLING => array(
        'label' => trans('phone billing'),
        'name' => 'phone_billing',
        'type' => 'selection',
        'values' => array(
            CCONSENT_NONE_PHONE_BILLING => array(
                'label' => trans('<!billing-type>none'),
            ),
            CCONSENT_SIMPLIFIED_PHONE_BILLING => array(
                'label' => trans('<!billing-type>simplified'),
                'name' => 'simplified_phone_billing',
            ),
            CCONSENT_FULL_PHONE_BILLING => array(
                'label' => trans('<!billing-type>full'),
                'name' => 'full_phone_billing',
            ),
        ),
    ),
    CCONSENT_NONE_PHONE_BILLING => CCONSENT_PHONE_BILLING,
    CCONSENT_SIMPLIFIED_PHONE_BILLING => CCONSENT_PHONE_BILLING,
    CCONSENT_FULL_PHONE_BILLING => CCONSENT_PHONE_BILLING,
);

const CCONSENT_GROUP_MARKETING = 1,
    CCONSENT_GROUP_SERVICE_INFO = 2,
    CCONSENT_GROUP_INVOICES = 3,
    CCONSENT_GROUP_3RDPARTY_MARKETING = 4,
    CCONSENT_GROUP_COMPLAINT = 5;

$CCONSENT_GROUPS = array(
    CCONSENT_GROUP_INVOICES => array(
        'label' => trans('<!consent-group>invoices'),
        'consents' => array(
            CCONSENT_EINVOICE,
            CCONSENT_INVOICENOTICE,
        ),
    ),
    CCONSENT_GROUP_SERVICE_INFO => array(
        'label' => trans('<!consent-group>service information'),
        'consents' => array(
            CCONSENT_MAIL_SERVICE_INFO,
            CCONSENT_SMS_SERVICE_INFO,
            CCONSENT_PHONE_SERVICE_INFO,
        ),
    ),
    CCONSENT_GROUP_MARKETING => array(
        'label' => trans('<!consent-group>marketing'),
        'consents' => array(
            CCONSENT_MAIL_MARKETING,
            CCONSENT_SMS_MARKETING,
            CCONSENT_PHONE_MARKETING,
            CCONSENT_DIRECT_MARKETING,
        ),
    ),
    CCONSENT_GROUP_3RDPARTY_MARKETING => array(
        'label' => trans('<!consent-group>third party marketing'),
        'consents' => array(
            CCONSENT_MAIL_3RDPARTY_MARKETING,
            CCONSENT_SMS_3RDPARTY_MARKETING,
            CCONSENT_PHONE_3RDPARTY_MARKETING,
            CCONSENT_DIRECT_3RDPARTY_MARKETING,
        ),
    ),
    CCONSENT_GROUP_COMPLAINT => array(
        'label' => trans('<!consent-group>complaint information'),
        'consents' => array(
            CCONSENT_MAIL_COMPLAINT,
            CCONSENT_SMS_COMPLAINT,
            CCONSENT_PHONE_COMPLAINT,
        ),
    ),
);

const ORIGIN_FACEBOOK = 1,
    ORIGIN_COMPANY_WEBSITE = 2,
    ORIGIN_SEARCH_ENGINE = 3,
    ORIGIN_ONLINE_ADVERTISING = 4,
    ORIGIN_CAR_ADVERTISING = 5,
    ORIGIN_BANNER = 6,
    ORIGIN_LEAFLET = 7,
    ORIGIN_WORKER = 8,
    ORIGIN_RECOMMENDATION = 9,
    ORIGIN_NEIGHBOUR = 10,
    ORIGIN_EVENT = 11;

$ORIGINS = array(
    ORIGIN_FACEBOOK => '<!origin>Facebook',
    ORIGIN_COMPANY_WEBSITE => '<!origin>company website',
    ORIGIN_SEARCH_ENGINE => '<!origin>search engine',
    ORIGIN_ONLINE_ADVERTISING => '<!origin>online advertising',
    ORIGIN_CAR_ADVERTISING => '<!origin>car advertising',
    ORIGIN_BANNER => '<!origin>banner',
    ORIGIN_LEAFLET => '<!origin>leaflet',
    ORIGIN_WORKER => '<!origin>worker',
    ORIGIN_RECOMMENDATION => '<!origin>recommendation',
    ORIGIN_NEIGHBOUR => '<!origin>neighbour',
    ORIGIN_EVENT => '<!origin>event',
);

// Config types
const CONFIG_TYPE_AUTO = 0,
    CONFIG_TYPE_BOOLEAN = 1,
    CONFIG_TYPE_POSITIVE_INTEGER = 2,
    CONFIG_TYPE_EMAIL = 3,
    CONFIG_TYPE_RELOADTYPE = 4,
    CONFIG_TYPE_DOCTYPE = 5,
    CONFIG_TYPE_MARGINS = 6,
    CONFIG_TYPE_NONE = 7,
    CONFIG_TYPE_RICHTEXT = 8,
    CONFIG_TYPE_MAIL_BACKEND = 9,
    CONFIG_TYPE_MAIL_SECURE = 10,
    CONFIG_TYPE_DATE_FORMAT = 11,
    CONFIG_TYPE_EMAILS = 12;

$CONFIG_TYPES = array(
    CONFIG_TYPE_AUTO => trans('— auto —'),
    CONFIG_TYPE_NONE => trans('none'),
    CONFIG_TYPE_BOOLEAN => trans('boolean'),
    CONFIG_TYPE_POSITIVE_INTEGER => trans('integer greater than 0'),
    CONFIG_TYPE_EMAIL => trans('email'),
    CONFIG_TYPE_RELOADTYPE => trans('reload type'),
    CONFIG_TYPE_DOCTYPE => trans('document type'),
    CONFIG_TYPE_MARGINS => trans('margins'),
    CONFIG_TYPE_RICHTEXT => trans('visual editor'),
    CONFIG_TYPE_MAIL_BACKEND => trans('mail backend'),
    CONFIG_TYPE_MAIL_SECURE => trans('mail security protocol'),
    CONFIG_TYPE_DATE_FORMAT => trans('date format'),
    CONFIG_TYPE_EMAILS => trans('comma separated emails'),
);

// Helpdesk ticket status
const RT_NEW = 0,
    RT_OPEN = 1,
    RT_RESOLVED = 2,
    RT_DEAD = 3,
    RT_SCHEDULED = 4,
    RT_WAITING = 5,
    RT_EXPIRED = 6,
    RT_VERIFIED = 7;

$RT_STATES = array(
    RT_NEW => array(
        'label' => trans('new'),
        'color' => 'red',
        'img' => 'img/new.gif',
        'name' => 'RT_NEW'
    ),

    RT_OPEN => array(
        'label' => trans('opened'),
        'color' => 'black',
        'img' => 'img/open.gif',
        'name' => 'RT_OPEN'
    ),

    RT_RESOLVED => array(
        'label' => trans('resolved'),
        'color' => 'grey',
        'img' => 'img/resolved.gif',
        'name' => 'RT_RESOLVED'
    ),

    RT_DEAD => array(
        'label' => trans('dead'),
        'color' => '#8B0000',
        'img' => 'img/dead.gif',
        'name' => 'RT_DEAD'
    ),

    RT_SCHEDULED => array(
        'label' => trans('scheduled'),
        'color' => '#4169E1',
        'img' => 'img/calendar.gif',
        'name' => 'RT_SCHEDULED'
    ),

    RT_WAITING => array(
        'label' => trans('waiting'),
        'color' => '#b26b00',
        'img' => 'img/calendar.gif',
        'name' => 'RT_WAITING'
    ),
    RT_EXPIRED => array(
        'label' => trans('<!rt>expired'),
        'color' => '#278981',
        'img' => 'img/calendar.gif',
        'name' => 'RT_EXPIRED'
    ),
    RT_VERIFIED => array(
        'label' => trans('verified'),
        'color' => 'green',
        'img' => 'img/verifier.png',
        'name' => 'RT_VERIFIED'
    ),
);

// Helpdesk rights
const RT_RIGHT_READ = 1,
    RT_RIGHT_WRITE = 2,
    RT_RIGHT_DELETE = 4,
    RT_RIGHT_SMS_NOTICE = 8,
    RT_RIGHT_INDICATOR = 16,
    RT_RIGHT_EMAIL_NOTICE = 32,
    RT_RIGHT_NOTICE = 40,
    RT_RIGHT_SMS_WATCHING_NOTICE = 64,
    RT_RIGHT_EMAIL_WATCHING_NOTICE = 128;

$RT_RIGHTS = array(
    RT_RIGHT_READ => trans("Read"),
    RT_RIGHT_WRITE => trans("Write (+R)"),
    RT_RIGHT_DELETE => trans("Delete (+R)"),
    RT_RIGHT_SMS_NOTICE => trans("SMS Notice (+R)"),
    RT_RIGHT_EMAIL_NOTICE => trans("E-mail Notice (+R)"),
    RT_RIGHT_SMS_WATCHING_NOTICE => trans("Watcher SMS Notice"),
    RT_RIGHT_EMAIL_WATCHING_NOTICE => trans("Watcher E-mail Notice"),
    RT_RIGHT_INDICATOR => trans("Indicator (+R)"),
);

// helpdesk new message/note notification recipients
const RT_NOTIFICATION_USER = 1,
    RT_NOTIFICATION_VERIFIER = 2;

//Helpdesk ticket source
const RT_SOURCE_UNKNOWN = 0,
    RT_SOURCE_PHONE = 1,
    RT_SOURCE_EMAIL = 2,
    RT_SOURCE_USERPANEL = 3,
    RT_SOURCE_PERSONAL = 4,
    RT_SOURCE_MESSCHAT = 5,
    RT_SOURCE_PAPER = 6,
    RT_SOURCE_SMS = 7,
    RT_SOURCE_CALLCENTER = 8,
    RT_SOURCE_SIDUSIS = 9;

$RT_SOURCES = array(
    RT_SOURCE_UNKNOWN => 'unknown/other',
    RT_SOURCE_PHONE => 'phone',
    RT_SOURCE_EMAIL => 'e-mail',
    RT_SOURCE_USERPANEL => 'userpanel',
    RT_SOURCE_PERSONAL => 'personal',
    RT_SOURCE_MESSCHAT => 'instant messenger',
    RT_SOURCE_PAPER => 'complaint',
    RT_SOURCE_SMS => 'SMS',
    RT_SOURCE_CALLCENTER => 'call center',
    RT_SOURCE_SIDUSIS => 'SIDUSIS',
);

//Helpdesk ticket priority
const RT_PRIORITY_IDLE = -3,
    RT_PRIORITY_VERYLOW = -2,
    RT_PRIORITY_LOW = -1,
    RT_PRIORITY_NORMAL = 0,
    RT_PRIORITY_HIGHER = 1,
    RT_PRIORITY_URGENT = 2,
    RT_PRIORITY_CRITICAL = 3;

$RT_PRIORITIES = array(
    RT_PRIORITY_IDLE => trans('idle'),
    RT_PRIORITY_VERYLOW => trans('very low'),
    RT_PRIORITY_LOW => trans('low'),
    RT_PRIORITY_NORMAL => trans('normal'),
    RT_PRIORITY_HIGHER => trans('higher'),
    RT_PRIORITY_URGENT => trans('urgent'),
    RT_PRIORITY_CRITICAL => trans('critical'),
);

$RT_PRIORITY_STYLES = array(
    RT_PRIORITY_IDLE => 'background-color: darkblue; color: white;',
    RT_PRIORITY_VERYLOW => 'background-color: dodgerblue; color: white;',
    RT_PRIORITY_LOW => 'background-color: chartreuse; color: black;',
    RT_PRIORITY_NORMAL => 'background-color: transparent; color: black;',
    RT_PRIORITY_HIGHER => 'background-color: yellow; color: black;',
    RT_PRIORITY_URGENT => 'background-color: orange; color: black;',
    RT_PRIORITY_CRITICAL => 'background-color: red; color: black;',
);

$RT_MAIL_PRIORITIES = array(
    RT_PRIORITY_IDLE => 7,
    RT_PRIORITY_VERYLOW => 6,
    RT_PRIORITY_LOW => 5,
    RT_PRIORITY_NORMAL => 4,
    RT_PRIORITY_HIGHER => 3,
    RT_PRIORITY_URGENT => 2,
    RT_PRIORITY_CRITICAL => 1,
);

// Helpdesk cause type
const RT_CAUSE_OTHER = 0,
    RT_CAUSE_CUSTOMER = 1,
    RT_CAUSE_COMPANY = 2;

$RT_CAUSE = array(
    RT_CAUSE_OTHER => trans("unknown/other"),
    RT_CAUSE_CUSTOMER => trans("customer's side"),
    RT_CAUSE_COMPANY => trans("company's side")
);

// Helpdesk note type
const RTMESSAGE_REGULAR = 0,
    RTMESSAGE_NOTE = 1,
    RTMESSAGE_OWNER_CHANGE = 2,
    RTMESSAGE_QUEUE_CHANGE = 4,
    RTMESSAGE_STATE_CHANGE = 8,
    RTMESSAGE_CAUSE_CHANGE = 16,
    RTMESSAGE_CUSTOMER_CHANGE = 32,
    RTMESSAGE_SUBJECT_CHANGE = 64,
    RTMESSAGE_CATEGORY_CHANGE = 128,
    RTMESSAGE_LOCATION_CHANGE = 256,
    RTMESSAGE_NODE_CHANGE = 512,
    RTMESSAGE_NETNODE_CHANGE = 1024,
    RTMESSAGE_PRIORITY_CHANGE = 2048,
    RTMESSAGE_NETDEV_CHANGE = 4096,
    RTMESSAGE_VERIFIER_CHANGE = 8192,
    RTMESSAGE_DEADLINE_CHANGE = 16384,
    RTMESSAGE_SERVICE_CHANGE = 32768,
    RTMESSAGE_TYPE_CHANGE = 65536,
    RTMESSAGE_INVPROJECT_CHANGE = 131072,
    RTMESSAGE_VERIFIER_RTIME = 262144,
    RTMESSAGE_SOURCE_CHANGE = 524288,
    RTMESSAGE_PARENT_CHANGE = 1048576,
    RTMESSAGE_ASSIGNED_EVENT_ADD = 2097152,
    RTMESSAGE_ASSIGNED_EVENT_CHANGE = 4194304,
    RTMESSAGE_ASSIGNED_EVENT_DELETE = 8388608;

const NETWORK_NODE_FLAG_BSA = 1,
    NETWORK_NODE_FLAG_INTERFACE_COUNT_INCREASE_POSSIBILITY = 2,
    NETWORK_NODE_FLAG_CRITICAL_INFRASTRUCTURE = 4;

$NETWORK_NODE_FLAGS = array(
    NETWORK_NODE_FLAG_BSA => trans('<!uke-pit>BSA service'),
    NETWORK_NODE_FLAG_INTERFACE_COUNT_INCREASE_POSSIBILITY => trans('<!uke-pit>interface count increase possibility'),
    NETWORK_NODE_FLAG_CRITICAL_INFRASTRUCTURE => trans('<!uke-pit>critical infrastructure'),
);

$NETWORK_NODE_SERVICES = array(
    1 => trans('<!uke-pit-service>access to cable ducting'),
    2 => trans('<!uke-pit-service>access to dark fibers'),
    3 => trans('<!uke-pit-service>LLU'),
    4 => trans('<!uke-pit-service>VULA'),
    5 => trans('<!uke-pit-service>access to pole substructure, towers and masts'),
    6 => trans('<!uke-pit-service>collocation'),
    7 => trans('<!uke-pit-service>network connection in collocation mode'),
    8 => trans('<!uke-pit-service>network connection in linear mode'),
    9 => trans('<!uke-pit-service>provided to end user'),
    10 => trans('<!uke-pit-service>other'),
);

const NETWORK_INTERFACE_TYPE_UNI = 0,
    NETWORK_INTERFACE_TYPE_NNI = 1;

$NETWORK_INTERFACE_TYPES = array(
    NETWORK_INTERFACE_TYPE_UNI => array(
        'label' => trans('Access network interface'),
        'name' => 'NETWORK_INTERFACE_TYPE_UNI',
        'alias' => 'uni',
    ),
    NETWORK_INTERFACE_TYPE_NNI => array(
        'label' => trans('Infrastructure network interface'),
        'name' => 'NETWORK_INTERFACE_TYPE_NNI',
        'alias' => 'nni',
    ),
);

//Request Tracker Ticket Types
const RT_TYPE_OFFER = 1,
    RT_TYPE_DOCS = 2,
    RT_TYPE_FAULT = 3,
    RT_TYPE_INST = 4,
    RT_TYPE_MOD = 5,
    RT_TYPE_START = 6,
    RT_TYPE_STOP = 7,
    RT_TYPE_REMOVE = 8,
    RT_TYPE_OTHER = 9,
    RT_TYPE_CONF = 10,
    RT_TYPE_PAYMENT = 11,
    RT_TYPE_TRANSFER = 12,
    RT_TYPE_NO_SERVICE = 13;

$RT_TYPES = array(
    RT_TYPE_OTHER => array(
        'label' => 'other',
        'class' => 'lms-ui-rt-ticket-type-other',
        'name' => 'RT_TYPE_OTHER'
    ),
    RT_TYPE_OFFER => array(
        'label' => 'offer',
        'class' => 'lms-ui-rt-ticket-type-offer',
        'name' => 'RT_TYPE_OFFER'
    ),
    RT_TYPE_DOCS => array(
        'label' => 'documents',
        'class' => 'lms-ui-rt-ticket-type-docs',
        'name' => 'RT_TYPE_DOCS'
    ),
    RT_TYPE_FAULT => array(
        'label' => 'fault',
        'class' => 'lms-ui-rt-ticket-type-fault',
        'name' => 'RT_TYPE_FAULT'
    ),
    RT_TYPE_INST => array(
        'label' => 'installation',
        'class' => 'lms-ui-rt-ticket-type-inst',
        'name' => 'RT_TYPE_INST'
    ),
    RT_TYPE_MOD => array(
        'label' => 'modification',
        'class' => 'lms-ui-rt-ticket-type-mod',
        'name' => 'RT_TYPE_MOD'
    ),
    RT_TYPE_CONF => array(
        'label' => '<!rt-type>configuration',
        'class' => 'lms-ui-rt-ticket-type-conf',
        'name' => 'RT_TYPE_CONF'
    ),
    RT_TYPE_START => array(
        'label' => 'service start',
        'class' => 'lms-ui-rt-ticket-type-start',
        'name' => 'RT_TYPE_START'
    ),
    RT_TYPE_STOP => array(
        'label' => 'service hold',
        'class' => 'lms-ui-rt-ticket-type-stop',
        'name' => 'RT_TYPE_STOP'
    ),
    RT_TYPE_TRANSFER => array(
      'label' => 'service transfer',
      'class' => 'lms-ui-rt-ticket-type-transfer',
      'name' => 'RT_TYPE_TRANSFER'
    ),
    RT_TYPE_REMOVE => array(
        'label' => 'deinstallation',
        'class' => 'lms-ui-rt-ticket-type-remove',
        'name' => 'RT_TYPE_REMOVE'
    ),
    RT_TYPE_PAYMENT => array(
        'label' => 'payment',
        'class' => 'lms-ui-rt-ticket-type-payment',
        'name' => 'RT_TYPE_PAYMENT'
    ),
    RT_TYPE_NO_SERVICE => array(
        'label' => 'no service',
        'class' => 'lms-ui-rt-ticket-type-no-service',
        'name' => 'RT_TYPE_NO_SERVICE'
    ),
);

// Messages status
const MSG_NEW = 1,
    MSG_SENT = 2,
    MSG_ERROR = 3,
    MSG_DRAFT = 4,
    MSG_DELIVERED = 5,
    MSG_CANCELLED = 6,
    MSG_BOUNCED = 7;

$MESSAGESTATUSES = array(
    MSG_NEW => array(
        'class' => 'lms-ui-message-new',
        'label' => trans('waiting<!plural>'),
    ),
    MSG_SENT => array(
        'class' => 'lms-ui-message-sent',
        'label' => trans('sent<!plural>'),
    ),
    MSG_ERROR => array(
        'class' => 'lms-ui-message-error',
        'label' => trans('errornous<!plural>'),
    ),
    MSG_DRAFT => array(
        'class' => 'lms-ui-message-draft',
        'label' => trans('drafts'),
    ),
    MSG_DELIVERED => array(
        'class' => 'lms-ui-message-delivered',
        'label' => trans('delivered<!plural>'),
    ),
    MSG_CANCELLED => array(
        'class' => 'lms-ui-message-cancelled',
        'label' => trans('cancelled<!plural>'),
    ),
    MSG_BOUNCED => array(
        'class' => 'lms-ui-message-bounced',
        'label' => trans('bounced<!plural>'),
    ),
);

// Messages types
const MSG_MAIL = 1,
    MSG_SMS = 2,
    MSG_ANYSMS = 3,
    MSG_WWW = 4,
    MSG_USERPANEL = 5,
    MSG_USERPANEL_URGENT = 6;

// Template types
const TMPL_WARNING = 1,
    TMPL_MAIL = 2,
    TMPL_SMS = 3,
    TMPL_WWW = 4,
    TMPL_USERPANEL = 5,
    TMPL_USERPANEL_URGENT = 6,
    TMPL_HELPDESK = 7,
    TMPL_CNOTE_REASON = 8;

$MESSAGETEMPLATES = array(
    TMPL_WARNING => array(
        'class' => 'lms-ui-icon-warning',
        'label' => trans('<!message>warning'),
    ),
    TMPL_MAIL => array(
        'class' => 'lms-ui-icon-mail',
        'label' => trans('mail'),
    ),
    TMPL_SMS => array(
        'class' => 'lms-ui-icon-sms',
        'label' => trans('sms'),
    ),
    TMPL_WWW => array(
        'class' => 'lms-ui-icon-www',
        'label' => trans('www'),
    ),
    TMPL_USERPANEL => array(
        'class' => 'lms-ui-icon-userpanel',
        'label' => trans('<!message>userpanel'),
    ),
    TMPL_USERPANEL_URGENT => array(
        'class' => 'lms-ui-icon-userpanel',
        'label' => trans('<!message>userpanel (urgent)'),
    ),
    TMPL_HELPDESK => array(
        'class' => 'lms-ui-icon-helpdesk',
        'label' => trans('<!message>helpdesk'),
    ),
    TMPL_CNOTE_REASON => array(
        'class' => 'lms-ui-icon-finances',
        'label' => trans('<!message>credit note reason'),
    ),
);

// Account types
const ACCOUNT_SHELL = 1,
    ACCOUNT_MAIL = 2,
    ACCOUNT_WWW = 4,
    ACCOUNT_FTP = 8,
    ACCOUNT_SQL = 16,
    ACCOUNT_CLOUD = 32;

$ACCOUNTTYPES = array(
    ACCOUNT_SHELL => array(
        'label' => trans('shell'),
        'alias' => 'sh',
        'accountlimittip' => trans('Enter limit of shell accounts'),
        'accountquotatip' => trans('Enter quota limit of shell account'),
        'accountlimitlabel' => trans('Limit of shell accounts:'),
        'accountquotalabel' => trans('Quota limit of shell account:'),
    ),
    ACCOUNT_MAIL => array(
        'label' => trans('mail'),
        'alias' => 'mail',
        'accountlimittip' => trans('Enter limit of e-mail accounts'),
        'accountquotatip' => trans('Enter quota limit of e-mail account'),
        'accountlimitlabel' => trans('Limit of e-mail accounts:'),
        'accountquotalabel' => trans('Quota limit of e-mail account:'),
    ),
    ACCOUNT_WWW => array(
        'label' => trans('www'),
        'alias' => 'www',
        'accountlimittip' => trans('Enter limit of www accounts'),
        'accountquotatip' => trans('Enter quota limit of www account'),
        'accountlimitlabel' => trans('Limit of www accounts:'),
        'accountquotalabel' => trans('Quota limit of www account:'),
    ),
    ACCOUNT_FTP => array(
        'label' => trans('ftp'),
        'alias' => 'ftp',
        'accountlimittip' => trans('Enter limit of ftp accounts'),
        'accountquotatip' => trans('Enter quota limit of ftp account'),
        'accountlimitlabel' => trans('Limit of ftp accounts:'),
        'accountquotalabel' => trans('Quota limit of ftp account:'),
    ),
    ACCOUNT_SQL => array(
        'label' => trans('sql'),
        'alias' => 'sql',
        'accountlimittip' => trans('Enter limit of sql accounts'),
        'accountquotatip' => trans('Enter quota limit of sql account'),
        'accountlimitlabel' => trans('Limit of sql accounts:'),
        'accountquotalabel' => trans('Quota limit of sql account:'),
    ),
    ACCOUNT_CLOUD => array(
        'label' => trans('cloud'),
        'alias' => 'cloud',
        'accountlimittip' => trans('Enter limit of cloud accounts'),
        'accountquotatip' => trans('Enter quota limit of cloud account'),
        'accountlimitlabel' => trans('Limit of cloud accounts:'),
        'accountquotalabel' => trans('Quota limit of cloud account:'),
    ),
);

// Financial document types
const DOC_INVOICE = 1,
    DOC_RECEIPT = 2,
    DOC_CNOTE = 3,
    DOC_DNOTE = 5,
    DOC_INVOICE_PRO = 6,
    DOC_INVOICE_PURCHASE = 7;

// Non-financial document types
const DOC_CONTRACT = -1,
    DOC_ANNEX = -2,
    DOC_PROTOCOL = -3,
    DOC_ORDER = -4,
    DOC_SHEET = -5,
    DOC_BREACH = -6,
    DOC_PAYMENT_BOOK = -7,
    DOC_PAYMENTBOOK = DOC_PAYMENT_BOOK,
    DOC_PAYMENT_SUMMON = -8,
    DOC_PAYMENTSUMMONS = DOC_PAYMENT_SUMMON,
    DOC_PAYMENT_PRESUMMON = -9,
    DOC_PAYMENTPRESUMMONS = DOC_PAYMENT_PRESUMMON,
    DOC_BILLING = -10,
    DOC_PRICE_LIST = -11,
    DOC_PRICELIST = DOC_PRICE_LIST,
    DOC_PROMOTION = -12,
    DOC_WARRANTY = -13,
    DOC_REGULATIONS = -14,
    DOC_CONF_FILE = -15,
    DOC_OFFER = -16,
    DOC_COMPLAINT = -17,
    DOC_OTHER = -128;

$DOCTYPES = array(
    DOC_OFFER           =>      trans('offer'),
    DOC_BILLING         =>      trans('billing'),
    DOC_INVOICE         =>      trans('invoice'),
    DOC_INVOICE_PRO     =>      trans('pro-forma invoice'),
    DOC_INVOICE_PURCHASE =>     trans('purchase invoice'),
    DOC_RECEIPT         =>      trans('cash receipt'),
    DOC_CNOTE       =>  trans('credit note'), // faktura korygujaca
//    DOC_CMEMO     =>  trans('credit memo'), // nota korygujaca
    DOC_DNOTE       =>  trans('debit note'), // nota obciazeniowa/debetowa/odsetkowa
    DOC_CONTRACT        =>      trans('contract'), //umowa
    DOC_ANNEX       =>  trans('annex'), //aneks umowy
    DOC_PROTOCOL        =>      trans('protocol'), //protokol uruchomienia
    DOC_ORDER       =>  trans('order'), //zamowienie
    DOC_SHEET       =>  trans('customer sheet'), // karta klienta
    DOC_BREACH      =>  trans('contract termination'), //rozwiazanie umowy
    DOC_PAYMENT_BOOK  => trans('payment book'), // ksiazeczka oplat
    DOC_PAYMENT_SUMMON  => trans('payment summon'), // wezwanie do zapłaty
    DOC_PAYMENT_PRESUMMON  => trans('payment pre-summon'), // przedsądowe wezw. do zapłaty
    DOC_PRICE_LIST       =>  trans('price-list'), // cennik
    DOC_PROMOTION       =>  trans('promotion'), // promocja
    DOC_WARRANTY       =>  trans('warranty'), // gwarancja
    DOC_REGULATIONS       =>  trans('regulations'), // regulamin
    DOC_CONF_FILE   =>  trans('configuration file'),
    DOC_COMPLAINT   =>  trans('complaint'),
    DOC_OTHER       =>  trans('other'),
);

$DOCTYPE_ALIASES = array(
    DOC_OFFER => 'offer',
    DOC_BILLING => 'billing',
    DOC_CONTRACT => 'contract',
    DOC_ANNEX => 'annex',
    DOC_PROTOCOL => 'protocol',
    DOC_ORDER => 'order',
    DOC_SHEET => 'sheet',
    DOC_BREACH => 'contract-termination',
    DOC_PAYMENT_BOOK => 'payment-book',
    DOC_PAYMENT_SUMMON  => 'payment-summon',
    DOC_PAYMENT_PRESUMMON  => 'payment-pre-summon',
    DOC_PRICE_LIST => 'price-list',
    DOC_PROMOTION => 'promotion',
    DOC_WARRANTY => 'warranty',
    DOC_REGULATIONS => 'regulations',
    DOC_CONF_FILE => 'configuration-file',
    DOC_COMPLAINT => 'complaint',
    DOC_OTHER => 'other',
);

// for all document types
const DOC_OPEN = 0,
    DOC_CLOSED = 1;
// for document types < 0
const DOC_CLOSED_AFTER_CUSTOMER_SCAN = 2,
    DOC_CLOSED_AFTER_CUSTOMER_SMS = 3;

// document flags
const DOC_FLAG_RECEIPT = 1,
    DOC_FLAG_TELECOM_SERVICE = 2,
    DOC_FLAG_RELATED_ENTITY = 4,
    DOC_FLAG_SPLIT_PAYMENT = 8,
    DOC_FLAG_NET_ACCOUNT = 16;

$DOC_FLAGS = array(
    DOC_FLAG_RECEIPT => trans('FP'),
    DOC_FLAG_TELECOM_SERVICE => trans('EE'),
    DOC_FLAG_RELATED_ENTITY => trans('TP'),
    DOC_FLAG_SPLIT_PAYMENT => trans('MPP'),
);

const DOC_ENTITY_ORIGINAL = 1,
    DOC_ENTITY_COPY = 2,
    DOC_ENTITY_DUPLICATE = 4;

$DOCENTITIES = array(
    DOC_ENTITY_ORIGINAL => trans('original'),
    DOC_ENTITY_COPY => trans('copy'),
    DOC_ENTITY_DUPLICATE => trans('duplicate'),
);

const DOCRIGHT_VIEW = 1,
    DOCRIGHT_CREATE = 2,
    DOCRIGHT_CONFIRM = 4,
    DOCRIGHT_EDIT = 8,
    DOCRIGHT_DELETE = 16,
    DOCRIGHT_ARCHIVE = 32;

$DOCRIGHTS = array(
    DOCRIGHT_VIEW => trans('Viewing'),
    DOCRIGHT_CREATE => trans('Creating'),
    DOCRIGHT_CONFIRM => trans('Confirming'),
    DOCRIGHT_EDIT => trans('Editing'),
    DOCRIGHT_DELETE => trans('Deleting'),
    DOCRIGHT_ARCHIVE => trans('Archiving'),
);

// Guarantee periods
$GUARANTEEPERIODS = array(
    -1 => trans('lifetime'),
    0  => trans('none'),
    12 => trans('$a months', 12),
    24 => trans('24 months', 24),
    36 => trans('$a months', 36),
    48 => trans('$a months', 48),
    60 => trans('$a months', 60)
);

const DISPOSABLE = 0,
    DAILY = 1,
    WEEKLY = 2,
    MONTHLY = 3,
    QUARTERLY = 4,
    YEARLY = 5,
    CONTINUOUS = 6,
    HALFYEARLY = 7;

// Accounting periods
$PERIODS = array(
    YEARLY  =>  trans('yearly'),
    HALFYEARLY  =>      trans('half-yearly'),
    QUARTERLY   =>  trans('quarterly'),
    MONTHLY =>  trans('monthly'),
//    WEEKLY    =>  trans('weekly'),
//    DAILY =>  trans('daily'),
    DISPOSABLE  =>  trans('disposable')
);

$BILLING_PERIODS = array(
    DISPOSABLE => ConfigHelper::getConfig('assignments.billing_period_disposable', trans('disposable')),
    DAILY => ConfigHelper::getConfig('assignments.billing_period_daily', trans('daily')),
    WEEKLY => ConfigHelper::getConfig('assignments.billing_period_weekly', trans('weekly')),
    MONTHLY => ConfigHelper::getConfig('assignments.billing_period_monthly', trans('monthly')),
    QUARTERLY => ConfigHelper::getConfig('assignments.billing_period_quarterly', trans('quarterly')),
    HALFYEARLY => ConfigHelper::getConfig('assignments.billing_period_half_yearly', trans('half-yearly')),
    YEARLY => ConfigHelper::getConfig('assignments.billing_period_yearly', trans('yearly')),
);

// Numbering periods
$NUM_PERIODS = array(
    CONTINUOUS  =>  trans('continuously'),
    YEARLY  =>  trans('yearly'),
    HALFYEARLY  =>  trans('half-yearly'),
    QUARTERLY   =>  trans('quarterly'),
    MONTHLY =>  trans('monthly'),
//    WEEKLY    =>  trans('weekly'),
    DAILY   =>  trans('daily'),
);

// Service Types
const SERVICE_OTHER = -1,
    SERVICE_INTERNET = 1,
    SERVICE_HOSTING = 2,
    SERVICE_SERVICE = 3,
    SERVICE_PHONE = 4,
    SERVICE_TV = 5,
    SERVICE_TRANSMISSION = 6;

// Tariff flags
const TARIFF_FLAG_REWARD_PENALTY_ON_TIME_PAYMENTS = 1,
    TARIFF_FLAG_REWARD_PENALTY_EINVOICE = 2,
    TARIFF_FLAG_REWARD_PENALTY_MAIL_MARKETING = 4,
    TARIFF_FLAG_REWARD_PENALTY_SMS_MARKETING = 8,
    TARIFF_FLAG_NET_ACCOUNT = 16,
    TARIFF_FLAG_SPLIT_PAYMENT = 32;

define(
    'TARIFF_FLAG_ALL_REWARD_PENALTY_FLAGS',
    TARIFF_FLAG_REWARD_PENALTY_ON_TIME_PAYMENTS
        | TARIFF_FLAG_REWARD_PENALTY_EINVOICE
        | TARIFF_FLAG_REWARD_PENALTY_MAIL_MARKETING
        | TARIFF_FLAG_REWARD_PENALTY_SMS_MARKETING
);

$TARIFF_FLAGS = array(
    TARIFF_FLAG_REWARD_PENALTY_ON_TIME_PAYMENTS => trans('on time payments'),
    TARIFF_FLAG_REWARD_PENALTY_EINVOICE => trans('electronic invoice'),
    TARIFF_FLAG_REWARD_PENALTY_MAIL_MARKETING => trans('e-mail marketing'),
    TARIFF_FLAG_REWARD_PENALTY_SMS_MARKETING => trans('sms marketing'),
);

// Liability flags
const LIABILITY_FLAG_NET_ACCOUT = 16,
    LIABILITY_FLAG_SPLIT_PAYMENT = 32;

// VoIP call directions
const BILLING_RECORD_DIRECTION_INCOMING = 1,
    BILLING_RECORD_DIRECTION_OUTGOING = 2;

// VoIP call types
const BILLING_RECORD_TYPE_VOICE_CALL = 0,
    BILLING_RECORD_TYPE_CALL = BILLING_RECORD_TYPE_VOICE_CALL,
    BILLING_RECORD_TYPE_SMS = 1,
    BILLING_RECORD_TYPE_MMS = 2,
    BILLING_RECORD_TYPE_DATA_TRANSFER = 3,
    BILLING_RECORD_TYPE_VIDEO_CALL = 4;

// VoIP call statuses
const BILLING_RECORD_STATUS_BUSY = 1,
    BILLING_RECORD_STATUS_ANSWERED = 2,
    BILLING_RECORD_STATUS_NO_ANSWER = 3,
    BILLING_RECORD_STATUS_SERVER_FAILED = 4,
    BILLING_RECORD_STATUS_UNKNOWN = 5;

// VoIP pool number types
const VOIP_POOL_NUMBER_MOBILE = 1,
    VOIP_POOL_NUMBER_FIXED = 2;

$VOIP_POOL_NUMBER_TYPES = array(
    VOIP_POOL_NUMBER_MOBILE => trans("mobile"),
    VOIP_POOL_NUMBER_FIXED  => trans("fixed")
);

// bit flags for VoIP call
const BILLING_RECORD_FLAG_ADMIN_RECORDING = 1,
    BILLING_RECORD_FLAG_CUSTOMER_RECORDING = 2;

const VOIP_ACCOUNT_FLAG_ADMIN_RECORDING = BILLING_RECORD_FLAG_ADMIN_RECORDING,
    VOIP_ACCOUNT_FLAG_CUSTOMER_RECORDING = BILLING_RECORD_FLAG_CUSTOMER_RECORDING,
    VOIP_ACCOUNT_FLAG_TRUNK = 4;

$SERVICETYPES = array(
    SERVICE_OTHER => ConfigHelper::getConfig('assignments.type_other', ConfigHelper::getConfig('tarifftypes.other', trans('other'))),
    SERVICE_INTERNET => ConfigHelper::getConfig('assignments.type_internet', ConfigHelper::getConfig('tarifftypes.internet', trans('internet'))),
    SERVICE_HOSTING => ConfigHelper::getConfig('assignments.type_hosting', ConfigHelper::getConfig('tarifftypes.hosting', trans('hosting'))),
    SERVICE_SERVICE => ConfigHelper::getConfig('assignments.type_service', ConfigHelper::getConfig('tarifftypes.service', trans('service'))),
    SERVICE_PHONE => ConfigHelper::getConfig('assignments.type_phone', ConfigHelper::getConfig('tarifftypes.phone', trans('phone'))),
    SERVICE_TV => ConfigHelper::getConfig('assignments.type_tv', ConfigHelper::getConfig('tarifftypes.tv', trans('tv'))),
    SERVICE_TRANSMISSION => ConfigHelper::getConfig('assignments.type_transmission', ConfigHelper::getConfig('tarifftypes.transmission', trans('transmission'))),
);

const INVOICE_FEATURE_DEADLINE = 1,
    INVOICE_FEATURE_TO_PAY = 2,
    INVOICE_FEATURE_TRANSFER_FORM = 4,
    INVOICE_FEATURE_AUTO_PAYMENT = 8;

const PAYTYPE_CASH = 1,
    PAYTYPE_TRANSFER = 2,
    PAYTYPE_TRANSFER_CASH = 3,
    PAYTYPE_CARD = 4,
    PAYTYPE_COMPENSATION = 5,
    PAYTYPE_BARTER = 6,
    PAYTYPE_CONTRACT = 7,
    PAYTYPE_PAID = 8,
    PAYTYPE_CASH_ON_DELIVERY = 9,
    PAYTYPE_INSTALMENTS = 10,
    PAYTYPE_BANK_LOAN = 11;

$PAYTYPES = array(
    PAYTYPE_CASH => array(
        'label' => 'cash',
        'features' => INVOICE_FEATURE_AUTO_PAYMENT,
    ),
    PAYTYPE_TRANSFER => array(
        'label' => 'transfer',
        'features' => INVOICE_FEATURE_DEADLINE | INVOICE_FEATURE_TO_PAY | INVOICE_FEATURE_TRANSFER_FORM,
    ),
    PAYTYPE_TRANSFER_CASH  => array(
        'label' => 'transfer/cash',
        'features' => INVOICE_FEATURE_DEADLINE | INVOICE_FEATURE_TO_PAY | INVOICE_FEATURE_TRANSFER_FORM,
    ),
    PAYTYPE_CARD => array(
        'label' => 'card',
        'features' => INVOICE_FEATURE_AUTO_PAYMENT,
    ),
    PAYTYPE_COMPENSATION => array(
        'label' => 'compensation',
        'features' => INVOICE_FEATURE_AUTO_PAYMENT,
    ),
    PAYTYPE_BARTER => array(
        'label' => 'barter',
        'features' => INVOICE_FEATURE_AUTO_PAYMENT,
    ),
    PAYTYPE_CONTRACT => array(
        'label' => 'contract',
        'features' => INVOICE_FEATURE_DEADLINE | INVOICE_FEATURE_TO_PAY | INVOICE_FEATURE_TRANSFER_FORM,
    ),
    PAYTYPE_PAID => array(
        'label' => 'paid',
        'features' => INVOICE_FEATURE_AUTO_PAYMENT,
    ),
    PAYTYPE_CASH_ON_DELIVERY  => array(
        'label' => 'cash on delivery',
        'features' => INVOICE_FEATURE_DEADLINE | INVOICE_FEATURE_TO_PAY | INVOICE_FEATURE_TRANSFER_FORM,
    ),
    PAYTYPE_INSTALMENTS => array(
        'label' => 'instalments',
        'features' => INVOICE_FEATURE_DEADLINE | INVOICE_FEATURE_TO_PAY | INVOICE_FEATURE_TRANSFER_FORM,
    ),
    PAYTYPE_BANK_LOAN => array(
        'label' => 'bank loan',
        'features' => INVOICE_FEATURE_DEADLINE | INVOICE_FEATURE_TO_PAY | INVOICE_FEATURE_TRANSFER_FORM,
    ),
);

// Contact types
const CONTACT_MOBILE = 1,
    CONTACT_FAX = 2,
    CONTACT_LANDLINE = 4,
    CONTACT_EMAIL = 8,
    CONTACT_INVOICES = 16,
    CONTACT_NOTIFICATIONS = 32,
    CONTACT_BANKACCOUNT = 64,
    CONTACT_TECHNICAL = 128,
    CONTACT_URL = 256,
    CONTACT_IM = 7680,
    CONTACT_IM_GG = 512,
    CONTACT_IM_YAHOO = 1024,
    CONTACT_IM_SKYPE = 2048,
    CONTACT_IM_FACEBOOK = 4096,
    CONTACT_DISABLED = 16384,
    CONTACT_DOCUMENTS = 32768,
    CONTACT_REPRESENTATIVE = 65536,
    CONTACT_HELPDESK_NOTIFICATIONS = 131072;

$CONTACTTYPES = array(
    CONTACT_MOBILE          =>  trans('mobile'),
    CONTACT_FAX             =>  trans('fax'),
    CONTACT_INVOICES        =>  trans('invoices'),
    CONTACT_DISABLED        =>  trans('disabled'),
    CONTACT_NOTIFICATIONS   =>  trans('notifications'),
    CONTACT_TECHNICAL       =>  trans('technical'),
    CONTACT_IM_GG           =>  trans('Gadu-Gadu'),
    CONTACT_IM_YAHOO        =>  trans('Yahoo'),
    CONTACT_IM_SKYPE        =>  trans('Skype'),
    CONTACT_IM_FACEBOOK     =>  trans('Facebook'),
    CONTACT_DOCUMENTS       =>  trans('documents'),
    CONTACT_REPRESENTATIVE  =>  trans('representative'),
    CONTACT_HELPDESK_NOTIFICATIONS =>  trans('helpdesk'),
);

const DISCOUNT_PERCENTAGE = 1,
    DISCOUNT_AMOUNT = 2;

$DISCOUNTTYPES = array(
    DISCOUNT_PERCENTAGE => '%',
    DISCOUNT_AMOUNT     => trans('amount'),
);

//weekdays
const DAY_MONDAY = 0,
    DAY_TUESDAY = 1,
    DAY_WEDNESDAY = 2,
    DAY_THURSDAY = 3,
    DAY_FRIDAY = 4,
    DAY_SATURDAY = 5,
    DAY_SUNDAY = 6;

$DAYS = array(
    DAY_MONDAY  => trans('Mon'),
    DAY_TUESDAY => trans('Tue'),
    DAY_WEDNESDAY   => trans('Wed'),
    DAY_THURSDAY    => trans('Thu'),
    DAY_FRIDAY  => trans('Fri'),
    DAY_SATURDAY    => trans('Sat'),
    DAY_SUNDAY  => trans('Sun'),
);

const LINKTYPE_WIRE = 0,
    LINKTYPE_WIRELESS = 1,
    LINKTYPE_FIBER = 2;

$LINKTYPES = array(
    LINKTYPE_WIRE     => trans('wire'),
    LINKTYPE_WIRELESS => trans('wireless'),
    LINKTYPE_FIBER    => trans('fiber'),
);

$SIIS_LINKTECHNOLOGIES = array(
    0 => array(
        1 => 'ADSL',
        2 => 'ADSL2',
        3 => 'ADSL2+',
        4 => 'VDSL',
        5 => 'VDSL2',
        10 => 'HDSL',
        11 => 'PDH',
        12 => 'POTS/ISDN',
        6 => '10 Mb/s Ethernet',
        7 => '100 Mb/s Fast Ethernet',
        8 => '1 Gigabit Ethernet',
        9 => '10 Gigabit Ethernet',
        50 => '(EURO)DOCSIS 1.x',
        51 => '(EURO)DOCSIS 2.x',
        52 => '(EURO)DOCSIS 3.x',
        53 => 'COAXDATA',
        54 => 'ATV',
    ),
    1 => array(
        100 => 'WiFi - 2,4 GHz',
        101 => 'WiFi - 5 GHz',
        102 => 'WiMAX',
        103 => 'LMDS',
        104 => 'radiolinia',
        105 => 'CDMA',
        106 => 'GPRS',
        107 => 'EDGE',
        108 => 'HSPA',
        109 => 'HSPA+',
        110 => 'DC-HSPA+',
        111 => 'MC-HSPA+',
        112 => 'LTE',
        113 => 'UMTS',
        114 => 'DMS',
        115 => 'FWA',
        116 => '5G',
    ),
    2 => array(
        200 => 'CWDM',
        201 => 'DWDM',
        202 => 'SDH',
        203 => '10 Mb/s Ethernet',
        204 => '100 Mb/s Fast Ethernet',
        205 => '1 Gigabit Ethernet',
        206 => '10 Gigabit Ethernet',
        210 => '40 Gigabit Ethernet',
        207 => '100 Gigabit Ethernet',
        208 => 'EPON',
        209 => 'GPON',
        211 => 'ATM',
        212 => 'PDH',
        250 => '(EURO)DOCSIS 1.x',
        251 => '(EURO)DOCSIS 2.x',
        252 => '(EURO)DOCSIS 3.x',
        253 => 'ATV',
    ),
);

$LINKTECHNOLOGIES = array(
    0 => array(
        1 => 'ADSL',
        2 => 'ADSL2',
        3 => 'ADSL2+',
        4 => 'VDSL',
        5 => 'VDSL2',
        13 => 'VDSL2(vectoring)',
        14 => 'G.Fast',
        50 => '(EURO)DOCSIS 1.x',
        51 => '(EURO)DOCSIS 2.x',
        52 => '(EURO)DOCSIS 3.x',
        6 => '10 Mb/s Ethernet',
        7 => '100 Mb/s Fast Ethernet',
        8 => '1 Gigabit Ethernet',
        15 => '2,5 Gigabit Ethernet',
        16 => '5 Gigabit Ethernet',
        9 => '10 Gigabit Ethernet',
        11 => 'SDH/PDH',
        17 => 'MoCA',
        18 => 'EoC',
        53 => 'CoaxData',
    ),
    1 => array(
        117 => 'WiFi – 802.11a w paśmie 5GHz',
        118 => 'WiFi – 802.11b w paśmie 2.4GHz',
        119 => 'WiFi – 802.11g w paśmie 2.4GHz',
        100 => 'WiFi – 802.11n w paśmie 2.4GHz',
        120 => 'WiFi – 802.11n w paśmie 5GHz',
        101 => 'WiFi – 802.11ac w paśmie 5GHz',
        121 => 'WiFi – 802.11ax w paśmie 2.4GHz',
        122 => 'WiFi – 802.11ax w paśmie 5GHz',
        123 => 'WiFi – 802.11ax w paśmie 6GHz',
        124 => 'WiFi – 802.11ad w paśmie 60GHz',
        102 => 'WiMAX',
        103 => 'LMDS',
        104 => 'radiolinia',
        106 => '2G/GSM (w tym GPRS oraz EDGE)',
        //107 => 'EDGE',
        152 => '3G/CDMA2000',
        113 => '3G/UMTS',
        108 => '3G/HSPA',
        109 => '3G/HSPA+',
        150 => '3G/DC-HSPA',
        110 => '3G/DC-HSPA+',
        151 => '3G/MC-HSPA',
        111 => '3G/MC-HSPA+',
        112 => '4G/LTE',
        153 => '4G/LTE-A',
        154 => '4G/LTE-Pro',
        155 => '5G/NR SA',
        156 => '5G/NR NSA',
    ),
    2 => array(
        200 => 'CWDM',
        201 => 'DWDM',
        202 => 'SDH/PDH',
        203 => '10 Mb/s Ethernet',
        204 => '100 Mb/s Fast Ethernet',
        205 => '1 Gigabit Ethernet',
        213 => '2,5 Gigabit Ethernet',
        214 => '5 Gigabit Ethernet',
        206 => '10 Gigabit Ethernet',
        215 => '25 Gigabit Ethernet',
        210 => '40 Gigabit Ethernet',
        207 => '100 Gigabit Ethernet',
        208 => 'EPON',
        216 => '10G-EPON',
        209 => 'GPON',
        219 => 'NGPON1 (XGPON)',
        220 => 'NGPON2 (XGPON)',
        221 => 'XGSPON',
        222 => '25G PON',
        223 => 'MoCA',
        224 => 'EoC',
        250 => '(EURO)DOCSIS 1.x',
        251 => '(EURO)DOCSIS 2.x',
        252 => '(EURO)DOCSIS 3.x',
    ),
);

$allowed_link_technologies = trim(ConfigHelper::getConfig('phpui.allowed_link_technologies', '', true));
if (strlen($allowed_link_technologies)) {
    $allowed_link_technologies = array_filter(
        preg_split("/([\s]+|[\s]*,[\s]*)/", $allowed_link_technologies, -1, PREG_SPLIT_NO_EMPTY),
        function ($link_technology) {
            return ctype_digit($link_technology);
        }
    );
    if (!empty($allowed_link_technologies)) {
        $allowed_link_technologies = array_flip($allowed_link_technologies);
        foreach ($LINKTECHNOLOGIES as $linktype => &$linktechnologies) {
            foreach ($linktechnologies as $linktechnology_idx => $linktechnology) {
                if (!isset($allowed_link_technologies[$linktechnology_idx])) {
                    unset($linktechnologies[$linktechnology_idx]);
                }
            }
            if (empty($linktechnologies)) {
                unset($LINKTECHNOLOGIES[$linktype]);
            }
        }
        unset($linktechnologies);
    }
}

$SIDUSIS_LINKTECHNOLOGIES = array(
    LINKTYPE_WIRE => array(
        1 => 'ADSL',
        2 => 'ADSL2',
        3 => 'ADSL2+',
        4 => 'VDSL',
        5 => 'VDSL2',
        13 => 'VDSL2(vectoring)',
        14 => 'G.Fast',
        50 => '(EURO)DOCSIS 1.x',
        51 => '(EURO)DOCSIS 2.x',
        52 => '(EURO)DOCSIS 3.x',
        6 => '10 Mb/s Ethernet',
        7 => '100 Mb/s Fast Ethernet',
        8 => '1 Gigabit Ethernet',
        15 => '2,5 Gigabit Ethernet',
        16 => '5 Gigabit Ethernet',
        9 => '10 Gigabit Ethernet',
        11 => 'SDH/PDH',
        17 => 'MoCA',
        18 => 'EoC',
    ),
    LINKTYPE_WIRELESS => array(
        112 => 'LTE',
        117 => 'LTE-A',
        118 => 'LTE-Pro',
        119 => 'NR SA',
        120 => 'NR NSA',
        150 => 'inna',
    ),
    LINKTYPE_FIBER => array(
        250 => '(EURO)DOCSIS 1.x',
        251 => '(EURO)DOCSIS 2.x',
        252 => '(EURO)DOCSIS 3.x',
        203 => '10 Mb/s Ethernet',
        204 => '100 Mb/s Fast Ethernet',
        205 => '1 Gigabit Ethernet',
        213 => '2,5 Gigabit Ethernet',
        214 => '5 Gigabit Ethernet',
        206 => '10 Gigabit Ethernet',
        215 => '25 Gigabit Ethernet',
        207 => '100 Gigabit Ethernet',
        217 => 'CWDM',
        218 => 'DWDM',
        212 => 'SDH/PDH',
        208 => 'EPON',
        216 => '10G-EPON',
        209 => 'GPON',
        219 => 'NGPON1 (XGPON)',
        220 => 'NGPON2 (XGPON)',
        221 => 'XGSPON',
        222 => '25G PON',
        223 => 'MoCA',
        224 => 'EoC',
    ),
);

$allowed_link_technologies = trim(ConfigHelper::getConfig(
    'uke.sidusis_allowed_link_technologies',
    ConfigHelper::getConfig(
        'sidusis.allowed_link_technologies',
        ConfigHelper::getConfig(
            'phpui.allowed_link_technologies',
            '',
            true
        ),
        true
    ),
    true
));
if (strlen($allowed_link_technologies)) {
    $allowed_link_technologies = array_filter(
        preg_split("/([\s]+|[\s]*,[\s]*)/", $allowed_link_technologies, -1, PREG_SPLIT_NO_EMPTY),
        function ($link_technology) {
            return ctype_digit($link_technology);
        }
    );
    if (!empty($allowed_link_technologies)) {
        $allowed_link_technologies = array_flip($allowed_link_technologies);
        foreach ($SIDUSIS_LINKTECHNOLOGIES as $linktype => &$linktechnologies) {
            foreach ($linktechnologies as $linktechnology_idx => $linktechnology) {
                if (!isset($allowed_link_technologies[$linktechnology_idx])) {
                    unset($linktechnologies[$linktechnology_idx]);
                }
            }
            if (empty($linktechnologies)) {
                unset($SIDUSIS_LINKTECHNOLOGIES[$linktype]);
            }
        }
        unset($linktechnologies);
    }
}

$LINKSPEEDS = array(
    10000       => trans('10Mbit/s'),
    25000       => trans('25Mbit/s'),
    54000       => trans('54Mbit/s'),
    100000      => trans('100Mbit/s'),
    200000      => trans('200Mbit/s'),
    300000      => trans('300Mbit/s'),
    1000000     => trans('1Gbit/s'),
    10000000    => trans('10Gbit/s'),
    40000000    => trans('40Gbit/s'),
);

$BOROUGHTYPES = array(
    1 => trans('municipal commune'),
    2 => trans('rural commune'),
    3 => trans('municipal-rural commune'),
    4 => trans('city in the municipal-rural commune'),
    5 => trans('rural area to municipal-rural commune'),
    8 => trans('estate in Warsaw-Centre commune'),
    9 => trans('estate'),
);

$PASSWDEXPIRATIONS = array(
    0   => trans('never expires'),
    7   => trans('week'),
    14  => trans('2 weeks'),
    21  => trans('21 days'),
    31  => trans('month'),
    62  => trans('2 months'),
    93  => trans('quarter'),
    183 => trans('half year'),
    365 => trans('year'),
);

$NETELEMENTSTATUSES = array(
    0   => trans('existing'),
    1   => trans('under construction'),
    2   => trans('planned'),
);

$NETELEMENTTYPES = array(
    21  => trans('<!netelemtype>office building'),
    2   => trans('<!netelemtype>residential building'),
    1   => trans('<!netelemtype>industrial building'),
    11  => trans('<!netelemtype>service building'),
    12  => trans('<!netelemtype>public building'),
    3   => trans('<!netelemtype>religious building'),
    13  => trans('<!netelemtype>power grid object'),
    10  => trans('<!netelemtype>chimney'),
    17  => trans('<!netelemtype>cable cabinet'),
    9   => trans('<!netelemtype>well'),
    20  => trans('<!netelemtype>cable joint'),
    8   => trans('<!netelemtype>cable box'),
    6   => trans('<!netelemtype>telecommunication container'),
    19  => trans('<!netelemtype>telecommunication post'),
    18  => trans('<!netelemtype>cable post'),
    7   => trans('<!netelemtype>telecommunication cabinet'),
    16  => trans('<!netelemtype>cable connector'),
    15  => trans('<!netelemtype>lighting mast'),
    4   => trans('<!netelemtype>telecommunication mast'),
    14  => trans('<!netelemtype>pole'),
    5   => trans('<!netelemtype>telecommunication tower'),
);

$NETELEMENTTYPEGROUPS = array(
    trans('building objects (SIIS)') => array(
        21 => true,
        2 => true,
        1 => true,
        11 => true,
        12 => true,
        3 => true,
        13 => true,
        10 => true,
    ),
    trans('infrastructure elements (PIT)') => array(
        17 => true,
        9 => true,
        20 => true,
        8 => true,
        6 => true,
        19 => true,
        18 => true,
        7 => true,
        16 => true,
        15 => true,
        4 => true,
        14 => true,
        5 => true,
    ),
);

$NETWORK_DUCT_TYPES = array(
    1 => trans('underground (placed directly in the ground)'),
    2 => trans('placed in cable ducts (including cable pipeline, microducts)'),
    3 => trans('placed in the technological channel'),
    4 => trans('above-ground on telecommunication pole foundation'),
    5 => trans('overground on power, lighting or traction foundation'),
);

$NETELEMENTOWNERSHIPS = array(
    0   => trans('Own node'),
    1   => trans('Node shared with another entity'),
    2   => trans('Foreign node'),
);

const USERPANEL_AUTH_TYPE_ID_PIN = 1,
    USERPANEL_AUTH_TYPE_PHONE_PIN = 2,
    USERPANEL_AUTH_TYPE_DOCUMENT_PIN = 3,
    USERPANEL_AUTH_TYPE_EMAIL_PIN = 4,
    USERPANEL_AUTH_TYPE_PPPOE_LOGIN_PASSWORD = 5,
    USERPANEL_AUTH_TYPE_TEN_SSN_PIN = 6,
    USERPANEL_AUTH_TYPE_EXTID_PIN = 7;

$USERPANEL_AUTH_TYPES = array(
    USERPANEL_AUTH_TYPE_ID_PIN => array(
        'label' => trans('Customer ID'),
        'label_secret' => trans('PIN'),
        'selection' => trans('Customer ID and PIN'),
    ),
    USERPANEL_AUTH_TYPE_PHONE_PIN => array(
        'label' => trans('Phone number'),
        'label_secret' => trans('PIN'),
        'selection' => trans('Phone number and PIN'),
    ),
    USERPANEL_AUTH_TYPE_DOCUMENT_PIN => array(
        'label' => trans('Document number'),
        'label_secret' => trans('PIN'),
        'selection' => trans('Document number and PIN'),
    ),
    USERPANEL_AUTH_TYPE_EMAIL_PIN => array(
        'label' => trans('Customer e-mail'),
        'label_secret' => trans('PIN'),
        'selection' => trans('Customer e-mail and PIN'),
    ),
    USERPANEL_AUTH_TYPE_PPPOE_LOGIN_PASSWORD => array(
        'label' => trans('PPPoE login'),
        'label_secret' => trans('PPPoE password'),
        'selection' => trans('PPPoE login and password'),
    ),
    USERPANEL_AUTH_TYPE_TEN_SSN_PIN => array(
        'label' => trans('SSN/TEN'),
        'label_secret' => trans('PIN'),
        'selection' => trans('SSN/TEN and PIN'),
    ),
    USERPANEL_AUTH_TYPE_EXTID_PIN => array(
        'label' => trans('Customer External ID/PIN'),
        'label_secret' => trans('PIN'),
        'selection' => trans('Customer External ID and PIN'),
        'options-label' => trans('Customer External ID/PIN authentication options'),
        'options' => array(
            array(
                'type' => 'single-select',
                'name' => 'authentication_customer_extid_service_provider_id',
                'label' => trans('Service provider'),
                'getter' => function () {
                    $DB = LMSDB::getInstance();
                    $options = array(
                        array(
                            'id' => '',
                            'label' => trans('<!service-provider>— default —'),
                        ),
                    );
                    $db_options = $DB->GetAll(
                        'SELECT
                            id,
                            name AS label
                        FROM serviceproviders
                        ORDER BY name'
                    );
                    if (!empty($db_options)) {
                        $options = array_merge($options, $db_options);
                    }
                    return $options;
                }
            ),
        ),
    ),
);

const EVENT_OTHER = 1,
    EVENT_NETWORK = 2,
    EVENT_SERVICE = 3,
    EVENT_INSTALLATION = 4,
    EVENT_MEETING = 5,
    EVENT_VACATION = 6,
    EVENT_DUTY = 7,
    EVENT_PHONE = 8,
    EVENT_TV = 9,
    EVENT_TECHNICAL_VERIFICATION = 10,
    EVENT_REMINDER = 11;

$EVENTTYPES = array(
    EVENT_OTHER => array(
        'label' => 'other',
        'style' => 'background-color: gray; color: white;',
        'alias' => 'other',
    ),
    EVENT_NETWORK => array(
        'label' => 'network',
        'style' => 'background-color: blue; color: white;',
        'alias' => 'network',
    ),
    EVENT_SERVICE => array(
        'label' => 'service<!event>',
        'style' => 'background-color: red; color: white;',
        'alias' => 'service',
    ),
    EVENT_INSTALLATION => array(
        'label' => 'installation',
        'style' => 'background-color: green; color: white;',
        'alias'=> 'installation',
    ),
    EVENT_MEETING => array(
        'label' => 'meeting',
        'style' => 'background-color: gold; color: black;',
        'alias' => 'meeting',
    ),
    EVENT_VACATION => array(
        'label' => 'vacation',
        'style' => 'background-color: white; color: black;',
        'alias' => 'vacation',
    ),
    EVENT_DUTY => array(
        'label' => 'duty',
        'style' => 'background-color: brown; color: white;',
        'alias' => 'duty',
    ),
    EVENT_PHONE => array(
        'label' => 'phone',
        'style' => 'background-color: yellow; color: black;',
        'alias' => 'phone',
    ),
    EVENT_TV => array(
        'label' => 'tv',
        'style' => 'background-color: greenyellow; color: blue;',
        'alias' => 'tv',
    ),
    EVENT_TECHNICAL_VERIFICATION => array(
        'label' => 'technical verification',
        'style' => 'background-color: #30D5C8; color: black;',
        'alias' => 'technical_verification',
    ),
    EVENT_REMINDER => array(
        'label' => 'reminder',
        'style' => 'background-color: #FF66FF; color: black;',
        'alias' => 'reminder',
    ),
);

const SESSIONTYPE_PPPOE = 1,
    SESSIONTYPE_DHCP = 2,
    SESSIONTYPE_EAP = 4,
    SESSIONTYPE_WIFI = 8,
    SESSIONTYPE_VOIP = 16,
    SESSIONTYPE_STB = 32,
    SESSIONTYPE_DOCSIS = 64,
    SESSIONTYPE_LLU = 128,
    SESSIONTYPE_BSA = 256,
    SESSIONTYPE_VPN = 512;

$SESSIONTYPES = array(
    SESSIONTYPE_PPPOE => array(
        'label' => trans('PPPoE Client'),
        'tip' => 'Enable/disable PPPoE Server Client',
        'alias' => 'pppoe',
    ),
    SESSIONTYPE_DHCP => array(
        'label' => trans('DHCP Client'),
        'tip' => 'Enable/disable DHCP Server Client',
        'alias' => 'dhcp',
    ),
    SESSIONTYPE_EAP => array(
        'label' => trans('EAP Client'),
        'tip' => 'Enable/disable EAP Server Client',
        'alias' => 'eap',
    ),
    SESSIONTYPE_WIFI => array(
        'label' => trans('WiFi AP Client'),
        'tip' => 'Enable/disable WiFi AP Client access',
        'alias' => 'ap-client',
    ),
    SESSIONTYPE_VOIP => array(
        'label' => trans('VoIP Gateway'),
        'tip' => 'Enable/disable VoIP Gateway access',
        'alias' => 'voip',
    ),
    SESSIONTYPE_STB => array(
        'label' => trans('Set-top box'),
        'tip' => 'Enable/disable set-top box access',
        'alias' => 'stb',
    ),
    SESSIONTYPE_DOCSIS => array(
        'label' => trans('DOCSIS access'),
        'tip' => 'Enable/disable DOCSIS access',
        'alias' => 'docsis',
    ),
    SESSIONTYPE_LLU => array(
        'label' => trans('LLU service'),
        'tip' => 'Mark as LLU service',
        'alias' => 'llu',
    ),
    SESSIONTYPE_BSA => array(
        'label' => trans('BSA service'),
        'tip' => 'Mark as BSA service',
        'alias' => 'bsa',
    ),
    SESSIONTYPE_VPN => array(
        'label' => trans('VPN access'),
        'tip' => 'Mark as VPN access',
        'alias' => 'vpn',
    ),
);

const EXISTINGASSIGNMENT_KEEP = 0,
    EXISTINGASSIGNMENT_SUSPEND = 1,
    EXISTINGASSIGNMENT_CUT = 2,
    EXISTINGASSIGNMENT_DELETE = 3;

$EXISTINGASSIGNMENTS = array(
    EXISTINGASSIGNMENT_KEEP => trans('<!existingassignment>keep'),
    EXISTINGASSIGNMENT_SUSPEND => trans('<!existingassignment>suspend'),
    EXISTINGASSIGNMENT_CUT => trans('<!existingassignment>cut'),
    EXISTINGASSIGNMENT_DELETE => trans('<!existingassignment>delete'),
);

$CURRENCIES = array(
    'AUD' => 'AUD',
    'BGN' => 'BGN',
    'BRL' => 'BRL',
    'CAD' => 'CAD',
    'CHF' => 'CHF',
    'CLP' => 'CLP',
    'CNY' => 'CNY',
    'CZK' => 'CZK',
    'DKK' => 'DKK',
    'EUR' => 'EUR',
    'GBP' => 'GBP',
    'GYD' => 'GYD',
    'HKD' => 'HKO',
    'HRK' => 'HRK',
    'HUF' => 'HUF',
    'IDR' => 'IDR',
    'ILS' => 'ILS',
    'INR' => 'INR',
    'ISK' => 'ISK',
    'JPY' => 'JPY',
    'KRW' => 'KRW',
    'LTL' => 'LTL',
    'LVL' => 'LVL',
    'MXN' => 'MXN',
    'MYR' => 'MYR',
    'NOK' => 'NOK',
    'NZD' => 'NZD',
    'PHP' => 'PHP',
    'PLN' => 'PLN',
    'RON' => 'RON',
    'RUB' => 'RUB',
    'SEK' => 'SEK',
    'SGD' => 'SGD',
    'THB' => 'THB',
    'TRY' => 'TRY',
    'UAH' => 'UAH',
    'USD' => 'USD',
    'XDR' => 'XOR',
    'ZAR' => 'ZAR',
);

$TAX_CATEGORIES = array(
    1 => array(
        'label' => 'napój alkoholowy',
        'description' => 'Dostawa napojów alkoholowych - alkoholu etylowego, piwa, wina, napojów fermentowanych i wyrobów pośrednich, w rozumieniu przepisów o podatku akcyzowym',
    ),
    2 => array(
        'label' => 'paliwo',
        'description' => 'Dostawa towarów, o których mowa w art. 103 ust. 5aa ustawy',
    ),
    3 => array(
        'label' => 'olej opałowy',
        'description' => 'Dostawa oleju opałowego w rozumieniu przepisów o podatku akcyzowym oraz olejów smarowych, pozostałych olejów o kodach CN od 2710 19 71 do 2710 19 99, z wyłączeniem wyrobów o kodzie CN 2710 19 85 (oleje białe, parafina ciekła) oraz smarów plastycznych zaliczanych do kodu CN 2710 19 99, olejów smarowych o kodzie CN 2710 20 90, preparatów smarowych objętych pozycją CN 3403, z wyłączeniem smarów plastycznych objętych tą pozycją',
    ),
    4 => array(
        'label' => 'wyrób nikotynowy',
        'description' => 'Dostawa wyrobów tytoniowych, suszu tytoniowego, płynu do papierosów elektronicznych i wyrobów nowatorskich, w rozumieniu przepisów o podatku akcyzowym',
    ),
    5 => array(
        'label' => 'odpad',
        'description' => 'Dostawa odpadów - wyłącznie określonych w poz. 79-91 załącznika nr 15 do ustawy',
    ),
    6 => array(
        'label' => 'urządzenie elektroniczne',
        'description' => 'Dostawa urządzeń elektronicznych oraz części i materiałów do nich, wyłącznie określonych w poz. 7-9, 59-63, 65, 66, 69 i 94-96 załącznika nr 15 do ustawy',
    ),
    7 => array(
        'label' => 'pojazd samochodowy',
        'description' => 'Dostawa pojazdów oraz części samochodowych o kodach wyłącznie CN 8701 - 8708 oraz CN 8708 10',
    ),
    8 => array(
        'label' => 'metal szlachetny lub nieszlachetny',
        'description' => 'Dostawa metali szlachetnych oraz nieszlachetnych - wyłącznie określonych w poz. 1-3 załącznika nr 12 do ustawy oraz w poz. 12-25, 33-40, 45, 46, 56 i 78 załącznika nr 15 do ustawy',
    ),
    9 => array(
        'label' => 'lek lub wybór medyczny',
        'description' => 'Dostawa leków oraz wyrobów medycznych - produktów leczniczych, środków spożywczych specjalnego przeznaczenia żywieniowego oraz wyrobów medycznych, objętych obowiązkiem zgłoszenia, o którym mowa w art. 37av ust. 1 ustawy z dnia 6 września 2001 r. - Prawo farmaceutyczne (Dz. U. z 2019 r. poz. 499, z późn. zm.)',
    ),
    10 => array(
        'label' => 'budynek lub grunt',
        'description' => 'Dostawa budynków, budowli i gruntów',
    ),
    11 => array(
        'label' => 'usługa związana z gazami cieplarnianymi',
        'description' => 'Świadczenie usług w zakresie przenoszenia uprawnień do emisji gazów cieplarnianych, o których mowa w ustawie z dnia 12 czerwca 2015 r. o systemie handlu uprawnieniami do emisji gazów cieplarnianych (Dz. U. z 2018 r. poz. 1201 i 2538 oraz z 2019 r. poz. 730, 1501 i 1532)',
    ),
    12 => array(
        'label' => 'usługa o charakterze niematerialnym',
        'description' => 'Świadczenie usług o charakterze niematerialnym - wyłącznie: doradczych, księgowych, prawnych, zarządczych, szkoleniowych, marketingowych, firm centralnych (head offices), reklamowych, badania rynku i opinii publicznej, w zakresie badań naukowych i prac rozwojowych',
    ),
    13 => array(
        'label' => 'usługa transportowa lub gospodarki magazynowej',
        'description' => 'Świadczenie usług transportowych i gospodarki magazynowej - Sekcja H PKWiU 2015 symbol ex 49.4, ex 52.1',
    ),
);

// Identity types
$IDENTITY_TYPES = array(
    1   => 'ID card',
    2   => 'driving license',
    3   => 'passport',
    4   => 'residence card',
    5   => 'permanent residence card',
);

if (isset($SMARTY)) {
    $SMARTY->assign(
        array(
            '_NETWORK_NODE_FLAGS' => $NETWORK_NODE_FLAGS,
            '_NETWORK_NODE_SERVICES' => $NETWORK_NODE_SERVICES,
            '_NETWORK_INTERFACE_TYPES' => $NETWORK_INTERFACE_TYPES,
            '_CTYPES' => $CTYPES,
            '_CSTATUSES' => $CSTATUSES,
            '_CUSTOMERFLAGS' => $CUSTOMERFLAGS,
            '_CCONSENTS' => $CCONSENTS,
            '_CCONSENT_GROUPS' => $CCONSENT_GROUPS,
            '_ORIGINS' => $ORIGINS,
            '_MESSAGESTATUSES' => $MESSAGESTATUSES,
            '_MESSAGETEMPLATES' => $MESSAGETEMPLATES,
            '_ACCOUNTTYPES' => $ACCOUNTTYPES,
            '_DOCTYPES' => $DOCTYPES,
            '_DOCENTITIES' => $DOCENTITIES,
            '_DOCRIGHTS' => $DOCRIGHTS,
            '_PERIODS' => $PERIODS,
            '_GUARANTEEPERIODS' => $GUARANTEEPERIODS,
            '_NUM_PERIODS' => $NUM_PERIODS,
            '_RT_RIGHTS' => $RT_RIGHTS,
            '_RT_STATES' => $RT_STATES,
            '_RT_SOURCES' => $RT_SOURCES,
            '_RT_PRIORITIES' => $RT_PRIORITIES,
            '_RT_PRIORITY_STYLES' => $RT_PRIORITY_STYLES,
            '_RT_TYPES' => $RT_TYPES,
            '_CONFIG_TYPES' => $CONFIG_TYPES,
            '_TARIFF_FLAGS' => $TARIFF_FLAGS,
            '_SERVICETYPES' => $SERVICETYPES,
            '_PAYTYPES' => $PAYTYPES,
            '_CONTACTTYPES' => $CONTACTTYPES,
            '_DISCOUNTTYPES' => $DISCOUNTTYPES,
            '_DAYS' => $DAYS,
            '_LINKTYPES' => $LINKTYPES,
            '_LINKTECHNOLOGIES' => $LINKTECHNOLOGIES,
            '_LINKSPEEDS' => $LINKSPEEDS,
            '_BOROUGHTYPES' => $BOROUGHTYPES,
            '_PASSWDEXPIRATIONS' => $PASSWDEXPIRATIONS,
            '_NETELEMENTSTATUSES' => $NETELEMENTSTATUSES,
            '_NETELEMENTTYPES' => $NETELEMENTTYPES,
            '_NETELEMENTTYPEGROUPS' => $NETELEMENTTYPEGROUPS,
            '_NETWORK_DUCT_TYPES' => $NETWORK_DUCT_TYPES,
            '_NETELEMENTOWNERSHIPS' => $NETELEMENTOWNERSHIPS,
            '_USERPANEL_AUTH_TYPES' => $USERPANEL_AUTH_TYPES,
            '_SESSIONTYPES' => $SESSIONTYPES,
            '_EXISTINGASSIGNMENTS' => $EXISTINGASSIGNMENTS,
            '_CURRENCIES' => $CURRENCIES,
            '_TAX_CATEGORIES' => $TAX_CATEGORIES,
            '_IDENTITY_TYPES' => $IDENTITY_TYPES,
        )
    );
    $SMARTY->assignByRef('_EVENTTYPES', $EVENTTYPES);
}

const DEFAULT_NUMBER_TEMPLATE = '%N/LMS/%Y';

// Investment project types
const INV_PROJECT_REGULAR = 0,
    INV_PROJECT_SYSTEM = 1;

// Address types
const POSTAL_ADDRESS = 0,
    BILLING_ADDRESS = 1,
    LOCATION_ADDRESS = 2,
    DEFAULT_LOCATION_ADDRESS = 3,
    RECIPIENT_ADDRESS = 4;
