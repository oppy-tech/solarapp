# Data Integrity Agent — Task C: Data Integrity Report

## Role
You are a data integrity specialist. Your job is to scan the project database for anomalies, inconsistencies, and integrity issues, then produce a structured report with findings and recommendations.

## Known Issues from Code Review (MUST FIX)
These were flagged in a prior code review and must be addressed:

1. **Use `->chunk()` instead of `->get()`** — `Project::where('ahj_id', $ahj->id)->get()` loads all projects into memory. Use `->chunk(100, function ($projects) { ... })` or `->cursor()` to process in batches for production readiness.

2. **Negative seconds in "unreasonably fast approval" check** — if `approved_at` is before `submitted_at`, `diffInSeconds()` may return a negative value or an absolute value depending on usage. This caused 68 misleading warnings in the report. Ensure you check for `approved_at < submitted_at` separately (that's an "impossible timestamp" issue, not a "fast approval" issue). Only flag as "unreasonably fast" when `approved_at > submitted_at` AND the difference is suspiciously small (e.g. < 60 seconds).

## Technology Context
- **Language**: PHP 8.4
- **Framework**: Laravel 11 (Eloquent ORM, Artisan commands, Carbon for dates)
- **Database**: MySQL via Laravel Sail (Docker)
- **Run commands via**: `./vendor/bin/sail artisan` (from `solar-interview/` directory)
- **App URL**: http://localhost:8383

## Domain Context
- **AHJ** = Authority Having Jurisdiction — a local government body that reviews solar permits
- There are **2 AHJs** in the database with separate project sets
- **Project statuses**: `draft`, `submitted`, `approved`, `revision_required`
- **Lifecycle**: A project is created (draft) → submitted (`submitted_at` set) → either approved (`approved_at` set, status=approved) or sent back (`revision_required`)
- **Project types**: `PV` (photovoltaic) or `PV+ST` (PV + storage)

## Schema Reference
```sql
projects:
  id, ahj_id (FK), title, status, project_type_id,
  submitted_at (nullable datetime), approved_at (nullable datetime),
  created_at, updated_at

ahjs:
  id, name, address_line_1, city, state, zip, contact_email,
  charges_fees (bool), is_live (bool), meta (json), created_at, updated_at
```

## Files you may READ for context
- `solar-interview/app/Models/Project.php`
- `solar-interview/app/Models/Ahj.php`
- `solar-interview/database/migrations/2026_01_01_000000_create_interview_tables.php`
- `solar-interview/database/seeders/InterviewSeeder.php`
- `solar-interview/stubs/sample_data.json` — the raw seed data

## Your Output File
- `solar-interview/DATA_INTEGRITY_REPORT.md`

## What to Scan For

### Status/Timestamp Consistency
- `approved` projects missing `approved_at`
- `approved` projects missing `submitted_at`
- `draft` projects that have `submitted_at` or `approved_at` set (shouldn't happen)
- `submitted` or `revision_required` projects that have `approved_at` set (shouldn't be approved yet)

### Impossible Timestamps
- `approved_at` earlier than `submitted_at` (can't approve before submission)
- `submitted_at` earlier than `created_at` (can't submit before record creation)
- Future dates (timestamps beyond the current date)
- Unreasonably fast approvals (e.g. approved_at within seconds of submitted_at)

### Missing Required Fields
- Projects without a `title`
- Projects without an `ahj_id` or with an `ahj_id` that doesn't exist in the `ahjs` table
- Projects with empty or null `status`
- Projects with unrecognised `status` values (not in: draft, submitted, approved, revision_required)
- Projects with unrecognised `project_type_id` values

### Cross-AHJ Analysis
- Report counts per AHJ separately
- Flag if any projects reference a non-existent AHJ

## Report Format
Structure the report as:

```markdown
# Data Integrity Report
Generated: [date]

## Summary
[High-level counts: total projects scanned, total issues found, breakdown by severity]

## Findings

### Critical
[Issues that would cause application errors or data corruption]

### Warning
[Issues that indicate bad data but won't crash the app]

### Info
[Minor anomalies or observations worth noting]

## Recommendations
[How each category of issue should be handled — fix in DB, add validation, etc.]
```

## Approach
- You may write a temporary Artisan command to query the database, or inspect `stubs/sample_data.json` directly, or both
- Prefer querying the live database for accuracy — the JSON is the seed source but the DB is the truth
- If you write a command, place it in `solar-interview/app/Console/Commands/` and name it `DataIntegrityCheck.php`

## Test-Driven Development

You MUST write tests FIRST, then implement. Follow red-green-refactor.

### Test file
- `solar-interview/tests/Feature/DataIntegrityCheckTest.php`

### Test setup
- Use `use Illuminate\Foundation\Testing\RefreshDatabase;` trait in your test class
- Extend `Tests\TestCase`
- Write your integrity logic as an Artisan command (`DataIntegrityCheck`) so it is testable
- Tests should seed specific bad data with manual creation (`Ahj::create`, `Project::create`), run the command via `$this->artisan('data:integrity-check')`, and assert the output/exit code
- No factories exist for interview models — create records inline
- A `testing` MySQL database exists and RefreshDatabase runs migrations automatically
- Run tests via: `./vendor/bin/sail artisan test --filter=DataIntegrityCheckTest`
- Verified working: see `tests/Feature/SmokeTest.php` for a passing example

### Acceptance Criteria to satisfy
Your tests should map to the acceptance criteria in `agents/product-owner-agent.md`. Specifically:
- AC4.1 (audit has been run)
- AC4.2 (inconsistent status/timestamps)
- AC4.3 (impossible timestamps)
- AC4.4 (missing required data)
- AC4.5 (specific project IDs in findings)
- AC4.6 (severity levels)
- AC4.7 (both AHJs covered)
- AC4.8 (recommendations included)

### Tests to write (before implementation)

#### Status/timestamp consistency
1. **Detects approved project missing approved_at** — seed project with status=approved, approved_at=null, assert it's flagged
2. **Detects approved project missing submitted_at** — seed project with status=approved, submitted_at=null, assert it's flagged
3. **Detects approved_at before submitted_at** — seed project with impossible timestamps, assert it's flagged
4. **Detects draft project with submitted_at set** — seed draft project with submitted_at, assert it's flagged
5. **Detects draft project with approved_at set** — seed draft project with approved_at, assert it's flagged
6. **Detects submitted project with approved_at set** — seed submitted project with approved_at, assert it's flagged
7. **Detects revision_required project with approved_at set** — seed revision_required project with approved_at, assert it's flagged

#### Impossible timestamps
8. **Detects submitted_at before created_at** — seed project where submitted_at is earlier than created_at
9. **Detects future submitted_at** — seed project with submitted_at in the future (beyond current date)
10. **Detects future approved_at** — seed project with approved_at in the future
11. **Detects unreasonably fast approval** — seed project approved within 1 second of submission, flag as suspicious

#### Missing/invalid fields
12. **Detects project with unknown status** — seed project with status='invalid_status', assert it's flagged
13. **Detects project with missing title** — seed project with empty string title, assert it's flagged
14. **Detects project with null title** — seed project with null title, assert it's flagged
15. **Detects project with empty status** — seed project with status='', assert it's flagged
16. **Detects project with null status** — seed project with null status, assert it's flagged
17. **Detects project with unrecognised project_type_id** — seed project with project_type_id='INVALID', assert it's flagged
18. **Detects orphaned ahj_id** — seed project referencing non-existent AHJ (use raw DB insert to bypass FK constraint if needed), assert it's flagged

#### Report structure and integrity
19. **Clean data produces no issues** — seed only valid projects with correct status/timestamp combinations, assert zero issues found
20. **Reports per-AHJ breakdown** — seed issues across 2 AHJs, assert both are reported separately with correct counts
21. **Command generates report file** — run command, assert `DATA_INTEGRITY_REPORT.md` exists and contains expected sections (Summary, Findings, Recommendations)
22. **Report includes specific project IDs** — seed a bad project, run command, assert the project's ID appears in the report output
23. **Report separates findings by severity** — seed critical and warning-level issues, assert both severity levels appear in report
24. **Command exits with non-zero code when issues found** — seed bad data, assert command exit code indicates issues were detected
25. **Command exits with zero code when no issues** — seed clean data, assert command exit code is 0

### Running tests
```bash
./vendor/bin/sail artisan test --filter=DataIntegrityCheckTest
```

## Infrastructure Context & Security Guardrails

The production environment runs on AWS ECS Fargate with Postgres RDS. Keep this in mind for your audit:

### Security awareness
- **Hardcoded credentials in infra** — the CloudFormation template has a plaintext DB password (`SolarApp2024!`). If you find any hardcoded credentials in application code, flag them as Critical.
- **Overly permissive IAM** — the app has `s3:*`, `sqs:*`, `ses:*`, `logs:*` on all resources. Note any code that assumes or exploits these broad permissions.
- **Internet-facing with no WAF** — port 80 is open to `0.0.0.0/0`. Any input validation gaps you find are higher severity because of this.

### Scale awareness
- **RDS is a single `db.t4g.small` with 20GB storage** — flag any data patterns that could cause storage bloat or query performance issues (e.g. unbounded text fields, missing indexes on queried columns).
- **No auto-scaling on ECS** — only 2 fixed instances. Flag any data issues that could cause request timeouts under load (e.g. scanning full tables without pagination).
- **Your audit command must be efficient** — use chunked queries (`->chunk()` or `->cursor()`) if scanning large tables. Don't load the entire projects table into memory with `->get()`.

### What to flag in the report
If your data scan reveals issues that are symptomatic of missing application-level validation (e.g. projects with impossible status transitions), recommend specific validation rules in your report. This feeds back into the backend agent's work.

## Guardrails
- Do NOT modify any existing application files (models, controllers, views, migrations)
- Do NOT modify the database data — this is a read-only audit
- Do NOT delete or alter the seed data
- Report on BOTH AHJs separately where relevant
- Be specific: include project IDs and field values in findings so issues are actionable
