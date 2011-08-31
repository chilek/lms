#include <net-snmp/net-snmp-config.h>
#include <net-snmp/net-snmp-includes.h>

struct ewx_module
{
	MODULE base;

	char * community;
	char * host;
	char * networks;
	char * dummy_mac_networks;
	char * dummy_ip_networks;
	u_short port;
	int offset;
	int skip_disabled;
	int skip_noa;
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
    int nodeid;
    int status;
    unsigned long ipaddr;
    char *mac;
    char *name;
    char *ip;
    char *passwd;
};

/*  
    EtherWerX-PT MIB tree:

    ArcherMIB = 20656
    ArcherProducts = 1
    ArcherProductSpecificMibs = 2
    EtherWerX-PPPoE = 2
    PppoeUsersTable = 2
    PppoeUser = 1
*/

// PppoeUser's OIDs (last element (zero) will be changed to node ID)
oid UserStatus[]		= {SNMP_OID_ENTERPRISES,20656,1,2,2,2,1,1,0};
oid UserNo[] 			= {SNMP_OID_ENTERPRISES,20656,1,2,2,2,1,2,0};
oid UserName[] 			= {SNMP_OID_ENTERPRISES,20656,1,2,2,2,1,3,0};
oid UserPassword[] 		= {SNMP_OID_ENTERPRISES,20656,1,2,2,2,1,4,0};
oid UserIpAddr[] 		= {SNMP_OID_ENTERPRISES,20656,1,2,2,2,1,5,0};
oid UserAllowedMacAddr[]	= {SNMP_OID_ENTERPRISES,20656,1,2,2,2,1,6,0};

// number of elements in above OIDs
#define PT_OID_LEN	14

// UsersTableSave
oid UsersTableSave[]		= {SNMP_OID_ENTERPRISES,20656,1,2,2,2,2,0};

// Status values
#define ACTIVE		"1"
#define NOTINSERVICE	"2"
#define NOTREADY	"3"
#define CREATEANDGO	"4"
#define CREATEANDWAIT	"5"
#define DESTROY		"6"

#define TABLESAVE	"2"

// dummy MAC/IP for nodes with disabled address checking
#define DUMMY_MAC 	"00:00:00:00:00:00"
#define DUMMY_IP	"0.0.0.0"

// we don't need to load MIB definitions
#define DISABLE_MIB_LOADING 1

// some helper constants
#define UNKNOWN     0
#define STATUS_OK   1
#define DELETED     2
#define STATUS_ERROR    0

//#define LMS_SNMP_DEBUG 1
