
struct ethers_module
{
	MODULE base;
	unsigned char *file;
	unsigned char *command;
	unsigned char *networks;
	unsigned char *customergroups;

	int dummy_macs;
};
struct net
{
	unsigned char *name;
	unsigned long address;
	unsigned long mask;
};

struct group
{
	unsigned char *name;
	int id;
};
