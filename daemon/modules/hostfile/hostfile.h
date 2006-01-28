struct net
{
	char *name;
	char *domain;
	char *interface;
	char *gateway;
	char *dns;
	char *dns2;
	char *wins;
	unsigned long address;
	unsigned long mask;
};

struct group
{
	char *name;
	int id;
};

struct hostfile_module
{
	MODULE base;

	char *prefix;
	char *append;
	char *grant;
	char *deny;
	char *grant_pub;
	char *deny_pub;
	char *file;
	char *command;
	char *networks;
	char *customergroups;

	int skip_dev_ips;
};
