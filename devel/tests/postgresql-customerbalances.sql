\set ON_ERROR_STOP on
BEGIN;

-- Ordinary inserts, updates, transfers and deletes must agree with a full sum.
INSERT INTO customers (id, lastname) VALUES
    (1900000101, 'audit_balance_customer'), (1900000102, 'audit_balance_target');
INSERT INTO cash (id, customerid, value) VALUES
    (1900000101, 1900000101, 100), (1900000102, 1900000101, 50);
DO $$ BEGIN
    ASSERT (SELECT balance = 150 FROM customerbalances WHERE customerid = 1900000101);
END $$;
UPDATE cash SET currencyvalue = 2 WHERE id = 1900000102;
DO $$ BEGIN
    ASSERT (SELECT balance = 200 FROM customerbalances WHERE customerid = 1900000101);
END $$;
UPDATE cash SET customerid = 1900000102 WHERE id = 1900000102;
DO $$ BEGIN
    ASSERT (SELECT balance = 100 FROM customerbalances WHERE customerid = 1900000101);
    ASSERT (SELECT balance = 100 FROM customerbalances WHERE customerid = 1900000102);
END $$;
UPDATE cash SET customerid = NULL WHERE id = 1900000101;
DO $$ BEGIN
    ASSERT NOT EXISTS (SELECT 1 FROM customerbalances WHERE customerid = 1900000101);
END $$;
DELETE FROM cash WHERE id = 1900000102;
DO $$ BEGIN
    ASSERT NOT EXISTS (SELECT 1 FROM customerbalances WHERE customerid = 1900000102);
END $$;

-- A disappearing parent must not be reinserted into the cache by its cash FK.
INSERT INTO cash (id, customerid, value) VALUES (1900000103, 1900000101, 100);
DELETE FROM customers WHERE id = 1900000101;
DO $$ BEGIN
    ASSERT (SELECT customerid IS NULL FROM cash WHERE id = 1900000103);
    ASSERT NOT EXISTS (SELECT 1 FROM customerbalances WHERE customerid = 1900000101);
END $$;
INSERT INTO cash (id, customerid, value) VALUES (1900000104, 1900000102, 75);
UPDATE customers SET id = 1900000103 WHERE id = 1900000102;
DO $$ BEGIN
    ASSERT (SELECT customerid = 1900000103 FROM cash WHERE id = 1900000104);
    ASSERT (SELECT balance = 75 FROM customerbalances WHERE customerid = 1900000103);
    ASSERT NOT EXISTS (SELECT 1 FROM customerbalances WHERE customerid = 1900000102);
END $$;

-- An unknown exchange rate is excluded by SUM, including when every rate is NULL.
INSERT INTO customers (id, lastname) VALUES (1900000104, 'audit_balance_unknown_rate');
INSERT INTO cash (id, customerid, value, currencyvalue)
    VALUES (1900000105, 1900000104, 100, NULL);
DO $$ BEGIN
    ASSERT (SELECT balance = 0 FROM customerbalances WHERE customerid = 1900000104);
END $$;
SELECT customerbalances_refresh();
DO $$ BEGIN
    ASSERT (SELECT balance = 0 FROM customerbalances WHERE customerid = 1900000104);
END $$;
UPDATE cash SET currencyvalue = 3 WHERE id = 1900000105;
DO $$ BEGIN
    ASSERT (SELECT balance = 300 FROM customerbalances WHERE customerid = 1900000104);
END $$;
UPDATE cash SET currencyvalue = NULL WHERE id = 1900000105;
DO $$ BEGIN
    ASSERT (SELECT balance = 0 FROM customerbalances WHERE customerid = 1900000104);
END $$;

-- Moving a cash row between receipt items/documents changes exclusion status.
INSERT INTO cashregs (id, name, balanceexclude)
    VALUES (1900000101, 'audit_balance_excluded', 1);
INSERT INTO documents (id, type, customerid) VALUES
    (1900000101, 2, 1900000103), (1900000102, 2, 1900000103);
INSERT INTO receiptcontents (docid, itemid, value, regid)
    VALUES (1900000101, 1, 75, 1900000101), (1900000102, 2, 75, 1900000101);
UPDATE cash SET docid = 1900000101, itemid = 1 WHERE id = 1900000104;
DO $$ BEGIN
    ASSERT (SELECT balance = 0 FROM customerbalances WHERE customerid = 1900000103);
END $$;
UPDATE cash SET itemid = 2 WHERE id = 1900000104;
DO $$ BEGIN
    ASSERT (SELECT balance = 75 FROM customerbalances WHERE customerid = 1900000103);
END $$;
UPDATE cash SET docid = 1900000102 WHERE id = 1900000104;
DO $$ BEGIN
    ASSERT (SELECT balance = 0 FROM customerbalances WHERE customerid = 1900000103);
END $$;
UPDATE cash SET docid = NULL WHERE id = 1900000104;
DO $$ BEGIN
    ASSERT (SELECT balance = 75 FROM customerbalances WHERE customerid = 1900000103);
END $$;

TRUNCATE cash;
DO $$ BEGIN
    ASSERT NOT EXISTS (SELECT 1 FROM customerbalances);
END $$;
ROLLBACK;
