struct net
{
	char *name;
	char *domain;
	char *interface;
	char *gateway;
	char *dns;
	char *dns2;
	char *wins;
	char *prefix;
	unsigned long address;
	unsigned long mask;
};

struct host
{
	char *name;
	char *passwd;
	char *warning;
	char *access;
	char *info;
	char *mac;
	char *port;
	char *id;
	char *cid;
	char *customer;
	char *ip;
	char *ip_pub;
	char *i16;
	char *i16_pub;
	struct net net;
	struct net pubnet;
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
	char *excluded_networks;
	char *customergroups;
	char *excluded_customergroups;
	char *nodegroups;
	char *excluded_nodegroups;

	int skip_dev_ips;
	int skip_host_ips;
	int pub_replace;
	int warn_replace;
	int join_customers;
	int share_netdev_pubip;
};
