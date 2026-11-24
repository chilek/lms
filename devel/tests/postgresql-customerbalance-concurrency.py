#!/usr/bin/env python3
"""Run only on the disposable database created by test-postgresql-schema.sh."""
import os
import selectors
import subprocess
import time
from decimal import Decimal

CMD = [os.environ.get('PSQL', 'psql'), '-X', '-qAt', '-v', 'ON_ERROR_STOP=1']


def query(sql):
    result = subprocess.run(CMD, input=sql, text=True, capture_output=True, timeout=10)
    if result.returncode:
        raise RuntimeError(result.stderr)
    return result.stdout.strip()


class Session:
    def __init__(self, name):
        self.process = subprocess.Popen(
            CMD, stdin=subprocess.PIPE, stdout=subprocess.PIPE,
            stderr=subprocess.PIPE, text=True, bufsize=1,
            env=dict(os.environ, PGAPPNAME=name),
        )
        self.selector = selectors.DefaultSelector()
        self.selector.register(self.process.stdout, selectors.EVENT_READ)

    def send(self, sql):
        self.process.stdin.write(sql + '\n')
        self.process.stdin.flush()

    def until(self, expected):
        deadline = time.monotonic() + 10
        while time.monotonic() < deadline:
            if not self.selector.select(.1):
                continue
            line = self.process.stdout.readline().strip()
            if line == expected:
                return
            if self.process.poll() is not None:
                raise RuntimeError(self.process.stderr.read())
        raise TimeoutError(expected)

    def close(self):
        if self.process.poll() is None:
            self.process.stdin.close()
            self.process.wait(timeout=10)
        if self.process.returncode:
            raise RuntimeError(self.process.stderr.read())
        self.selector.close()


def wait_for_lock(name):
    deadline = time.monotonic() + 5
    while time.monotonic() < deadline:
        result = query("SELECT wait_event_type FROM pg_stat_activity "
                       "WHERE application_name = '" + name + "';")
        if result == 'Lock':
            return
        time.sleep(.05)
    raise AssertionError(name + ' did not wait for a lock')


def seed(excluded=False, receipt=False):
    query("INSERT INTO customers(id,lastname) VALUES "
          "(1900000401,'audit_dep_concurrent_a'),(1900000402,'audit_dep_concurrent_b');"
          "INSERT INTO cashregs(id,name,balanceexclude) VALUES "
          "(1900000401,'audit_dep_concurrent_reg'," + str(int(excluded)) + ");"
          "INSERT INTO documents(id,type,customerid) VALUES "
          "(1900000401,2,1900000401),(1900000402,2,1900000402);")
    if receipt:
        query(RECEIPT)


def cleanup():
    query("DELETE FROM cash WHERE id IN(1900000401,1900000402);"
          "DELETE FROM receiptcontents WHERE docid IN(1900000401,1900000402);"
          "DELETE FROM documents WHERE id IN(1900000401,1900000402);"
          "DELETE FROM customers WHERE id IN(1900000401,1900000402);"
          "DELETE FROM cashregs WHERE id=1900000401;")


def assert_balance(expected):
    value = query('SELECT balance FROM customerbalances WHERE customerid=1900000401;')
    assert value and Decimal(value) == Decimal(expected), (value, expected)


def race(name, first, second, excluded=False, receipt=False, expected=0, setup='', target_expected=None):
    seed(excluded, receipt)
    if setup:
        query(setup)
    a = Session('audit_dependency_a')
    b = Session('audit_dependency_b')
    try:
        a.send("BEGIN; " + first + " SELECT 'A_READY';")
        a.until('A_READY')
        b.send("BEGIN; " + second + " COMMIT; SELECT 'B_DONE';")
        wait_for_lock('audit_dependency_b')
        a.send("COMMIT; SELECT 'A_DONE';")
        a.until('A_DONE')
        b.until('B_DONE')
        assert_balance(expected)
        if target_expected is not None:
            actual = query('SELECT balance FROM customerbalances WHERE customerid=1900000402;')
            assert actual and Decimal(actual) == Decimal(target_expected), (actual, target_expected)
        print('PASS: ' + name, flush=True)
    finally:
        a.close()
        b.close()
        cleanup()


CASH = ("INSERT INTO cash(id,customerid,docid,itemid,value) "
        "VALUES(1900000401,1900000401,1900000401,1,100);")
RECEIPT = ("INSERT INTO receiptcontents(docid,itemid,value,regid) "
           "VALUES(1900000401,1,100,1900000401);")
TOGGLE = 'UPDATE cashregs SET balanceexclude=1 WHERE id=1900000401;'

race('two first payments for one customer', CASH,
     'INSERT INTO cash(id,customerid,docid,itemid,value) '
     'VALUES(1900000402,1900000401,1900000401,1,50);', expected=150)
race('opposite customer transfers',
     'UPDATE cash SET customerid=1900000402 WHERE id=1900000401;',
     'UPDATE cash SET customerid=1900000401 WHERE id=1900000402;',
     setup='INSERT INTO cash(id,customerid,value) VALUES '
           '(1900000401,1900000401,100),(1900000402,1900000402,50);',
     expected=50, target_expected=100)
race('cash before maintenance rebuild', CASH,
     'SELECT customerbalances_refresh();', expected=100)
race('maintenance rebuild before cash', 'SELECT customerbalances_refresh();',
     CASH, expected=100)
race('cash before first receipt', CASH, RECEIPT, excluded=True)
race('receipt before first cash', RECEIPT, CASH, excluded=True)
race('cash before cash register toggle', CASH, TOGGLE, receipt=True)
race('cash register toggle before first cash', TOGGLE, CASH, receipt=True)
CASH_NULL_DOC = ("INSERT INTO cash(id,customerid,value) "
                 "VALUES(1900000401,1900000401,100);")
race('cash with NULL docid before receipt TRUNCATE', CASH_NULL_DOC,
     'TRUNCATE receiptcontents;', excluded=True, receipt=True, expected=100)
race('receipt TRUNCATE before cash with NULL docid', 'TRUNCATE receiptcontents;',
     CASH_NULL_DOC, excluded=True, receipt=True, expected=100)

seed(receipt=True)
a = Session('audit_dependency_a')
b = Session('audit_dependency_b')
try:
    query("INSERT INTO receiptcontents(docid,itemid,value,regid) "
          "VALUES(1900000402,1,50,1900000401);")
    a.send("BEGIN; " + CASH + " SELECT 'A_READY';")
    a.until('A_READY')
    b.send("BEGIN; INSERT INTO cash(id,customerid,docid,itemid,value) "
           "VALUES(1900000402,1900000402,1900000402,1,50); COMMIT; SELECT 'B_DONE';")
    b.until('B_DONE')
    a.send("COMMIT; SELECT 'A_DONE';")
    a.until('A_DONE')
    assert_balance(100)
    assert Decimal(query('SELECT balance FROM customerbalances WHERE customerid=1900000402;')) == 50
    print('PASS: different documents/customers write concurrently in the same register', flush=True)
finally:
    a.close()
    b.close()
    cleanup()

seed()
try:
    result = query("BEGIN; INSERT INTO cash(id,customerid,value) "
                   "VALUES(1900000401,1900000401,100); "
                   "SELECT COUNT(*) FROM pg_locks WHERE pid=pg_backend_pid() "
                   "AND relation='receiptcontents'::regclass AND mode='AccessShareLock' AND granted; "
                   "ROLLBACK;")
    assert result == '1', result
    print('PASS: cash with NULL docid holds AccessShareLock on receiptcontents', flush=True)
finally:
    cleanup()
