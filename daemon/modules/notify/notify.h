
struct notify_module
{
	MODULE base;

	unsigned char *file;
	unsigned char *command;	
	unsigned char *mailtemplate;
	unsigned char *debugmail;
	int limit;
};
