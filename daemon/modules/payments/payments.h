
struct payments_module
{
	MODULE base;
	
	unsigned char *comment;
	unsigned char *deadline;	
	unsigned char *paytype;
	int up_payments;
};
