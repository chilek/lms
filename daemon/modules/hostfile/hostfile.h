
struct hostfile_module
{
	MODULE base;

	unsigned char *prefix;
	unsigned char *append;
	unsigned char *grant;
	unsigned char *deny;

	unsigned char *file;
	unsigned char *command;
};
