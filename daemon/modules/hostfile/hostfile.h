struct net
{
	unsigned char *name;
	unsigned char *domain;
	unsigned long address;
	unsigned long mask;
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

	int skip_dev_ips;
};
