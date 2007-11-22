#include <syslog.h>
#include <sys/ioctl.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <features.h>    /* for the glibc version number */
#if __GLIBC__ >= 2 && __GLIBC_MINOR__ >= 1
#include <net/if.h>
#include <net/if_arp.h>
#include <netpacket/packet.h>
#include <net/ethernet.h>     /* the L2 protocols */
#else
#include <asm/types.h>
#include <linux/if_arp.h>
#include <linux/if_ether.h>   /* the L2 protocols */
#endif
#include <errno.h>
#include <stdlib.h>
#include <signal.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <sys/time.h>

#define KB 1024
#define MAXIFN 1024

struct net
{
	unsigned long address;
	unsigned long mask;
};

struct host
{
	char *id;
	unsigned long ipaddr;
	int active;
};

struct pinger_module
{
	MODULE base;

	char *networks;
	int use_secure_function;
};

struct if_desc 
{
	int index;
	unsigned int ip;
	unsigned int netmask;
	unsigned char mac[6];
	unsigned int network;
};

/*
struct ifconf 
{
           int ifc_len;    //size of buffer
           union {
                   char *  ifc_buf; 		// buffer address
                   struct ifreq *ifc_req; 	// array of structures
           };
};

    When  you  send  packets  it is enough to specify sll_family, sll_addr,
    sll_halen, sll_ifindex.  The other fields should be 0.  sll_hatype  and
    sll_pkttype are set on received packets for your information.  For bind
    only sll_protocol and sll_ifindex are used.

struct arphdr
{
	unsigned short	ar_hrd;			// format of hardware address
	unsigned short	ar_pro;			// format of protocol address
	unsigned char	ar_hln;			// length of hardware address
	unsigned char	ar_pln;			// length of protocol address
	unsigned short	ar_op;			// ARP opcode (command)
#if 0
	 // 	 Ethernet looks like this : This bit is variable sized however...
	 
	unsigned char	ar_sha[ETH_ALEN];	// sender hardware address
	unsigned char	ar_sip[4];		// sender IP address
	unsigned char	ar_tha[ETH_ALEN];	// target hardware address
	unsigned char	ar_tip[4];		// target IP address
#endif
};

struct ethhdr
{
	unsigned char	h_dest[ETH_ALEN];	// destination eth addr
	unsigned char	h_source[ETH_ALEN];	// source ether addr
	unsigned short	h_proto;		// packet type ID field
};
*/
