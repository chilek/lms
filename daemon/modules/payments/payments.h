
struct payments_module
{
	MODULE base;
	
	unsigned char *comment;
	unsigned char *paytype;
	unsigned char *deadline;
	int up_payments;
	int expiry_days;
	int monthly_num;
};
