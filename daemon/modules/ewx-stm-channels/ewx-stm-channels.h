#include <net-snmp/net-snmp-config.h>
#include <net-snmp/net-snmp-includes.h>

struct ewx_module
{
	MODULE base;

	char *networks;
	char *excluded_networks;
	char *dummy_mac_networks;
	char *dummy_ip_networks;
	char *excluded_dummy_mac_networks;
	char *excluded_dummy_ip_networks;
	char *customergroups;
	char *community;
	char *host;
	char *night_hours;

	u_short port;
	int path;
	int offset;
	int skip_disabled;
	int default_upceil;
	int default_downceil;
	int default_halfduplex;
};

struct net
{
	unsigned long address;
	unsigned long mask;
	char *name;
};

struct host
{
    int id;
	int status;
	int uprate;
	int upceil;
	int downrate;
	int downceil;
	int halfduplex;
	char *ip;
	char *mac;
};

struct channel
{
	int id;
	int cid;
	int status;
	int upceil;
	int downceil;
	int upratesum;
	int downratesum;
	int halfduplex;
    int no;
	struct host *hosts;
};

/*  
    EtherWerX Layer2 Traffic Manager MIB tree:

    ArcherMIB = 20656
    ArcherProducts = 1
    ArcherProductSpecificMibs = 2
    EtherWerX-L2tm    1

    L2tmCustomersTable 	4
    L2tmCustomer 	1	(L2tmCustomersTableSave 	2)
    L2tmCustomerStatus  		1       RowStatus
    L2tmCustomerNo      		2       Unsigned32
    L2tmCustomerPathNo  		3       Unsigned32
    L2tmCustomerChannelNo		10      Unsigned32
    L2tmCustomerIpAddr			4	DisplayString
    L2tmCustomerMacAddr 		5       DisplayString
    L2tmCustomerMinSpeed		6       Unsigned32	(kbit/s)
    L2tmCustomerMaxSpeed        	7	Unsigned32	(kbit/s)
    L2tmCustomerUplinkMinSpeed  	11	Unsigned32	(kbit/s)
    L2tmCustomerUplinkMaxSpeed  	12	Unsigned32	(kbit/s)
    L2tmCustomerDownlinkMinSpeed 	13	Unsigned32	(kbit/s)
    L2tmCustomerDownlinkMaxSpeed 	14	Unsigned32	(kbit/s)
    L2tmCustomerShare            	8	Unsigned32
    L2tmCustomerHalfDuplex       	9	INTEGER

    L2tmChannelsTable 	6
    L2tmChannel		1	(L2tmChannelsTableSave	2)
    L2tmChannelStatus   	1       RowStatus
    L2tmChannelNo               2	Unsigned32
    L2tmChannelPathNo		3	Unsigned32
    L2tmChannelUplink           4	Unsigned32
    L2tmChannelDownlink         5	Unsigned32
    L2tmChannelHalfDuplex       6	INTEGER

*/

// nodes OIDs (last element (zero) will be changed to node ID)
oid CustomerStatus[]		= {SNMP_OID_ENTERPRISES,20656,1,2,1,4,1,1,0};
oid CustomerNo[] 		= {SNMP_OID_ENTERPRISES,20656,1,2,1,4,1,2,0};
oid CustomerPathNo[] 		= {SNMP_OID_ENTERPRISES,20656,1,2,1,4,1,3,0};
oid CustomerChannelNo[] 	= {SNMP_OID_ENTERPRISES,20656,1,2,1,4,1,10,0};
oid CustomerIpAddr[] 		= {SNMP_OID_ENTERPRISES,20656,1,2,1,4,1,4,0};
oid CustomerMacAddr[]		= {SNMP_OID_ENTERPRISES,20656,1,2,1,4,1,5,0};
oid CustomerMinSpeed[] 		= {SNMP_OID_ENTERPRISES,20656,1,2,1,4,1,6,0};
oid CustomerMaxSpeed[]		= {SNMP_OID_ENTERPRISES,20656,1,2,1,4,1,7,0};
oid CustomerUpMinSpeed[] 	= {SNMP_OID_ENTERPRISES,20656,1,2,1,4,1,11,0};
oid CustomerUpMaxSpeed[]	= {SNMP_OID_ENTERPRISES,20656,1,2,1,4,1,12,0};
oid CustomerDownMinSpeed[] 	= {SNMP_OID_ENTERPRISES,20656,1,2,1,4,1,13,0};
oid CustomerDownMaxSpeed[]	= {SNMP_OID_ENTERPRISES,20656,1,2,1,4,1,14,0};
oid CustomerShare[] 		= {SNMP_OID_ENTERPRISES,20656,1,2,1,4,1,8,0};
oid CustomerHalfDuplex[]	= {SNMP_OID_ENTERPRISES,20656,1,2,1,4,1,9,0};

// channels OIDs (last element (zero) will be changed to channel ID)
oid ChannelStatus[]		= {SNMP_OID_ENTERPRISES,20656,1,2,1,6,1,1,0};
oid ChannelNo[] 		= {SNMP_OID_ENTERPRISES,20656,1,2,1,6,1,2,0};
oid ChannelPathNo[] 		= {SNMP_OID_ENTERPRISES,20656,1,2,1,6,1,3,0};
oid ChannelUplink[] 		= {SNMP_OID_ENTERPRISES,20656,1,2,1,6,1,4,0};
oid ChannelDownlink[] 		= {SNMP_OID_ENTERPRISES,20656,1,2,1,6,1,5,0};
oid ChannelHalfDuplex[] 	= {SNMP_OID_ENTERPRISES,20656,1,2,1,6,1,6,0};

// paths OIDs (last element (zero) will be changed to path ID)
oid PathUplink[]		= {SNMP_OID_ENTERPRISES,20656,1,2,1,2,1,3,0};
oid PathDownlink[]		= {SNMP_OID_ENTERPRISES,20656,1,2,1,2,1,4,0};

// Licences
oid MaxCustomers[]		= {SNMP_OID_ENTERPRISES,20656,1,2,1,1,2,4,0};
oid MaxChannels[]		= {SNMP_OID_ENTERPRISES,20656,1,2,1,1,2,9,0};

// number of elements in above OIDs
#define STM_OID_LEN	14

// Status values
#define ACTIVE		"1"
#define NOTINSERVICE	"2"
#define NOTREADY	"3"
#define CREATEANDGO	"4"
#define CREATEANDWAIT	"5"
#define DESTROY		"6"
#define TABLESAVE	"2"
// HalfDuplex field values
#define HALFDUPLEX	"1"
#define FULLDUPLEX	"2"

// Customers/Channels tables status
oid CustomersTableSave[]	= {SNMP_OID_ENTERPRISES,20656,1,2,1,4,2,0};
oid ChannelsTableSave[]		= {SNMP_OID_ENTERPRISES,20656,1,2,1,6,2,0};

// dummy MAC for nodes with disabled hardware address checking
#define DUMMY_MAC 	"00:00:00:00:00:00"
#define DUMMY_IP 	"0.0.0.0"

// we don't need to load MIB definitions
#define DISABLE_MIB_LOADING 1

// max. channel ID
#define MAX_ID		99999

// some helper constants
#define UNKNOWN		0
#define STATUS_OK	1
#define STATUS_ERROR	0
#define DELETED		2

//#define LMS_SNMP_DEBUG 1
