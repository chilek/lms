struct net
{
	char *name;
	char *interface;
	unsigned long address;
	unsigned long mask;
};

struct node
{
        int id;
	int network;
	char *ip;
	char *name;
	char *mac;
};

struct channel
{
        int id;
	int uprate;
	int upceil;
	int downrate;
	int downceil;
	int climit;
	int plimit;
	// nodes
	int no;
	struct node *nodes;
	// assignments
	int subno;
	int *subs;
	// customer
	int cid;
	char *customer;
};

struct tc_module
{
	MODULE base;

	char *file;
	char *command;
	char *begin;
	char *end;
	char *networks;
	char *customergroups;

	char *class_up;
	char *class_down;

	char *filter_up;
	char *filter_down;

	char *plimit;
	char *climit;
};

#define XVALUE	100
