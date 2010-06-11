#ifndef SQL_H
#define SQL_H

#include "tscript_context.h"

#ifdef USE_PGSQL
#include "../../../dbdrivers/pgsql/db.h"
#endif
#ifdef USE_MYSQL
#include "../../../dbdrivers/mysql/db.h"
#endif

void tscript_ext_sql_init(tscript_context *, ConnHandle *);
void tscript_ext_sql_close(tscript_context *);

#define CUSTOMERS "SELECT customers.id AS id, customers.lastname AS lastname, \
		    customers.name AS name, customers.status AS status, \
		    address, zip, city, email, ten, ssn, \
		    customers.info AS info, message, regon, rbe, icn, \
		    SUM(nodes.warning) AS warning, SUM(nodes.access) AS access, \
		    ROUND(COALESCE(SUM(value), 0.00)/( \
			CASE COUNT(DISTINCT nodes.id) \
			WHEN 0 THEN 1 \
			ELSE COUNT(DISTINCT nodes.id) END \
		    ),2) AS balance \
		FROM customers \
		LEFT JOIN cash ON (customers.id = customerid) \
		LEFT JOIN nodes ON (customers.id = ownerid) \
		WHERE deleted = 0 \
		GROUP BY customers.id, lastname, customers.name, \
		    customers.status, address, zip, city, email, \
		    ten, ssn, customers.info, message, regon, \
		    rbe, icn"

#ifdef USE_MYSQL
#define NODES "SELECT nodes.id AS id, nodes.name AS name, ownerid, access, \
		    warning, netdev, lastonline, nodes.info AS info, \
		    CONCAT(customers.lastname, ' ', customers.name) AS owner, \
		    customers.message AS message, mac, passwd, linktype, \
		    INET_NTOA(ipaddr) AS ip, INET_NTOA(ipaddr_pub) AS ip_pub, \
		    chkmac, halfduplex \
		    FROM vnodes \
		    LEFT JOIN customers ON (customers.id = ownerid)"
#else
#define NODES "SELECT nodes.id AS id, nodes.name AS name, ownerid, access, \
		    warning, netdev, lastonline, nodes.info AS info, port, \
		    customers.lastname || ' ' || customers.name AS owner, \
		    customers.message AS message, mac, passwd, linktype, \
		    INET_NTOA(ipaddr) AS ip, INET_NTOA(ipaddr_pub) AS ip_pub, \
		    chkmac, halfduplex \
		    FROM vnodes \
		    LEFT JOIN customers ON (customers.id = ownerid)"
#endif

#define NETWORKS "SELECT id, name, INET_NTOA(address) AS address, \
		    mask, interface, gateway, dns, dns2, wins, domain, \
		    dhcpstart, dhcpend \
		    FROM networks" 

#endif
