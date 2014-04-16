
struct cutoff_module
{
	MODULE base;

	int warn_only;
	int nodegroup_only;
	int nodeassignments;
	int customerassignments;
	int checkinvoices;
	int deadline;
	int disable_suspended;

	char *limit;
	char *command;
	char *warning;
	char *expwarning;

	char *customergroups;
	char *excluded_customergroups;
	char *networks;
	char *excluded_networks;
};
