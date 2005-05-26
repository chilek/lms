
struct dns_module
{
	MODULE base;

	unsigned char * fpatterns;
	unsigned char * rpatterns;
	unsigned char * fgeneric;
	unsigned char * rgeneric;
	unsigned char * fzones;
	unsigned char * rzones;
	unsigned char * forward;
	unsigned char * reverse;
	unsigned char * command;	
	
	unsigned char * confpattern;
	unsigned char * confout;
	unsigned char * confforward;
	unsigned char * confreverse;
	
	unsigned char * networks;
	unsigned char * customergroups;
};

struct net
{
	unsigned char *name;
	unsigned long address;
};

struct group
{
	unsigned char *name;
	int id;
};
