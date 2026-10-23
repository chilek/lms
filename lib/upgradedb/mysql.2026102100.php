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

$this->Execute("DROP TRIGGER cash_customerbalances_delete_trigger");
$this->Execute("DROP TRIGGER cash_customerbalances_update_trigger");
$this->Execute("DROP TRIGGER cash_customerbalances_insert_trigger");

$this->Execute("DROP PROCEDURE IF EXISTS customerbalances_refresh");

$this->Execute("
    CREATE TRIGGER cash_customerbalances_insert_trigger AFTER INSERT ON cash
        FOR EACH ROW
        BEGIN
            IF NEW.customerid IS NOT NULL THEN
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
            END IF;
        END
");

$this->Execute("
    CREATE TRIGGER cash_customerbalances_update_trigger AFTER UPDATE ON cash
        FOR EACH ROW
        BEGIN
            IF OLD.value <> NEW.value OR OLD.currencyvalue <> NEW.currencyvalue THEN
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
                IF NEW.customerid IS NOT NULL THEN
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
                END IF;
            END IF;
        END
");

$this->Execute("
    CREATE TRIGGER cash_customerbalances_delete_trigger AFTER DELETE ON cash
        FOR EACH ROW
        BEGIN
            IF OLD.customerid IS NOT NULL THEN
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
            END IF;
        END
");

$this->Execute("
    DELIMITER $$
    CREATE PROCEDURE customerbalances_refresh()
    BEGIN
        DELETE FROM customerbalances;

        INSERT INTO customerbalances (customerid, balance)
        SELECT
            cash.customerid,
            SUM(CASE WHEN COALESCE(cr.disabled, 0) = 0 THEN cash.value * cash.currencyvalue ELSE 0 END)
        FROM cash
        LEFT JOIN receiptcontents rc ON rc.docid = cash.docid AND rc.itemid = cash.itemid
        LEFT JOIN cashregs cr ON cr.id = rc.regid
        GROUP BY cash.customerid;
    END$$
    DELIMITER ;
");

$this->Execute("CALL customerbalances_refresh()");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026102100', 'dbversion'));

$this->CommitTrans();
