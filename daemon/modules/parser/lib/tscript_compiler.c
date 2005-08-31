/*

T-Script - Compiler API
Copyright (C) 2004, Adrian Smarzewski <adrian@kadu.net>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

#include "tscript_compiler.h"
#include "tscript_parser.h"

extern FILE* tscript_yyin;
extern void* tscript_yy_setup_scanner(const char *);
extern void tscript_yy_cleanup_scanner(void *);

int tscript_compile_stream(tscript_context* context, FILE* file)
{
	tscript_yyin = file;
	tscript_init_lexical();
	return tscript_yyparse(&context->ast);
}

int tscript_compile_string(tscript_context* context, const char* string)
{
	int r;
	void* buf = tscript_yy_setup_scanner(string);
	tscript_init_lexical();
	r = tscript_yyparse(&context->ast);
	tscript_yy_cleanup_scanner(buf);
	return r;
}

int tscript_compile_stdin(tscript_context* context)
{
	return tscript_compile_stream(context, stdin);
}

int tscript_compile_file(tscript_context* context, char* file_name)
{
	int r;
	FILE* f = fopen(file_name, "r");
	if (f == NULL)
		return -1;
	r = tscript_compile_stream(context, f);
	fclose(f);
	return r;
}
