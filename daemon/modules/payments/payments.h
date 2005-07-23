
struct payments_module
{
	MODULE base;
	
	unsigned char *comment;
	unsigned char *paytype;
	unsigned char *deadline;
	int up_payments;
	int expiry_days;
	int monthly_num;
	double suspension_percentage;
};

#define YEARLY 5
#define QUARTERLY 4
#define MONTHLY 3
#define WEEKLY 2
#define DAILY 1

#define _YEARLY_ "5"
#define _QUARTERLY_ "4"
#define _MONTHLY_ "3"
#define _WEEKLY_ "2"
#define _DAILY_ "1"
