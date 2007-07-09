
struct cutoff_module
{
	MODULE base;
	
	int warn_only;
	int nodeassignments;
	char *command;
	char *limit;
	char *warning;
	char *expwarning;
};
