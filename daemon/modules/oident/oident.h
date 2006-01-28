struct net
{
	unsigned long address;
	unsigned long mask;
};	

struct oident_module
{
	MODULE base;

	char *prefix;
	char *append;
	char *host;
	char *file;
	char *command;
	char *networks;
	
	int netcount;
};
