\set ON_ERROR_STOP on
BEGIN;
INSERT INTO customers (id, lastname) VALUES (1900000301, 'audit_dependency_customer');
INSERT INTO cashregs (id, name, balanceexclude) VALUES
    (1900000301, 'audit_dependency_included', 0),
    (1900000302, 'audit_dependency_excluded', 1);
INSERT INTO documents (id, type, customerid) VALUES
    (1900000301, 2, 1900000301), (1900000302, 2, 1900000301);
INSERT INTO cash (id, customerid, docid, itemid, value)
    VALUES (1900000301, 1900000301, 1900000301, 1, 100);
DO $$ BEGIN
    ASSERT (SELECT balance = 100 FROM customerbalances WHERE customerid = 1900000301);
END $$;
INSERT INTO receiptcontents (docid, itemid, value, regid)
    VALUES (1900000301, 1, 100, 1900000302);
DO $$ BEGIN
    ASSERT (SELECT balance = 0 FROM customerbalances WHERE customerid = 1900000301);
END $$;
UPDATE receiptcontents SET regid = 1900000301 WHERE docid = 1900000301;
DO $$ BEGIN
    ASSERT (SELECT balance = 100 FROM customerbalances WHERE customerid = 1900000301);
END $$;
UPDATE cashregs SET balanceexclude = 1 WHERE id = 1900000301;
DO $$ BEGIN
    ASSERT (SELECT balance = 0 FROM customerbalances WHERE customerid = 1900000301);
END $$;
UPDATE receiptcontents SET itemid = 2 WHERE docid = 1900000301;
DO $$ BEGIN
    ASSERT (SELECT balance = 100 FROM customerbalances WHERE customerid = 1900000301);
END $$;
UPDATE receiptcontents SET itemid = 1 WHERE docid = 1900000301;
UPDATE receiptcontents SET docid = 1900000302 WHERE docid = 1900000301;
DO $$ BEGIN
    ASSERT (SELECT balance = 100 FROM customerbalances WHERE customerid = 1900000301);
END $$;
UPDATE receiptcontents SET docid = 1900000301 WHERE docid = 1900000302;
DO $$ BEGIN
    ASSERT (SELECT balance = 0 FROM customerbalances WHERE customerid = 1900000301);
END $$;
DELETE FROM receiptcontents WHERE docid = 1900000301;
DO $$ BEGIN
    ASSERT (SELECT balance = 100 FROM customerbalances WHERE customerid = 1900000301);
END $$;
INSERT INTO receiptcontents (docid, itemid, value, regid)
    VALUES (1900000301, 1, 100, 1900000302);
DELETE FROM cashregs WHERE id = 1900000302;
DO $$ BEGIN
    ASSERT (SELECT regid IS NULL FROM receiptcontents WHERE docid = 1900000301);
    ASSERT (SELECT balance = 100 FROM customerbalances WHERE customerid = 1900000301);
END $$;
UPDATE receiptcontents SET regid = 1900000301 WHERE docid = 1900000301;
DO $$ BEGIN
    ASSERT (SELECT balance = 0 FROM customerbalances WHERE customerid = 1900000301);
END $$;
TRUNCATE receiptcontents;
DO $$ BEGIN
    ASSERT (SELECT balance = 100 FROM customerbalances WHERE customerid = 1900000301);
END $$;

-- Key cascades must preserve the joined cash/register identity.
INSERT INTO receiptcontents (docid, itemid, value, regid)
    VALUES (1900000301, 1, 100, 1900000301);
UPDATE cashregs SET id = 1900000303, balanceexclude = 0 WHERE id = 1900000301;
DO $$ BEGIN
    ASSERT (SELECT regid = 1900000303 FROM receiptcontents WHERE docid = 1900000301);
    ASSERT (SELECT balance = 100 FROM customerbalances WHERE customerid = 1900000301);
END $$;
UPDATE cashregs SET balanceexclude = 1 WHERE id = 1900000303;
UPDATE documents SET id = 1900000303 WHERE id = 1900000301;
DO $$ BEGIN
    ASSERT (SELECT docid = 1900000303 FROM cash WHERE id = 1900000301);
    ASSERT (SELECT balance = 0 FROM customerbalances WHERE customerid = 1900000301);
END $$;
UPDATE customers SET id = 1900000303 WHERE id = 1900000301;
DO $$ BEGIN
    ASSERT (SELECT customerid = 1900000303 FROM cash WHERE id = 1900000301);
    ASSERT (SELECT balance = 0 FROM customerbalances WHERE customerid = 1900000303);
END $$;
DELETE FROM documents WHERE id = 1900000303;
DO $$ BEGIN
    ASSERT NOT EXISTS (SELECT 1 FROM cash WHERE id = 1900000301);
    ASSERT NOT EXISTS (SELECT 1 FROM customerbalances WHERE customerid = 1900000303);
END $$;
ROLLBACK;
