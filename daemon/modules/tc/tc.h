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

struct tc_module
{
	MODULE base;

	unsigned char *file;
	unsigned char *command;
	unsigned char *begin;
	unsigned char *end;
	unsigned char *host_mark_up;
	unsigned char *host_mark_down;
	unsigned char *host_htb_up;
	unsigned char *host_htb_down;
	unsigned char *host_plimit;
	unsigned char *host_climit;
	unsigned char *networks;
	unsigned char *customergroups;
	int one_class_per_host;
};
