#include "debug.h"

#include <stdio.h>
#include <stdarg.h>
#include "parser.tab.h"

int tscript_verbose = 0;

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

void tscript_runtime_error(const char* format, ...)
{
	va_list va;
	
	fprintf(stderr, "Runtime error: ");
	va_start(va, format);
	vfprintf(stderr, format, va);
	va_end(va);
	
	exit(1);
}

void tscript_yyerror(const char* msg)
{
	fprintf(stderr, "Compile error (line: %i, col: %i): %s\n", tscript_yylloc.first_line, tscript_yylloc.first_column, msg);
	exit(1);
}
