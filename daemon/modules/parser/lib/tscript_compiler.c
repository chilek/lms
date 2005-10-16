/****************************************************************************
**
** T-Script - Compiler API
** Copyright (C) 2004-2005, SILVERCODERS Adrian Smarzewski
** http://silvercoders.com
**
** Project homepage: http://silvercoders.com/index.php?page=T_Script
** Project authors:  Adrian Smarzewski
**
** This program may be distributed and/or modified under the terms of the
** GNU General Public License version 2 as published by the Free Software
** Foundation and appearing in the file COPYING.GPL included in the
** packaging of this file.
**
** Please remember that any attempt to workaround the GNU General Public
** License using wrappers, pipes, client/server protocols, and so on
** is considered as license violation. If your program, published on license
** other than GNU General Public License version 2, calls some part of this
** code directly or indirectly, you have to buy commerial license.
** If you do not like our point of view, simply do not use the product.
**
** Licensees holding valid commercial license for this product
** may use this file in accordance with the license published by
** Silvercoders and appearing in the file COPYING.COM
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
**
*****************************************************************************/

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
