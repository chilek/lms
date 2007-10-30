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

struct tc_module
{
	MODULE base;

	char *file;
	char *command;
	char *begin;
	char *end;
	char *host_mark_up;
	char *host_mark_down;
	char *host_htb_up;
	char *host_htb_down;
	char *host_plimit;
	char *host_climit;
	char *networks;
	char *customergroups;
	int one_class_per_host;
	int limit_per_host;
};
