
struct oident_module
{
	MODULE base;

	unsigned char *prefix;
	unsigned char *append;
	
	unsigned char *host;

	unsigned char *file;
	unsigned char *command;
	
	struct oident_net
	{
		unsigned long network;
		unsigned long netmask;
	} *networks;
	
	int netcount;
};
