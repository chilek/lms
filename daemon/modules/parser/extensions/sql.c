#include "sql.h"
#include "net.h"
#include "tscript_extensions.h"

#include <stdio.h>
#include <string.h>
#include <math.h>

ConnHandle *conn = NULL;
int rows;

void tscript_ext_sql_exec(char* query)
{
	rows = db_exec(conn, query);
}

tscript_value * tscript_ext_sql_escape(tscript_value *arg)
{
	char *tmp;
	tscript_value *res;

	tmp = db_escape(conn, tscript_value_convert_to_string(arg)->data);
	res = tscript_value_create(TSCRIPT_TYPE_STRING, tmp);
	free(tmp);

	return res;
}

tscript_value * tscript_ext_sql_rows(tscript_value *arg)
{
	QueryHandle *q = NULL;
	char *query = tscript_value_convert_to_string(arg)->data;
	
	rows = 0;
		
	if( (strncmp("SELECT", query, 6)==0) ||
	    (strncmp("select", query, 6)==0) )
	{
		q = db_query(conn, query);
		rows = db_nrows(q);
		db_free(&q);
	}
	else
		tscript_ext_sql_exec(query);

	return tscript_value_create_number(rows);
}

tscript_value * tscript_ext_sql_create(tscript_value *arg)
{
	char* query;

	asprintf(&query, "CREATE %s", tscript_value_convert_to_string(arg)->data);
	tscript_ext_sql_exec(query);
	free(query);

	return tscript_value_create_null();
}

tscript_value * tscript_ext_sql_drop(tscript_value *arg)
{
	char* query;

	asprintf(&query, "DROP %s", tscript_value_convert_to_string(arg)->data);
	tscript_ext_sql_exec(query);
	free(query);

	return tscript_value_create_null();
}

tscript_value * tscript_ext_sql_insert(tscript_value *arg)
{
	char* query;

	asprintf(&query, "INSERT %s", tscript_value_convert_to_string(arg)->data);
	tscript_ext_sql_exec(query);
	free(query);

	return tscript_value_create_null();
}

tscript_value * tscript_ext_sql_delete(tscript_value *arg)
{
	char* query;

	asprintf(&query, "DELETE %s", tscript_value_convert_to_string(arg)->data);
	tscript_ext_sql_exec(query);
	free(query);

	return tscript_value_create_null();
}

tscript_value * tscript_ext_sql_update(tscript_value *arg)
{
	char* query;
	
	asprintf(&query, "UPDATE %s", tscript_value_convert_to_string(arg)->data);
	tscript_ext_sql_exec(query);
	free(query);
	
	return tscript_value_create_null();
}

tscript_value * tscript_ext_sql_select(tscript_value *arg)
{
	tscript_value *res, **res_row, *index;
	int r, c;
	char *query, *colname, *value;
	QueryHandle *q = NULL;
	
	asprintf(&query, "SELECT %s", tscript_value_convert_to_string(arg)->data);
	q = db_query(conn, query);

	res = tscript_value_create_array();

	for(r = 0; r<db_nrows(q); r++)
	{
		index = tscript_value_create_number(r);
		res_row = tscript_value_array_item_ref(&res, index);
		tscript_value_free(index);
		*res_row = tscript_value_create_array();

		for(c = 0; c<db_ncols(q); c++)
		{
			colname = db_colname(q, c);
			value = db_get_data(q, r, colname);
			index = tscript_value_create_number(c);
			(*tscript_value_array_item_ref(res_row, index)) = tscript_value_create_string(value);
			(*tscript_value_subvar_ref(*res_row, colname)) = tscript_value_create_string(value);
			tscript_value_free(index);
		}
	}
	db_free(&q);
	
	return res;
}

tscript_value * tscript_ext_sql_customers()
{
	tscript_value *res, **res_row, *index;
	int r, c;
	char *colname, *value;
	QueryHandle *q = NULL;
	
	q = db_query(conn, CUSTOMERS);

	res = tscript_value_create_array();

	for(r = 0; r<db_nrows(q); r++)
	{
		index = tscript_value_create_number(r);
		res_row = tscript_value_array_item_ref(&res, index);
		tscript_value_free(index);
		*res_row = tscript_value_create_array();

		for(c = 0; c<db_ncols(q); c++)
		{
			colname = db_colname(q, c);
			value = db_get_data(q, r, colname);
			(*tscript_value_subvar_ref(*res_row, colname)) = tscript_value_create_string(value);
		}
	}
	db_free(&q);
	
	return res;
}

tscript_value * tscript_ext_sql_nodes()
{
	tscript_value *res, **res_row, *index;
	int r, c;
	char *colname, *value;
	QueryHandle *q = NULL;
	
	q = db_query(conn, NODES);

	res = tscript_value_create_array();

	for(r = 0; r<db_nrows(q); r++)
	{
		index = tscript_value_create_number(r);
		res_row = tscript_value_array_item_ref(&res, index);
		tscript_value_free(index);
		*res_row = tscript_value_create_array();

		for(c = 0; c<db_ncols(q); c++)
		{
			colname = db_colname(q, c);
			value = db_get_data(q, r, colname);
			(*tscript_value_subvar_ref(*res_row, colname)) = tscript_value_create_string(value);
		}
	}
	db_free(&q);
	
	return res;
}

tscript_value * tscript_ext_sql_networks()
{
	tscript_value *res, **res_row, *index;
	int r, c;
	char *colname, *value;
	QueryHandle *q = NULL;
	
	q = db_query(conn, NETWORKS);

	res = tscript_value_create_array();

	for(r = 0; r<db_nrows(q); r++)
	{
		index = tscript_value_create_number(r);
		res_row = tscript_value_array_item_ref(&res, index);
		tscript_value_free(index);
		*res_row = tscript_value_create_array();

		for(c = 0; c<db_ncols(q); c++)
		{
			colname = db_colname(q, c);
			value = db_get_data(q, r, colname);
			(*tscript_value_subvar_ref(*res_row, colname)) = tscript_value_create_string(value);
		}

		c = mask2prefix(db_get_data(q, r, "mask"));
		(*tscript_value_subvar_ref(*res_row, "prefix")) = tscript_value_create_number(c);
		(*tscript_value_subvar_ref(*res_row, "size")) = tscript_value_create_number(pow(2,32 - c));
	}
	db_free(&q);
	
	return res;
}

void tscript_ext_sql_init(tscript_context *context, ConnHandle *c)
{
	conn = c;
	tscript_add_extension(context, "CREATE", tscript_ext_sql_create);
	tscript_add_extension(context, "DROP", tscript_ext_sql_drop);
	tscript_add_extension(context, "INSERT", tscript_ext_sql_insert);
	tscript_add_extension(context, "DELETE", tscript_ext_sql_delete);
	tscript_add_extension(context, "UPDATE", tscript_ext_sql_update);
	tscript_add_extension(context, "SELECT", tscript_ext_sql_select);
	tscript_add_extension(context, "rows", tscript_ext_sql_rows);
	tscript_add_extension(context, "escape", tscript_ext_sql_escape);
	tscript_add_constant(context, "CUSTOMERS", tscript_ext_sql_customers);
	tscript_add_constant(context, "NODES", tscript_ext_sql_nodes);
	tscript_add_constant(context, "NETWORKS", tscript_ext_sql_networks);
}

void tscript_ext_sql_close(tscript_context *context)
{
	tscript_remove_extension(context, "CREATE");
	tscript_remove_extension(context, "DROP");
	tscript_remove_extension(context, "INSERT");
	tscript_remove_extension(context, "DELETE");
	tscript_remove_extension(context, "UPDATE");
	tscript_remove_extension(context, "SELECT");
	tscript_remove_extension(context, "rows");
	tscript_remove_extension(context, "escape");
	tscript_remove_constant(context, "NODES");
	tscript_remove_constant(context, "CUSTOMERS");
	tscript_remove_constant(context, "NETWORKS");
}
