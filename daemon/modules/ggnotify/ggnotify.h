
struct ggnotify_module
{
	MODULE base;

	int uin;
	char *passwd;	
	char *ggtemplate;
	int debuguin;
	int limit;
};
