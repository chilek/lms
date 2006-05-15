/****************************************************************************
**
** T-Script - EXEC extension
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

#include "tscript_exec.h"
#include "tscript_extensions.h"

#include <stdio.h>

tscript_value* tscript_ext_exec(tscript_value* arg)
{
	tscript_value* r;
	char* out;
	int res;
	
	FILE* child_out = popen(tscript_value_as_string(arg), "r");
	if (child_out == NULL)
		return tscript_value_create_error("Couldn't execute %s",
			tscript_value_as_string(arg));
	out = (char*)malloc(512); // FIXME
	res = fread(out, 1, 511, child_out);
	out[res]=0;
	pclose(child_out);
	r =  tscript_value_create_string(out);
	free(out);
	return r;
}

void tscript_ext_exec_init(tscript_context* context)
{
	tscript_add_extension(context, "exec", tscript_ext_exec, 1, 1);
}

void tscript_ext_exec_close(tscript_context* context)
{
	tscript_remove_extension(context, "exec");
}
