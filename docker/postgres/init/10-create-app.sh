#!/usr/bin/env bash

set -euo pipefail

: "${POSTGRES_APP_USER:?POSTGRES_APP_USER is required}"
: "${POSTGRES_APP_PASSWORD:?POSTGRES_APP_PASSWORD is required}"
: "${POSTGRES_APP_DB:?POSTGRES_APP_DB is required}"

psql --set ON_ERROR_STOP=1 \
    --username "${POSTGRES_USER}" \
    --dbname postgres \
    --set app_user="${POSTGRES_APP_USER}" \
    --set app_password="${POSTGRES_APP_PASSWORD}" \
    --set app_db="${POSTGRES_APP_DB}" <<'SQL'
SELECT format('CREATE ROLE %I LOGIN PASSWORD %L', :'app_user', :'app_password') \gexec
SELECT format(
    'CREATE DATABASE %I OWNER %I ENCODING %L LC_COLLATE %L LC_CTYPE %L TEMPLATE template0',
    :'app_db',
    :'app_user',
    'UTF8',
    'C.UTF-8',
    'C.UTF-8'
) \gexec
SELECT format('ALTER ROLE %I SET timezone TO %L', :'app_user', 'UTC') \gexec
SQL

psql --set ON_ERROR_STOP=1 \
    --username "${POSTGRES_USER}" \
    --dbname "${POSTGRES_APP_DB}" <<'SQL'
CREATE EXTENSION IF NOT EXISTS pg_stat_statements;
SQL
