
struct payments_module
{
	MODULE base;
	
	unsigned char *comment;
	int up_payments;
	int expiry_days;
};
