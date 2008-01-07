
struct cutoff_module
{
	MODULE base;
	
	int warn_only;
	int nodeassignments;
	int checkinvoices;
	int nodegroup_only;
	int deadline;
	int limit;
	
	char *command;
	char *warning;
	char *expwarning;

	char *customergroups;
	char *excluded_customergroups;	
};
