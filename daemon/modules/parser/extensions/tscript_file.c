/****************************************************************************
**
** T-Script - FILE extension
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

#include "tscript_file.h"
#include "tscript_extensions.h"

#include <dirent.h>
#include <stdio.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>
#include <string.h>
#ifdef WIN32
#include <errno.h>
#endif

static tscript_value* tscript_save_to_file(char* filename, char* str)
{
	int len;
	FILE* f = fopen(filename, "a");
	if (f == NULL)
		return tscript_value_create_error("error opening file %s", filename);
	len = strlen(str);
	if (fwrite(str, 1, len, f) != len)
	{
		fclose(f);
		return tscript_value_create_error("error writting file %s", filename);
	}
	if (fclose(f) != 0)
		return tscript_value_create_error("error closing file %s", filename);
	return tscript_value_create_string("");
}

tscript_value* tscript_ext_file(tscript_value* arg)
{
	tscript_value* index;
	tscript_value* tmp1;
	tscript_value* tmp2;
	tscript_value* res;
	index = tscript_value_create_number(0);
	tmp1 = tscript_value_convert_to_string(tscript_value_dereference(*tscript_value_array_item_ref(&arg, index)));
	tscript_value_free(index);
	index = tscript_value_create_number(1);
	tmp2 = tscript_value_convert_to_string(tscript_value_dereference(*tscript_value_array_item_ref(&arg, index)));
	tscript_value_free(index);
	res = tscript_save_to_file(
		tscript_value_as_string(tmp1),
		tscript_value_as_string(tmp2));
	tscript_value_free(tmp1);
	tscript_value_free(tmp2);
	return res;
}

tscript_value* tscript_ext_listdir(tscript_value* arg)
{
	tscript_value* res;
	DIR* d;
	struct dirent* e;
	struct stat s;
	int i;
	tscript_value* index;
	tscript_value** item;
	tscript_value* name;
	char* tmp;
	name = tscript_value_convert_to_string(arg);
	d = opendir(tscript_value_as_string(name));
	if (d == NULL)
		return tscript_value_create_error("error opening %s directory",
			tscript_value_as_string(name));
	tscript_value_free(name);
	res = tscript_value_create_array();
	for (i = 0; (e = readdir(d)) != NULL; i++)
	{
		index = tscript_value_create_number(i);
		item = tscript_value_array_item_ref(&res, index);
		*item = tscript_value_create_string(e->d_name);
		asprintf(&tmp, "%s/%s", tscript_value_as_string(tscript_value_convert_to_string(arg)), e->d_name);
		if (stat(tmp, &s) != 0)
			return tscript_value_create_error("could not stat %s file", tmp);
		free(tmp);
		*tscript_value_subvar_ref(*item, "size") =
			tscript_value_create_number(s.st_size);
		tscript_value_free(index);
	}
	closedir(d);
	return res;
}

#ifdef WIN32
// TODO: from dietlibc - gpl - should be replaced later
size_t getdelim(char **lineptr, size_t *n, int delim, FILE *stream)
{
	size_t i;
	int tmp;
	char* new;
	if (!lineptr || !n)
	{
		errno = EINVAL;
		return -1;
	}
	if (!*lineptr)
		*n=0;
	for (i=0; ; )
	{
		int x=fgetc(stream);
		if (i>=*n)
		{
			tmp=*n+100;
			new=realloc(*lineptr,tmp);
			if (!new) return -1;
			*lineptr=new; *n=tmp;
		}
		if (x==EOF) { if (!i) return -1; (*lineptr)[i]=0; return i; }
		(*lineptr)[i]=x;
		++i;
		if (x==delim) break;
	}
	(*lineptr)[i]=0;
	return i;
}

ssize_t getline(char **lineptr, size_t *n, FILE *stream)
{
	return getdelim(lineptr,n,'\n',stream);
}
#endif

tscript_value* tscript_ext_readfile(tscript_value* arg)
{
	FILE* f;
	char* line;
	size_t n;
	tscript_value* res;
	int i;
	tscript_value* index;

	f = fopen(tscript_value_as_string(tscript_value_convert_to_string(arg)), "r");
	if (f == NULL)
		return tscript_value_create_error("error opening %s file",
			tscript_value_as_string(tscript_value_convert_to_string(arg)));
	line = NULL;
	res = tscript_value_create_array();
	for (i = 0; getline(&line, &n, f) >= 0; i++)
	{
		index = tscript_value_create_number(i);
		(*tscript_value_array_item_ref(&res, index)) = tscript_value_create_string(line);
		tscript_value_free(index);
	}
	if (line != NULL)
		free(line);
	if (fclose(f) != 0)
		return tscript_value_create_error("error closing %s file",
			tscript_value_as_string(tscript_value_convert_to_string(arg)));
	return res;
}

tscript_value* tscript_ext_getfile(tscript_value* arg)
{
	FILE* f;
	char* line;
	size_t n;
	tscript_value* res;
	tscript_value* tmp;
	int i;

	f = fopen(tscript_value_as_string(tscript_value_convert_to_string(arg)), "r");
	if (f == NULL)
		return tscript_value_create_error("error opening %s file",
			tscript_value_as_string(tscript_value_convert_to_string(arg)));
	line = NULL;
	res = tscript_value_create_string("");
	for (i = 0; getline(&line, &n, f) >= 0; i++)
	{
		tmp = tscript_value_add(res, tscript_value_create_string(line));
		tscript_value_free(res);
		res = tmp;
	}
	if (line != NULL)
		free(line);
	if (fclose(f) != 0)
		return tscript_value_create_error("error closing %s file",
			tscript_value_as_string(tscript_value_convert_to_string(arg)));
	return res;
}

tscript_value* tscript_ext_deletefile(tscript_value* arg)
{
	if (unlink(tscript_value_as_string(tscript_value_convert_to_string(arg))) != 0)
		return tscript_value_create_error("error deleting %s file",
			tscript_value_as_string(tscript_value_convert_to_string(arg)));
	return tscript_value_create_string("");
}

tscript_value* tscript_ext_fileexists(tscript_value* arg)
{
	tscript_value* path;
	int res;
	path = tscript_value_convert_to_string(tscript_value_dereference(arg));
	res = access(tscript_value_as_string(path), F_OK);
	tscript_value_free(path);
	return tscript_value_create_number(res == 0);
}

void tscript_ext_file_init(tscript_context* context)
{
	tscript_add_extension(context, "file", tscript_ext_file, 2, 2);
	tscript_extension_set_block(context, "file");
	tscript_add_extension(context, "listdir", tscript_ext_listdir, 1, 1);
	tscript_add_extension(context, "deletefile", tscript_ext_deletefile, 1, 1);
	tscript_add_extension(context, "readfile", tscript_ext_readfile, 1, 1);
	tscript_add_extension(context, "getfile", tscript_ext_getfile, 1, 1);
	tscript_add_extension(context, "fileexists", tscript_ext_fileexists, 1, 1);
}

void tscript_ext_file_close(tscript_context* context)
{
	tscript_remove_extension(context, "file");
	tscript_remove_extension(context, "listdir");
	tscript_remove_extension(context, "deletefile");
	tscript_remove_extension(context, "readfile");
	tscript_remove_extension(context, "getfile");
	tscript_remove_extension(context, "fileexists");
}
