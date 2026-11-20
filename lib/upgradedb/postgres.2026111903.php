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
CREATE OR REPLACE FUNCTION customerbalances_recalculate(affected_customers integer[])
    RETURNS void
    LANGUAGE plpgsql
AS $$
DECLARE
    affected_customer integer;
    calculated_balance numeric;
BEGIN
    affected_customers := ARRAY(
        SELECT DISTINCT customerid
        FROM unnest(affected_customers) AS affected(customerid)
        WHERE customerid IS NOT NULL
        ORDER BY customerid
    );
    FOREACH affected_customer IN ARRAY affected_customers LOOP
        PERFORM pg_advisory_xact_lock(hashtext('customerbalances'), affected_customer);
    END LOOP;
    FOREACH affected_customer IN ARRAY affected_customers LOOP
        -- Cascades may already have removed or renumbered the parent customer.
        IF NOT EXISTS (SELECT 1 FROM customers WHERE id = affected_customer) THEN
            DELETE FROM customerbalances WHERE customerid = affected_customer;
            CONTINUE;
        END IF;
        IF NOT EXISTS (SELECT 1 FROM cash WHERE customerid = affected_customer) THEN
            DELETE FROM customerbalances WHERE customerid = affected_customer;
        ELSE
            SELECT COALESCE(SUM(CASE WHEN COALESCE(cr.balanceexclude, 0) = 0
                THEN c.value * c.currencyvalue ELSE 0 END), 0)
            INTO calculated_balance
            FROM cash c
            LEFT JOIN receiptcontents rc ON rc.docid = c.docid AND rc.itemid = c.itemid
            LEFT JOIN cashregs cr ON cr.id = rc.regid
            WHERE c.customerid = affected_customer;
            UPDATE customerbalances SET balance = calculated_balance
            WHERE customerid = affected_customer;
            IF NOT FOUND THEN
                INSERT INTO customerbalances (customerid, balance)
                VALUES (affected_customer, calculated_balance);
            END IF;
        END IF;
    END LOOP;
END;
$$;
");

$this->Execute("
CREATE OR REPLACE FUNCTION customerbalances_update()
    RETURNS trigger
    LANGUAGE plpgsql
AS $$
BEGIN
    IF TG_OP = 'TRUNCATE' THEN
        DELETE FROM customerbalances;
    ELSIF TG_OP = 'UPDATE' THEN
        IF OLD.value IS NOT DISTINCT FROM NEW.value
            AND OLD.currencyvalue IS NOT DISTINCT FROM NEW.currencyvalue
            AND OLD.customerid IS NOT DISTINCT FROM NEW.customerid
            AND OLD.docid IS NOT DISTINCT FROM NEW.docid
            AND OLD.itemid IS NOT DISTINCT FROM NEW.itemid THEN
            RETURN NULL;
        END IF;
        PERFORM customerbalances_recalculate(ARRAY[OLD.customerid, NEW.customerid]);
    ELSIF TG_OP = 'DELETE' THEN
        PERFORM customerbalances_recalculate(ARRAY[OLD.customerid]);
    ELSE
        PERFORM customerbalances_recalculate(ARRAY[NEW.customerid]);
    END IF;
    RETURN NULL;
END;
$$;
");

$this->Execute("
CREATE OR REPLACE FUNCTION customerbalances_refresh()
    RETURNS void
    LANGUAGE plpgsql
AS $$
BEGIN
    -- Keep the source rows stable throughout the maintenance rebuild.
    LOCK TABLE cash, receiptcontents, cashregs IN SHARE MODE;
    DELETE FROM customerbalances;
    INSERT INTO customerbalances (customerid, balance) (
        SELECT
            cash.customerid,
            COALESCE(SUM(CASE WHEN COALESCE(cr.balanceexclude, 0) = 0
                THEN cash.value * currencyvalue ELSE 0 END), 0)
        FROM cash
        LEFT JOIN receiptcontents rc ON rc.docid = cash.docid AND rc.itemid = cash.itemid
        LEFT JOIN cashregs cr ON cr.id = rc.regid
        WHERE cash.customerid IS NOT NULL
        GROUP BY cash.customerid
    );
END;
$$;
");

$this->Execute("
CREATE OR REPLACE FUNCTION customerbalances_lock_cash_receipts()
    RETURNS trigger
    LANGUAGE plpgsql
AS $$
DECLARE
    affected_documents integer[];
    affected_document integer;
BEGIN
    IF TG_OP = 'UPDATE' THEN
        IF OLD.value IS NOT DISTINCT FROM NEW.value
            AND OLD.currencyvalue IS NOT DISTINCT FROM NEW.currencyvalue
            AND OLD.customerid IS NOT DISTINCT FROM NEW.customerid
            AND OLD.docid IS NOT DISTINCT FROM NEW.docid
            AND OLD.itemid IS NOT DISTINCT FROM NEW.itemid THEN
            RETURN NEW;
        END IF;
        affected_documents := ARRAY[OLD.docid, NEW.docid];
    ELSIF TG_OP = 'DELETE' THEN
        affected_documents := ARRAY[OLD.docid];
    ELSE
        affected_documents := ARRAY[NEW.docid];
    END IF;

    FOR affected_document IN
        SELECT DISTINCT docid
        FROM unnest(affected_documents) AS affected(docid)
        WHERE docid IS NOT NULL
        ORDER BY docid
    LOOP
        PERFORM pg_advisory_xact_lock_shared(hashtext('customerbalances.receipts'), affected_document);
    END LOOP;

    -- This separate statement sees the committed receipt mapping after waiting
    -- for its document lock. SHARE also waits for a cash register flag update.
    -- Keep the query even for NULL docids: its relation lock protects TRUNCATE.
    PERFORM id
    FROM cashregs
    WHERE id IN (
        SELECT regid FROM receiptcontents
        WHERE docid = ANY(affected_documents)
    )
    ORDER BY id
    FOR SHARE;

    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;
    RETURN NEW;
END;
$$;
");

$this->Execute("
CREATE OR REPLACE FUNCTION customerbalances_lock_receipt_changes()
    RETURNS trigger
    LANGUAGE plpgsql
AS $$
DECLARE
    affected_documents integer[];
    affected_registers integer[];
    affected_document integer;
BEGIN
    IF TG_OP = 'UPDATE' THEN
        IF OLD.docid IS NOT DISTINCT FROM NEW.docid
            AND OLD.itemid IS NOT DISTINCT FROM NEW.itemid
            AND OLD.regid IS NOT DISTINCT FROM NEW.regid THEN
            RETURN NEW;
        END IF;
        affected_documents := ARRAY[OLD.docid, NEW.docid];
        affected_registers := ARRAY[OLD.regid, NEW.regid];
    ELSIF TG_OP = 'DELETE' THEN
        affected_documents := ARRAY[OLD.docid];
        affected_registers := ARRAY[OLD.regid];
    ELSE
        affected_documents := ARRAY[NEW.docid];
        affected_registers := ARRAY[NEW.regid];
    END IF;

    FOR affected_document IN
        SELECT DISTINCT docid
        FROM unnest(affected_documents) AS affected(docid)
        WHERE docid IS NOT NULL
        ORDER BY docid
    LOOP
        PERFORM pg_advisory_xact_lock(hashtext('customerbalances.receipts'), affected_document);
    END LOOP;
    PERFORM id FROM cashregs
    WHERE id = ANY(affected_registers)
    ORDER BY id
    FOR SHARE;

    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;
    RETURN NEW;
END;
$$;
");

$this->Execute("
CREATE OR REPLACE FUNCTION customerbalances_update_receipts()
    RETURNS trigger
    LANGUAGE plpgsql
AS $$
DECLARE
    affected_customers integer[];
    old_document integer;
    old_item smallint;
    new_document integer;
    new_item smallint;
BEGIN
    IF TG_OP = 'TRUNCATE' THEN
        -- TRUNCATE already owns ACCESS EXCLUSIVE on receiptcontents. Every
        -- balance-changing cash statement reads that table in its BEFORE
        -- trigger, so no further source-table locks are needed here.
        affected_customers := ARRAY(
            SELECT customerid FROM cash WHERE customerid IS NOT NULL
            UNION
            SELECT customerid FROM customerbalances
        );
        PERFORM customerbalances_recalculate(affected_customers);
        RETURN NULL;
    END IF;
    IF TG_OP = 'UPDATE' THEN
        IF OLD.docid IS NOT DISTINCT FROM NEW.docid
            AND OLD.itemid IS NOT DISTINCT FROM NEW.itemid
            AND OLD.regid IS NOT DISTINCT FROM NEW.regid THEN
            RETURN NULL;
        END IF;
    END IF;
    IF TG_OP <> 'INSERT' THEN
        old_document := OLD.docid;
        old_item := OLD.itemid;
    END IF;
    IF TG_OP <> 'DELETE' THEN
        new_document := NEW.docid;
        new_item := NEW.itemid;
    END IF;
    -- Membership is read only after the BEFORE trigger has obtained its locks.
    affected_customers := ARRAY(
        SELECT DISTINCT customerid FROM cash
        WHERE customerid IS NOT NULL
            AND ((docid = old_document AND itemid = old_item)
                OR (docid = new_document AND itemid = new_item))
        ORDER BY customerid
    );
    PERFORM customerbalances_recalculate(affected_customers);
    RETURN NULL;
END;
$$;
");

$this->Execute("
CREATE OR REPLACE FUNCTION customerbalances_update_cashregs()
    RETURNS trigger
    LANGUAGE plpgsql
AS $$
DECLARE
    affected_customers integer[];
BEGIN
    IF OLD.balanceexclude IS NOT DISTINCT FROM NEW.balanceexclude THEN
        RETURN NULL;
    END IF;
    -- The UPDATE row lock excludes concurrent SHARE readers in cash/receipts.
    affected_customers := ARRAY(
        SELECT DISTINCT c.customerid
        FROM cash c
        JOIN receiptcontents rc ON rc.docid = c.docid AND rc.itemid = c.itemid
        WHERE rc.regid = NEW.id AND c.customerid IS NOT NULL
        ORDER BY c.customerid
    );
    PERFORM customerbalances_recalculate(affected_customers);
    RETURN NULL;
END;
$$;
");

$this->Execute("DROP TRIGGER IF EXISTS cash_customerbalances_lock_receipts_trigger ON cash");

$this->Execute("
CREATE TRIGGER cash_customerbalances_lock_receipts_trigger
    BEFORE INSERT OR UPDATE OR DELETE ON cash
    FOR EACH ROW EXECUTE PROCEDURE customerbalances_lock_cash_receipts();
");

$this->Execute("DROP TRIGGER IF EXISTS receiptcontents_customerbalances_lock_trigger ON receiptcontents");

$this->Execute("
CREATE TRIGGER receiptcontents_customerbalances_lock_trigger
    BEFORE INSERT OR UPDATE OR DELETE ON receiptcontents
    FOR EACH ROW EXECUTE PROCEDURE customerbalances_lock_receipt_changes();
");

$this->Execute("DROP TRIGGER IF EXISTS receiptcontents_customerbalances_update_trigger ON receiptcontents");

$this->Execute("
CREATE TRIGGER receiptcontents_customerbalances_update_trigger
    AFTER INSERT OR UPDATE OR DELETE ON receiptcontents
    FOR EACH ROW EXECUTE PROCEDURE customerbalances_update_receipts();
");

$this->Execute("DROP TRIGGER IF EXISTS receiptcontents_customerbalances_truncate_trigger ON receiptcontents");

$this->Execute("
CREATE TRIGGER receiptcontents_customerbalances_truncate_trigger
    AFTER TRUNCATE ON receiptcontents
    EXECUTE PROCEDURE customerbalances_update_receipts();
");

$this->Execute("DROP TRIGGER IF EXISTS cashregs_customerbalances_update_trigger ON cashregs");

$this->Execute("
CREATE TRIGGER cashregs_customerbalances_update_trigger
    AFTER UPDATE ON cashregs
    FOR EACH ROW EXECUTE PROCEDURE customerbalances_update_cashregs();
");

$this->Execute("SELECT customerbalances_refresh()");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026111903', 'dbversion'));

$this->CommitTrans();
