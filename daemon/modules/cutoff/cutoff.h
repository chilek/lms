
struct cutoff_module
{
	MODULE base;
	
	int warn_only;
	int nodeassignments;
	int checkinvoices;
	int deadline;
	
	char *command;
	char *limit;
	char *warning;
	char *expwarning;

	char *customergroups;
	char *excluded_customergroups;	
};
