
struct cutoff_module
{
	MODULE base;
	
	int warn_only;
	unsigned char *command;
	unsigned char *limit;
	unsigned char *warning;
};
