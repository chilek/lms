
struct ggnotify_module
{
	MODULE base;

	int uin;
	unsigned char *passwd;	
	unsigned char *ggtemplate;
	int debuguin;
	int limit;
};
