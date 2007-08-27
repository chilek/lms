
struct ethers_module
{
	MODULE base;
	char *file;
	char *command;
	char *networks;
	char *customergroups;

	int dummy_macs;
};

struct net
{
	char *name;
	unsigned long address;
	unsigned long mask;
};

struct group
{
	char *name;
	int id;
};
