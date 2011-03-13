
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

#ifdef USE_PGSQL
#define BROADCAST "cast(cast(net.address as bit(32)) | ~ cast(inet_aton(net.mask) as bit(32)) as bigint)"
#else
#define BROADCAST "net.address | 4294967295>>bit_count(inet_aton(net.mask))"
#endif
