struct net
{
	char *name;
	char *domain;
	char *interface;
	unsigned long address;
	unsigned long mask;
};

struct group
{
	char *name;
	int id;
};

struct shaper_module
{
	MODULE base;

	char *file;
	char *command;
	char *begin;
	char *end;
	char *host_htb;
	char *networks;
	char *customergroups;
	int one_class_per_host;
};
