struct net
{
	unsigned long address;
	unsigned long mask;
};	

struct oident_module
{
	MODULE base;

	unsigned char *prefix;
	unsigned char *append;
	unsigned char *host;
	unsigned char *file;
	unsigned char *command;
	unsigned char *networks;
	
	int netcount;
};
