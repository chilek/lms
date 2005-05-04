struct net
{
	unsigned char *name;
	unsigned char *domain;
	unsigned char *interface;
	unsigned char *gateway;
	unsigned long address;
	unsigned long mask;
};

struct group
{
	unsigned char *name;
	int id;
};

struct hostfile_module
{
	MODULE base;

	unsigned char *prefix;
	unsigned char *append;
	unsigned char *grant;
	unsigned char *deny;
	unsigned char *grant_pub;
	unsigned char *deny_pub;
	unsigned char *file;
	unsigned char *command;
	unsigned char *networks;
	unsigned char *usergroups;

	int skip_dev_ips;
};
