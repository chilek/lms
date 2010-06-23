
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
};

struct plan
{
	int division;
	int period;
	int plan;
	int number;
};

#define HALFYEARLY 7
#define CONTINUOUS 6
#define YEARLY 5
#define QUARTERLY 4
#define MONTHLY 3
#define WEEKLY 2
#define DAILY 1
#define DISPOSABLE 0

#define _HALFYEARLY_ "7"
#define _YEARLY_ "5"
#define _QUARTERLY_ "4"
#define _MONTHLY_ "3"
#define _WEEKLY_ "2"
#define _DAILY_ "1"
#define _DISPOSABLE_ "0"

#ifdef USE_PGSQL
#define BROADCAST "cast(cast(address as bit(32)) | ~ cast(inet_aton(mask) as bit(32)) as bigint)"
#define CURRVAL "SELECT currval('')"
#else
#define BROADCAST "address | 4294967295>>bit_count(inet_aton(mask))"
#define LAST_INSERT_ID SELECT LAST_INSERT_ID()
#endif
