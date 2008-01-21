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

struct node
{
        int id;
	int uprate;
	int upceil;
	int downrate;
	int downceil;
	int climit;
	int plimit;
	int network;
	char *ip;
	char *name;
	char *mac;
};

struct customer
{
        int id;
	int no;
	struct node *nodes;
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
};

#define XVALUE	100
