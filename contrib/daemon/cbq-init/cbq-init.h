struct net
{
	unsigned char *name;
	unsigned char *interface;
	unsigned long address;
	unsigned long mask;
};

struct group
{
	unsigned char *name;
	int id;
};

struct cbq_module
{
	MODULE base;

	unsigned char *path;
	unsigned char *command;
	unsigned char *cbq_down;
	unsigned char *cbq_up;
	unsigned char *mark_rule;
	unsigned char *mark_file;
	unsigned char *mark_file_begin;
	unsigned char *mark_file_end;
	unsigned char *networks;
	unsigned char *customergroups;
};
