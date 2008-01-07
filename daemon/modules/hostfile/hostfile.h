struct net
{
	char *name;
	char *domain;
	char *interface;
	char *gateway;
	char *dns;
	char *dns2;
	char *wins;
	unsigned long address;
	unsigned long mask;
};

struct group
{
	char *name;
	int id;
};

struct host
{
	char *name;
	char *passwd;
	char *warning;
	char *access;
	char *info;
	char *mac;
	char *id;
	char *ip;
	char *ip_pub;
	char *i16;
	char *i16_pub;
	unsigned long address;
	unsigned long mask;
	struct net net;
};

struct hostfile_module
{
	MODULE base;

	char *prefix;
	char *append;
	char *host_prefix;
	char *host_append;
	char *grant;
	char *deny;
	char *grant_pub;
	char *deny_pub;
	char *warn;
	char *warn_pub;
	char *file;
	char *command;
	char *networks;
	char *customergroups;
	char *nodegroups;

	int skip_dev_ips;
	int pub_replace;
	int warn_replace;
};
