
struct notify_module
{
	MODULE base;

	char *file;
	char *command;	
	char *mailtemplate;
	char *debugmail;
	int limit;
};
