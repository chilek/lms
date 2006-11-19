
struct cutoff_module
{
	MODULE base;
	
	int warn_only;
	int deadline;
	char *command;
	char *limit;
	char *warning;
};

#define DATE_FORMAT_LEN 	20
