#include "tscript_exec.h"
#include <stdio.h>
#include "tscript_extensions.h"

tscript_value tscript_ext_exec(tscript_value arg)
{
	char* out;
	int res;
	FILE* child_out = popen(arg.data, "r");
	if (child_out == NULL)
		return tscript_value_create_error("Couldn't execute %s", arg.data);
	out = (char*)malloc(512); // FIXME
	res = fread(out, 1, 511, child_out);
	out[res]=0;
	pclose(child_out);
	return tscript_value_create(TSCRIPT_TYPE_STRING, out);
}

void tscript_ext_exec_init()
{
	tscript_add_extension("exec", tscript_ext_exec);
}

void tscript_ext_exec_close()
{
	tscript_remove_extension("exec");
}
