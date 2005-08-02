#include "tscript_string.h"
#include "tscript_extensions.h"
#include "tscript_values.h"
#include <string.h>
#include <ctype.h>

tscript_value tscript_ext_trim(tscript_value arg)
{
	char* tmp;
	int i;
	tscript_value val = tscript_value_convert_to_string(arg);
	for (i = 0; isspace(val.data[i]); i++) {};
	tmp = strdup(&val.data[i]);
	for (i = strlen(tmp) - 1; i >= 0 && isspace(tmp[i]); i--)
		tmp[i] = 0;
	val = tscript_value_create_string(tmp);
	free(tmp);
	return val;
}

void tscript_ext_string_init()
{
	tscript_add_extension("trim", tscript_ext_trim);
}

void tscript_ext_string_close()
{
	tscript_remove_extension("trim");
}
