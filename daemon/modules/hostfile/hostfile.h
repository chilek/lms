
struct hostfile_module
{
	MODULE base;

	unsigned char *prefix;
	unsigned char *append;
	unsigned char *grant;
	unsigned char *deny;
	
	int skip_dev_ips;
	int netcount;
	struct hosts_net
	{
		unsigned long network;
		unsigned long netmask;
	} *networks;
	
	unsigned char *file;
	unsigned char *command;
};
