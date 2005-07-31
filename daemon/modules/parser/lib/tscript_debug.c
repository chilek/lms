#include "tscript_debug.h"

#include <stdio.h>
#include <stdarg.h>
#include "tscript_parser.h"

static int tscript_verbose = 0;
static char* tscript_err = NULL;

void tscript_set_verbose(int verbose)
{
	tscript_verbose = verbose;
}

void tscript_debug(const char* format, ...)
{
	va_list va;
	
	if (!tscript_verbose)
		return;
	va_start(va, format);
	vfprintf(stderr, format, va);
	va_end(va);
}

void tscript_internal_error(const char* format, ...)
{
	va_list va;
	
	fprintf(stderr, "Internal error: ");
	va_start(va, format);
	vfprintf(stderr, format, va);
	va_end(va);
	
	exit(1);
}

void tscript_yyerror(const char* msg)
{
	asprintf(&tscript_err, "Compile error (line: %i, col: %i): %s",
		tscript_yylloc.first_line, tscript_yylloc.first_column, msg);
}

char* tscript_compiler_error()
{
	return tscript_err;
}
