/****************************************************************************
**
** T-Script - Context
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
