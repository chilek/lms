
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
	char *numbertemplate;
	int isdefault;
};

