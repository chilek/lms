#ifndef SQL_H
#define SQL_H

#include "tscript_context.h"
#include "../../../lmsd.h"

void tscript_ext_sql_init(tscript_context *,  GLOBAL *);
void tscript_ext_sql_close(tscript_context *);

#define CUSTOMERS "SELECT customers.id AS id, customers.lastname AS lastname, \
		    customers.name AS name, customers.status AS status, \
		    address, zip, city, ten, ssn, \
			(SELECT contact FROM customercontacts \
				WHERE customerid = customers.id AND customercontacts.type = 8 \
				ORDER BY id LIMIT 1) AS email, \
		    customers.info AS info, message, regon, rbe, icn, \
		    SUM(nodes.warning) AS warning, SUM(nodes.access) AS access, \
		    ROUND(COALESCE(SUM(value), 0.00)/( \
			CASE COUNT(DISTINCT nodes.id) \
			WHEN 0 THEN 1 \
			ELSE COUNT(DISTINCT nodes.id) END \
		    ),2) AS balance \
		FROM customeraddressview \
		LEFT JOIN cash ON (customers.id = cash.customerid) \
		LEFT JOIN nodes ON (customers.id = ownerid) \
		WHERE deleted = 0 \
		GROUP BY customers.id, lastname, customers.name, \
		    customers.status, address, zip, city, email, \
		    ten, ssn, customers.info, message, regon, \
		    rbe, icn"

#define NODES "SELECT n.id, n.name, n.ownerid, n.access, \
		    n.warning, n.netdev, n.lastonline, n.info, n.port, \
		    %cfullname AS owner, \
		    c.message, n.mac, n.passwd, n.linktype, \
		    INET_NTOA(n.ipaddr) AS ip, INET_NTOA(n.ipaddr_pub) AS ip_pub, \
		    n.chkmac, n.halfduplex \
		    FROM vnodes n \
		    LEFT JOIN customers c ON (c.id = n.ownerid)"

#define NETWORKS "SELECT id, name, INET_NTOA(address) AS address, \
		    mask, interface, gateway, dns, dns2, wins, domain, \
		    dhcpstart, dhcpend \
		    FROM networks"

#endif
