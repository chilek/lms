
struct dhcp_module
{
	MODULE base;

	char *prefix;
	char *append;
	char *subnetstart;
	char *subnetend;
	char *gateline;
	char *dnsline;
	char *domainline;
	char *winsline;
	char *rangeline;
	char *host;
	char *file;
	char *command;
	char *networks;
	char *customergroups;
};

struct net
{
	char *name;
	unsigned long address;
};

struct group
{
	char *name;
	int id;
};

#define MAXIFN 16
