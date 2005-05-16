
struct dhcp_module
{
	MODULE base;

	unsigned char *prefix;
	unsigned char *append;
	unsigned char *subnetstart;
	unsigned char *subnetend;
	unsigned char *gateline;
	unsigned char *dnsline;
	unsigned char *domainline;
	unsigned char *winsline;
	unsigned char *rangeline;
	unsigned char *host;
	unsigned char *file;
	unsigned char *command;
	unsigned char *networks;
	unsigned char *usergroups;
};

struct net
{
	unsigned char *name;
	unsigned long address;
};

struct group
{
	unsigned char *name;
	int id;
};

#define MAXIFN 16
