
struct cutoff_module
{
	MODULE base;
	
	int warn_only;
	char *command;
	char *limit;
	char *warning;
	char *expwarning;
};
