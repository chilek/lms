struct net
{
	unsigned char *name;
	unsigned char *domain;
	unsigned char *interface;
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
	unsigned char *file;
	unsigned char *command;
	unsigned char *networks;
	unsigned char *groups;

	int skip_dev_ips;
};
