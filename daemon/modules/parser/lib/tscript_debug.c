#include "tscript_debug.h"

#include <stdio.h>
#include "tscript_parser.h"

void tscript_set_debug_callback(tscript_context* context, tscript_debug_callback* callback)
{
	context->debug_callback = callback;
}

void tscript_debug(tscript_context* context, const char* format, ...)
{
	va_list va;
	
	if (context->debug_callback == NULL)
		return;
	va_start(va, format);
	context->debug_callback(format, va);
	va_end(va);
}

void tscript_internal_error(const char* format, ...)
{
	va_list va;
	
	fprintf(stderr, "Internal error: ");
	va_start(va, format);
	vfprintf(stderr, format, va);
	va_end(va);
	fprintf(stderr, "\n");
	
	exit(1);
}

void tscript_yyerror(tscript_context* context, const char* msg)
{
	if (context->error != NULL)
		free(context->error);
	asprintf(&context->error, "(line: %i, col: %i): %s",
		tscript_yylloc.first_line, tscript_yylloc.first_column, msg);
}

char* tscript_compile_error(tscript_context* context)
{
	return context->error;
}
