
struct payments_module
{
	MODULE base;

	char *comment;
	char *s_comment;
	char *deadline;
	char *networks;
	char *customergroups;
	char *excluded_networks;
	char *excluded_customergroups;
	int paytype;
	int numberplanid;
	int up_payments;
	int expiry_days;
	int num_period;
	int check_invoices;
	double suspension_percentage;
	char *tariff_internet;
	char *tariff_hosting;
	char *tariff_service;
	char *tariff_phone;
	char *tariff_tv;
	char *tariff_other;
};

struct plan
{
	int division;
	int period;
	int plan;
	int number;
	int isdefault;
};

#ifdef USE_PGSQL
#define BROADCAST "cast(cast(address as bit(32)) | ~ cast(inet_aton(mask) as bit(32)) as bigint)"
#define CURRVAL "SELECT currval('')"
#else
#define BROADCAST "address | 4294967295>>bit_count(inet_aton(mask))"
#define LAST_INSERT_ID SELECT LAST_INSERT_ID()
#endif
