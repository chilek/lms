
struct cutoff_module
{
	MODULE base;
	
	int warn_only;
	int deadline;
	unsigned char *command;
	unsigned char *limit;
	unsigned char *warning;
};

#define DATE_FORMAT_LEN 	20
