
struct payments_module
{
	MODULE base;
	
	unsigned char *comment;
	unsigned char *paytype;
	unsigned char *deadline;
	unsigned char *numberplanid;
	int up_payments;
	int expiry_days;
	int num_period;
	double suspension_percentage;
};

#define YEARLY 5
#define QUARTERLY 4
#define MONTHLY 3
#define WEEKLY 2
#define DAILY 1
#define DISPOSABLE 0

#define _YEARLY_ "5"
#define _QUARTERLY_ "4"
#define _MONTHLY_ "3"
#define _WEEKLY_ "2"
#define _DAILY_ "1"
#define _DISPOSABLE_ "0"
