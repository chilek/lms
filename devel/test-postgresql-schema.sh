#!/bin/sh
# Requirements: PostgreSQL client tools, Python 3, and permission to create a
# disposable database. Set PGHOST, PGPORT and PGUSER as for psql. Tested on PG17.
set -eu

schema_test_dir=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
schema_test_db="lms-schema-test-$$"

createdb "$schema_test_db"
trap 'dropdb --if-exists "$schema_test_db"' EXIT
trap 'exit 1' HUP INT TERM
export PGDATABASE="$schema_test_db"
export PGOPTIONS="${PGOPTIONS:-} -c client_min_messages=warning -c statement_timeout=15000"

psql -X -q -v ON_ERROR_STOP=1 -f "$schema_test_dir/../doc/lms.pgsql" >/dev/null
psql -X -q -v ON_ERROR_STOP=1 -f "$schema_test_dir/../doc/lms.pgsql" >/dev/null
echo 'PASS: fresh schema and repeated schema import'

psql -X -q -v ON_ERROR_STOP=1 <<'SQL'
BEGIN;
DO $$
DECLARE
    generated_id integer;
BEGIN
    INSERT INTO netdeviceproducers (name) VALUES ('schema regression') RETURNING id INTO generated_id;
    IF generated_id <> 3 THEN
        RAISE EXCEPTION 'Expected the first custom producer after the seeded rows, got %', generated_id;
    END IF;
    INSERT INTO tsigkeys (name, algorithm, secret)
        VALUES ('schema-regression', 'hmac-sha256', 'test') RETURNING id INTO generated_id;
    IF generated_id <> 1 THEN
        RAISE EXCEPTION 'Unexpected first TSIG key id: %', generated_id;
    END IF;
END;
$$;
ROLLBACK;
SQL
echo 'PASS: first inserts after seeding'

psql -X -q -v ON_ERROR_STOP=1 -f "$schema_test_dir/tests/postgresql-customerbalances.sql" >/dev/null
psql -X -q -v ON_ERROR_STOP=1 -f "$schema_test_dir/tests/postgresql-customerbalance-dependencies.sql" >/dev/null
echo 'PASS: cash, receipt, register and cascade regressions'

python3 "$schema_test_dir/tests/postgresql-customerbalance-concurrency.py"
