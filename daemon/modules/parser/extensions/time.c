#include "time.h"
#include "tscript_extensions.h"

#include <time.h>
//#include <string.h>

tscript_value * tscript_ext_time_date(tscript_value *arg)
{
	static char tmp[255];
	
	char *tmp_arg = tscript_value_convert_to_string(arg)->data;
	time_t t = time(NULL);

	if(*tmp_arg)
		strftime(tmp, 255, tmp_arg, localtime(&t));
	else
		strftime(tmp, 255, "%Y/%m/%d", localtime(&t));
	
	return tscript_value_create_string(tmp);
}

void tscript_ext_time_init(tscript_context *context)
{
	tscript_add_extension(context, "date", tscript_ext_time_date);
}

void tscript_ext_time_close(tscript_context *context)
{
	tscript_remove_extension(context, "date");
}
