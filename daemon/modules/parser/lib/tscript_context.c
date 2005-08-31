/*

T-Script - Context
Copyright (C) 2004-2005, Adrian Smarzewski <adrian@kadu.net>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

#include "tscript_context.h"

tscript_context* tscript_context_create()
{
	tscript_context* context = (tscript_context*)malloc(sizeof(tscript_context));
	context->ast = NULL;
	context->vars = tscript_variables_map_create();
	context->extensions = tscript_extension_map_create();
	context->constants = tscript_constant_map_create();
	context->error = NULL;
	context->debug_callback = NULL;
	return context;
}

void tscript_context_free(tscript_context* context)
{
	if (context->ast != NULL)
		tscript_ast_node_free(context->ast);
	tscript_variables_map_free(context->vars);
	tscript_extension_map_free(context->extensions);
	tscript_constant_map_free(context->constants);
	if (context->error != NULL)
		free(context->error);
	free(context);
}
