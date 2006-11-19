struct net
{
	char *name;
	char *interface;
	unsigned long address;
	unsigned long mask;
};

struct group
{
	char *name;
	int id;
};

struct cbq_module
{
	MODULE base;

	char *path;
	char *command;
	char *cbq_down;
	char *cbq_up;
	char *mark_rule;
	char *mark_file;
	char *mark_file_begin;
	char *mark_file_end;
	char *networks;
	char *customergroups;
};
