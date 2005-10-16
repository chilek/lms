/****************************************************************************
**
** T-Script - Debugging functions
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
