<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 */

$this->BeginTrans();

$this->Execute("
    CREATE TABLE customerbalances (
        customerid integer NOT NULL CONSTRAINT customerbalances_customerid_fkey
            REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE,
        balance numeric(9,2) NOT NULL
    )
");

$this->Execute("
	INSERT INTO customerbalances (customerid, balance)
		(SELECT customerid, SUM(value * currencyvalue) AS balance FROM cash WHERE customerid IS NOT NULL GROUP BY customerid)
");

$this->Execute("
    CREATE FUNCTION customerbalances_update()
        RETURNS trigger
        LANGUAGE plpgsql
        AS $$
            BEGIN
                IF (TG_OP = 'TRUNCATE') THEN
                    DELETE FROM customerbalances;
                    RETURN NULL;                
                ELSEIF (TG_OP = 'DELETE') THEN
                    IF NOT EXISTS (SELECT 1 FROM cash WHERE customerid = OLD.customerid) THEN
                        DELETE FROM customerbalances WHERE customerid = OLD.customerid;
                    ELSE
                        IF EXISTS (SELECT 1 FROM customerbalances WHERE customerid = OLD.customerid) THEN
                            UPDATE customerbalances SET balance = (SELECT SUM(value * currencyvalue) FROM cash WHERE customerid = OLD.customerid) WHERE customerid = OLD.customerid;
                        ELSE
                            INSERT INTO customerbalances (customerid, balance) VALUES (OLD.customerid, (SELECT SUM(value * currencyvalue) FROM cash WHERE customerid = OLD.customerid));
                        END IF;
                    END IF;
                    RETURN NULL;
                ELSEIF (TG_OP = 'UPDATE') THEN
                    IF EXISTS (SELECT 1 FROM customerbalances WHERE customerid = NEW.customerid) THEN
                        UPDATE customerbalances SET balance = (SELECT SUM(value * currencyvalue) FROM cash WHERE customerid = NEW.customerid) WHERE customerid = NEW.customerid;
                    ELSE
                        INSERT INTO customerbalances (customerid, balance) VALUES (NEW.customerid, (SELECT SUM(value * currencyvalue) FROM cash WHERE customerid = NEW.customerid));
                    END IF;
                    RETURN NEW;                
                ELSE
                    IF EXISTS (SELECT 1 FROM customerbalances WHERE customerid = NEW.customerid) THEN
                        UPDATE customerbalances SET balance = (SELECT SUM(value * currencyvalue) FROM cash WHERE customerid = NEW.customerid) WHERE customerid = NEW.customerid;
                    ELSE
                        INSERT INTO customerbalances (customerid, balance) VALUES (NEW.customerid,  (SELECT SUM(value * currencyvalue) FROM cash WHERE customerid = NEW.customerid));
                    END IF;
                    RETURN NEW;
                END IF;
            END;
        $$; 
    CREATE TRIGGER cash_customerbalances_update_trigger AFTER INSERT OR UPDATE OR DELETE ON cash
        FOR EACH ROW
        EXECUTE PROCEDURE customerbalances_update(); 
    CREATE TRIGGER cash_customerbalances_truncate_trigger AFTER TRUNCATE ON cash
        EXECUTE PROCEDURE customerbalances_update() 
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2019121800', 'dbversion'));

$this->CommitTrans();
