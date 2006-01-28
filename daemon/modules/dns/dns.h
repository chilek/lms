
struct dns_module
{
	MODULE base;

	char *fpatterns;
	char *rpatterns;
	char *fgeneric;
	char *rgeneric;
	char *fzones;
	char *rzones;
	char *forward;
	char *reverse;
	char *command;	
	
	char *confpattern;
	char *confout;
	char *confforward;
	char *confreverse;
	
	char *networks;
	char *customergroups;
};

struct net
{
	char *name;
	unsigned long address;
};

struct group
{
	char *name;
	int id;
};
