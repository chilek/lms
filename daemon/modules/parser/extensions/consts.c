#include "consts.h"
#include "tscript_extensions.h"
#include "tscript_debug.h"

#include <stdio.h>
#include <string.h>

ConnHandle *con = NULL;
int rows;

tscript_value tscript_ext_consts_customers()
{
	tscript_value res;
	tscript_value *res_row;
	int r, c;
	char *colname, *value;
	QueryHandle *q = NULL;
	
	tscript_debug("Executing SQL Extension: CUSTOMERS\n");
	q = db_query(con, CUSTOMERS);

	res = tscript_value_create(TSCRIPT_TYPE_ARRAY, "");

	for(r = 0; r<db_nrows(q); r++)
	{
		res_row = tscript_value_array_item_ref(&res, r);
		*res_row = tscript_value_create(TSCRIPT_TYPE_ARRAY, "");

		for(c = 0; c<db_ncols(q); c++)
		{
			colname = db_colname(q, c);
			value = db_get_data(q, r, colname);
			(*tscript_value_array_item_ref(res_row, c)) = tscript_value_create(TSCRIPT_TYPE_STRING, value);
			(*tscript_value_subvar_ref(res_row, colname)) = tscript_value_create(TSCRIPT_TYPE_STRING, value);
		}
	}
	db_free(&q);
	
	tscript_debug("Finished executing SQL Extension: CUSTOMERS\n");
	return res;
}

tscript_value tscript_ext_consts_nodes()
{
	tscript_value res;
	tscript_value *res_row;
	int r, c;
	char *colname, *value;
	QueryHandle *q = NULL;
	
	tscript_debug("Executing SQL Extension: NODES\n");
	q = db_query(con, NODES);

	res = tscript_value_create(TSCRIPT_TYPE_ARRAY, "");

	for(r = 0; r<db_nrows(q); r++)
	{
		res_row = tscript_value_array_item_ref(&res, r);
		*res_row = tscript_value_create(TSCRIPT_TYPE_ARRAY, "");

		for(c = 0; c<db_ncols(q); c++)
		{
			colname = db_colname(q, c);
			value = db_get_data(q, r, colname);
			(*tscript_value_array_item_ref(res_row, c)) = tscript_value_create(TSCRIPT_TYPE_STRING, value);
			(*tscript_value_subvar_ref(res_row, colname)) = tscript_value_create(TSCRIPT_TYPE_STRING, value);
		}
	}
	db_free(&q);
	
	tscript_debug("Finished executing SQL Extension: NODES\n");
	return res;
}

tscript_value tscript_ext_consts_networks()
{
	tscript_value res;
	tscript_value *res_row;
	int r, c;
	char *colname, *value;
	QueryHandle *q = NULL;
	
	tscript_debug("Executing SQL Extension: NETWORKS\n");
	q = db_query(con, NETWORKS);

	res = tscript_value_create(TSCRIPT_TYPE_ARRAY, "");

	for(r = 0; r<db_nrows(q); r++)
	{
		res_row = tscript_value_array_item_ref(&res, r);
		*res_row = tscript_value_create(TSCRIPT_TYPE_ARRAY, "");

		for(c = 0; c<db_ncols(q); c++)
		{
			colname = db_colname(q, c);
			value = db_get_data(q, r, colname);
			(*tscript_value_array_item_ref(res_row, c)) = tscript_value_create(TSCRIPT_TYPE_STRING, value);
			(*tscript_value_subvar_ref(res_row, colname)) = tscript_value_create(TSCRIPT_TYPE_STRING, value);
		}
		/* TODO:
		    prefix
		    size
		    broadcast
		    broadcastlong
		*/
	}
	db_free(&q);
	
	tscript_debug("Finished executing SQL Extension: NETWORKS\n");
	return res;
}

void tscript_ext_consts_init(ConnHandle *c)
{
	con = c;
	tscript_add_constant("CUSTOMERS", tscript_ext_consts_customers);
	tscript_add_constant("NODES", tscript_ext_consts_nodes);
	tscript_add_constant("NETWORKS", tscript_ext_consts_networks);
}

void tscript_ext_consts_close()
{
	tscript_remove_constant("CUSTOMERS");
	tscript_remove_constant("NODES");
	tscript_remove_constant("NETWORKS");
}
