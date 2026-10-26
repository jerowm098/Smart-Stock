================================================================================
SUPABASE SECTION — INSERT-READY TABLES
Document: SMARTSTOCK-[High-Level] Infrastructure and Workflow for Smart-Stock-041026-125710.pdf
Insertion point: page 4, under the existing heading
                "DATABASE MIGRATIONS AND BACKUP (Supabase)"

DO NOT DELETE the existing "DATABASE MIGRATIONS:" and "BACKUP AND RESTORE:"
prose blocks — these tables sit alongside them as the structured version.
Nothing in the source PDF was modified.
================================================================================


--------------------------------------------------------------------------------
TABLE 1 — SUPABASE CONFIGURATION UPDATE LOG      <-- the "updates" table
Insert directly under: DATABASE MIGRATIONS AND BACKUP (Supabase)
--------------------------------------------------------------------------------

Why this table exists: a configuration change that nobody announced looks exactly
like a bug. This log makes every Supabase change visible, dated, and attributable.

| # | Date | Change | Affected Environment | Reason | Verified By |
|---|---|---|---|---|---|
| 1 | ____________ | Project provisioned on Supabase free tier | All | Base database created for the capstone | ____________ |
| 2 | ____________ | Engine set to PostgreSQL, `pgsql` driver | All | Supabase is the primary database; SQLite is testing-only | ____________ |
| 3 | ____________ | Supavisor pooler adopted, port 6543 | Local, Test | Globe PH network is IPv4-only; direct host is unreachable | ____________ |
| 4 | ____________ | Migration path separated to port 5432 | Local | Transaction Pooler cannot execute migrations | ____________ |
| 5 | ____________ | `DB_SSLMODE=require` set on all environments | All | Supabase requires TLS for pooled connections | ____________ |
| 6 | ____________ | First daily snapshot cycle observed | Test | Confirms Layer 1 backup is active | ____________ |
| 7 | ____________ | First JSON dump executed via `backup:run` | Test | Confirms Layer 2 backup is active | ____________ |
| 8 | ____________ | First restore test completed | Test | Snapshot proven recoverable, not assumed | ____________ |
| 9 | ____________ | ____________________ | ______ | ____________________ | ____________ |
| 10 | ____________ | ____________________ | ______ | ____________________ | ____________ |

Rule: no row is left blank. If a value changed and it is not in this table, it was
an undocumented change and must be corrected here.


--------------------------------------------------------------------------------
TABLE 2 — SUPABASE CONNECTION PATHS
Replaces the single prose sentence about 5432 vs 6543.
--------------------------------------------------------------------------------

| Path | Host | Port | Protocol | Used By | Rule |
|---|---|---|---|---|---|
| Runtime | Supavisor Transaction Pooler (aws-0-ap-REGION.pooler.supabase.com) | 6543 | IPv4 | Test and Production runtime on Render | Default path for all application traffic |
| Migration | Supavisor Session Pooler (same pooler host) | 5432 | IPv4 | `php artisan migrate` only | Port 6543 cannot execute migrations |
| Direct | db.PROJECTREF.supabase.co | 5432 | IPv6 | Not currently used | Unreachable from Globe PH IPv4 network |

Why two ports: the Transaction Pooler multiplexes connections and does not retain the
prepared-statement session state that Laravel migrations depend on. Migrations therefore
use the Session Pooler on port 5432. This is a connection-type restriction, not a
preference — using the wrong port fails mid-run, after partial schema changes.

Credentials: DB_USERNAME differs between paths. Pooler requires the
`postgres.PROJECTREF` form; direct requires plain `postgres`. Both are supplied by the
Supabase Dashboard Connect modal and are never typed by hand.


--------------------------------------------------------------------------------
TABLE 3 — DATA RESIDENCY BY ENVIRONMENT
New. Answers "which database does each environment actually write to?"
--------------------------------------------------------------------------------

| Environment | Database | Data State | Who Writes | Reset Method |
|---|---|---|---|---|
| Local | Supabase developer project | Seeded, resettable | Developer only | Re-run seeders |
| Test | Supabase shared project | Live shared data | Developers via auto-deploy | Manual reseed, coordinated |
| Staging | Not yet provisioned | Not applicable | Not applicable | Not applicable |
| Production | Supabase shared project | Live shared data | Team via approved deploy | Snapshot restore only |

Note: Local and Test resolve to separate Supabase projects. Staging and Production are
not yet provisioned and currently resolve to the Test database, which is why no
independent reset procedure is defined for them.


--------------------------------------------------------------------------------
TABLE 4 — BACKUP AND RESTORE COVERAGE
Extends the existing 3-row What / Retention / Coverage table.
--------------------------------------------------------------------------------

| Attribute | Value |
|---|---|
| Layer 1 — What | Supabase managed daily snapshot of the full PostgreSQL database |
| Layer 1 — Retention | 7 days |
| Layer 1 — Scope | All tables: users, products, suppliers, sales, sale items, stock ins, stock adjustments, alerts, order suggestions |
| Layer 2 — What | Application-level JSON dump via `php artisan backup:run` |
| Layer 2 — Location | `storage/app/private/backups` — outside the public web root |
| Layer 2 — Access | Admin > Backups page, authenticated roles only |
| Restore procedure | Restore the snapshot into a scratch database, then verify a seeded account and its related records are intact |
| Restore tested | Once per milestone, before the sprint review |
| Restore duration | ____ minutes (record the actual time taken) |
| Restore result | Passed / Failed |
| Witnessed by | ____________ |

An untested restore is a belief, not a backup. The restore test is the only way to
discover that a snapshot excludes a table before the day it is needed.

Interaction with erasure: a 7-day snapshot window means a record deleted today still
exists in snapshots for up to seven days. This is why the data-erasure requirement is
written as "within seven days" rather than "immediately".

Current schema: 20 migration files under `database/migrations/`, from
`0001_01_01_000000_create_users_table.php` through
`2026_09_30_000004_protect_history_from_user_cascade_delete.php`.


--------------------------------------------------------------------------------
TABLE 5 — DATABASE MIGRATION CHECKLIST
Checklist form of the existing DATABASE MIGRATIONS prose block.
--------------------------------------------------------------------------------

- [ ] Migration file added under `database/migrations/` using a `YYYY_MM_DD_HHMMSS_` timestamp prefix
- [ ] Migration reviewed by a teammate other than the author
- [ ] Migration is backward compatible for one release
- [ ] Destructive change split in two: release N stops using the column, release N+1 drops it
- [ ] Migration executed against Session Pooler port 5432, never Transaction Pooler 6543
- [ ] Connection string copied from the Supabase Dashboard Connect modal, not typed by hand
- [ ] No direct manual editing of the live Supabase database used as a shortcut
- [ ] Schema verified in the Supabase dashboard afterwards — no manual drift
- [ ] Migration committed and not excluded by `.gitignore`

Rationale: every build that migrates an empty database is a free test of the migration
path. A hand-edited database has no such test and no audit trail.


--------------------------------------------------------------------------------
TABLE 6 — SUPABASE ACCESS AND OWNERSHIP
--------------------------------------------------------------------------------

| Role | Dashboard Access | SQL Access | Backup Restore | Can Run Migrations |
|---|---|---|---|---|
| Developer | Read/write on developer project | Yes, developer project only | Developer project only | Yes |
| Developer / QA | Read on Test project | Read-only | No | No |
| Project Manager | Read-only, through the application UI | No | No | No |
| System Analyst | Read-only, through the application UI | No | No | No |

Rationale: the application is the intended interface for verifying data. Direct SQL
access to the Test or Production project is not required for any task in the current
sprint scope, so it is not granted.


--------------------------------------------------------------------------------
TABLE 7 — FREE-TIER LIMITS WORTH KNOWING
Complements the existing "FREE-TIER LIMITS" bullet list with a mitigation column.
--------------------------------------------------------------------------------

| Limit | Value | What Happens at the Limit | Mitigation in Smart-Stock |
|---|---|---|---|
| Database storage | 1 GB | Transaction history and inserts begin failing | No file uploads stored in the database; dumps pruned |
| Connection cap | Provider-defined, low | Requests queue or time out | Supavisor pooler on 6543; no connection opened per request |
| Transaction Pooler | No prepared-statement session state | `php artisan migrate` fails partway through | Migrations use Session Pooler 5432 |
| Backup retention | 7 days | Data older than 7 days recoverable only from Layer 2 | JSON dump run per milestone |
| Monthly cost | 0 USD | Not applicable | All environments on free tiers |

Current data volume is well within the 1 GB ceiling for a hardware-store capstone
prototype. The limit is documented so it is a known constraint rather than a surprise
during a demonstration.


================================================================================
INSERT ORDER FOR PAGE 4
================================================================================

  DATABASE MIGRATIONS AND BACKUP (Supabase)      [existing heading - keep]
    DATABASE MIGRATIONS:                         [existing prose - keep]
    -> TABLE 5  Migration Checklist              [new]
    BACKUP AND RESTORE:                          [existing prose - keep]
    -> TABLE 4  Backup and Restore Coverage      [new, extends existing]
    -> TABLE 1  Supabase Configuration Update Log [new, first under heading]
    -> TABLE 2  Connection Paths                 [new, replaces prose sentence]
    -> TABLE 3  Data Residency by Environment    [new]
    -> TABLE 6  Access and Ownership             [new]
    -> TABLE 7  Free-Tier Limits                 [new]
    Figure 2 — Supabase Data and Backup Flow     [existing diagram]

================================================================================
