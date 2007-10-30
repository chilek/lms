
struct traffic_module
{
	MODULE base;
	char *file;
	char *begin_command;
	char *end_command;
};

typedef struct
{
	char *ipaddr;
	int id;
} HOSTS;
