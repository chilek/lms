<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2026 LMS Developers
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
    CREATE OR REPLACE FUNCTION customerbalances_update()
        RETURNS trigger
        LANGUAGE plpgsql
    AS $$
    BEGIN
        IF (TG_OP = 'TRUNCATE') THEN
            DELETE FROM customerbalances;
            RETURN NULL;
        ELSEIF (TG_OP = 'DELETE') THEN
            IF OLD.customerid IS NULL THEN
                RETURN NULL;
            END IF;
            IF NOT EXISTS (SELECT 1 FROM cash WHERE customerid = OLD.customerid) THEN
                DELETE FROM customerbalances WHERE customerid = OLD.customerid;
            ELSE
                IF EXISTS (SELECT 1 FROM customerbalances WHERE customerid = OLD.customerid) THEN
                    UPDATE customerbalances
                    SET balance = (
                        SELECT
                            SUM(CASE WHEN COALESCE(cr.disabled, 0) = 0 THEN cash.value * currencyvalue ELSE 0 END)
                        FROM cash
                        LEFT JOIN receiptcontents rc ON rc.docid = cash.docid AND rc.itemid = cash.itemid
                        LEFT JOIN cashregs cr ON cr.id = rc.regid
                        WHERE customerid = OLD.customerid
                    ) WHERE customerid = OLD.customerid;
                ELSE
                    INSERT INTO customerbalances (customerid, balance) VALUES (
                        OLD.customerid,
                        (
                            SELECT
                                SUM(CASE WHEN COALESCE(cr.disabled, 0) = 0 THEN cash.value * currencyvalue ELSE 0 END)
                            FROM cash
                            LEFT JOIN receiptcontents rc ON rc.docid = cash.docid AND rc.itemid = cash.itemid
                            LEFT JOIN cashregs cr ON cr.id = rc.regid
                            WHERE customerid = OLD.customerid
                        )
                    );
                END IF;
            END IF;
            RETURN NULL;
        ELSEIF (TG_OP = 'UPDATE') THEN
            IF OLD.value = NEW.value AND OLD.currencyvalue = NEW.currencyvalue THEN
                RETURN NEW;
            END IF;
            IF OLD.customerid IS NOT NULL AND OLD.customerid <> NEW.customerid THEN
                IF EXISTS (SELECT 1 FROM customerbalances WHERE customerid = OLD.customerid) THEN
                    UPDATE customerbalances
                    SET balance = (
                        SELECT
                            SUM(CASE WHEN COALESCE(cr.disabled, 0) = 0 THEN cash.value * currencyvalue ELSE 0 END)
                        FROM cash
                        LEFT JOIN receiptcontents rc ON rc.docid = cash.docid AND rc.itemid = cash.itemid
                        LEFT JOIN cashregs cr ON cr.id = rc.regid
                        WHERE customerid = OLD.customerid
                    ) WHERE customerid = OLD.customerid;
                ELSE
                    INSERT INTO customerbalances (customerid, balance) VALUES (
                        OLD.customerid,
                        (
                            SELECT
                                SUM(CASE WHEN COALESCE(cr.disabled, 0) = 0 THEN cash.value * currencyvalue ELSE 0 END)
                            FROM cash
                            LEFT JOIN receiptcontents rc ON rc.docid = cash.docid AND rc.itemid = cash.itemid
                            LEFT JOIN cashregs cr ON cr.id = rc.regid
                            WHERE customerid = OLD.customerid
                        )
                    );
                END IF;
            END IF;
            IF NEW.customerid IS NULL THEN
                RETURN NEW;
            END IF;
            IF EXISTS (SELECT 1 FROM customerbalances WHERE customerid = NEW.customerid) THEN
                UPDATE customerbalances
                SET balance = (
                    SELECT
                        SUM(CASE WHEN COALESCE(cr.disabled, 0) = 0 THEN cash.value * currencyvalue ELSE 0 END)
                    FROM cash
                    LEFT JOIN receiptcontents rc ON rc.docid = cash.docid AND rc.itemid = cash.itemid
                    LEFT JOIN cashregs cr ON cr.id = rc.regid
                    WHERE customerid = NEW.customerid
                ) WHERE customerid = NEW.customerid;
            ELSE
                INSERT INTO customerbalances (customerid, balance) VALUES (
                    NEW.customerid,
                    (
                        SELECT
                            SUM(CASE WHEN COALESCE(cr.disabled, 0) THEN cash.value * currencyvalue ELSE 0 END)
                        FROM cash
                        LEFT JOIN receiptcontents rc ON rc.docid = cash.docid AND rc.itemid = cash.itemid
                        LEFT JOIN cashregs cr ON cr.id = rc.regid
                        WHERE customerid = NEW.customerid
                    )
                );
            END IF;
            RETURN NEW;
        ELSE
            IF NEW.customerid IS NULL THEN
                RETURN NEW;
            END IF;
            IF EXISTS (SELECT 1 FROM customerbalances WHERE customerid = NEW.customerid) THEN
                UPDATE customerbalances
                SET balance = (
                    SELECT
                        SUM(CASE WHEN COALESCE(cr.disabled, 0) = 0 THEN cash.value * currencyvalue ELSE 0 END)
                    FROM cash
                    LEFT JOIN receiptcontents rc ON rc.docid = cash.docid AND rc.itemid = cash.itemid
                    LEFT JOIN cashregs cr ON cr.id = rc.regid
                    WHERE customerid = NEW.customerid
                ) WHERE customerid = NEW.customerid;
            ELSE
                INSERT INTO customerbalances (customerid, balance) VALUES (
                    NEW.customerid,
                    (
                        SELECT
                            SUM(CASE WHEN COALESCE(cr.disabled, 0) = 0 THEN cash.value * currencyvalue ELSE 0 END)
                        FROM cash
                        LEFT JOIN receiptcontents rc ON rc.docid = cash.docid AND rc.itemid = cash.itemid
                        LEFT JOIN cashregs cr ON cr.id = rc.regid
                        WHERE customerid = NEW.customerid
                    )
                );
            END IF;
            RETURN NEW;
        END IF;
    END;
    $$
");

$this->Execute("
    CREATE OR REPLACE FUNCTION customerbalances_refresh()
        RETURNS void
        LANGUAGE plpgsql
    AS $$
    BEGIN
        DELETE FROM customerbalances;
        INSERT INTO customerbalances (customerid, balance) (
            SELECT
                cash.customerid,
                SUM(CASE WHEN COALESCE(cr.disabled, 0) = 0 THEN cash.value * currencyvalue ELSE 0 END)
            FROM cash
            LEFT JOIN receiptcontents rc ON rc.docid = cash.docid AND rc.itemid = cash.itemid
            LEFT JOIN cashregs cr ON cr.id = rc.regid
            WHERE cash.customerid IS NOT NULL
            GROUP BY cash.customerid
        );
    END;
    $$
");

$this->Execute("SELECT customerbalances_refresh()");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026102100', 'dbversion'));

$this->CommitTrans();
