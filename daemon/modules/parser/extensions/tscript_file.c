/*

T-Script - FILE EXTENSION
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

#include "tscript_file.h"
#include "tscript_extensions.h"

#include <dirent.h>
#include <stdio.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>
#ifdef WIN32
#include <errno.h>
#endif

tscript_value* tscript_ext_listdir(tscript_value* arg)
{
	tscript_value* res;
	DIR* d;
	struct dirent* e;
	struct stat s;
	int i;
	tscript_value* index;
	tscript_value** item;
	char* tmp;
	d = opendir(tscript_value_convert_to_string(arg)->data);
	if (d == NULL)
		return tscript_value_create_error("error opening %s directory",
			tscript_value_convert_to_string(arg)->data);
	res = tscript_value_create_array();
	for (i = 0; (e = readdir(d)) != NULL; i++)
	{
		index = tscript_value_create_number(i);
		item = tscript_value_array_item_ref(&res, index);
		*item = tscript_value_create_string(e->d_name);
		asprintf(&tmp, "%s/%s", tscript_value_convert_to_string(arg)->data, e->d_name);
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

	f = fopen(tscript_value_convert_to_string(arg)->data, "r");
	if (f == NULL)
		return tscript_value_create_error("error opening %s file",
			tscript_value_convert_to_string(arg)->data);
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
			tscript_value_convert_to_string(arg)->data);
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

	f = fopen(tscript_value_convert_to_string(arg)->data, "r");
	if (f == NULL)
		return tscript_value_create_error("error opening %s file",
			tscript_value_convert_to_string(arg)->data);
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
			tscript_value_convert_to_string(arg)->data);
	return res;
}

tscript_value* tscript_ext_deletefile(tscript_value* arg)
{
	if (unlink(tscript_value_convert_to_string(arg)->data) != 0)
		return tscript_value_create_error("error deleting %s file",
			tscript_value_convert_to_string(arg)->data);
	return tscript_value_create_string("");
}

void tscript_ext_file_init(tscript_context* context)
{
	tscript_add_extension(context, "listdir", tscript_ext_listdir);
	tscript_add_extension(context, "deletefile", tscript_ext_deletefile);
	tscript_add_extension(context, "readfile", tscript_ext_readfile);
	tscript_add_extension(context, "getfile", tscript_ext_getfile);
}

void tscript_ext_file_close(tscript_context* context)
{
	tscript_remove_extension(context, "listdir");
	tscript_remove_extension(context, "deletefile");
	tscript_remove_extension(context, "readfile");
	tscript_remove_extension(context, "getfile");
}
