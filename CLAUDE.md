# SolarAPP+ Technical Interview

## Project Overview
Technical interview project for SolarAPP+ Senior Product Engineer role. We are executing **Option 3: Multi-Agent Engineering** — a multi-agent workflow that completes three parallel tasks (backend, frontend, data integrity), orchestrated via GitHub Actions with automated testing, PR creation, and code review.

## Domain Context
- **AHJ** = Authority Having Jurisdiction — local government bodies that review and approve solar installation permits
- There are **2 AHJs** in the database — all queries must be scoped to the current AHJ (multi-tenancy)
- **Project lifecycle**: draft → submitted (`submitted_at` set) → approved (`approved_at` set) / revision_required
- **Project types**: `PV` (photovoltaic), `PV+ST` (PV + storage)

## Task Summary (Option 3)

### Task A: Dashboard Analytics (Backend)
- Date range filtering via `start_date`/`end_date` query params on `submitted_at`
- Average approval time calculation using DB aggregates (not PHP loops)
- Server-side pagination (20 per page, replaces `limit(10)`)
- Input validation: invalid dates, SQL injection, XSS — all handled gracefully

### Task B: Dashboard UI (Frontend)
- Date range picker (native HTML date inputs, Tailwind-styled)
- Human-readable average approval time display (e.g. "3 days, 6 hours")
- Pending projects stat card
- Pagination nav links with filter persistence in URL
- Null-safe rendering (`submitted_at?->format()`)
- XSS escaping on all user-supplied data

### Task C: Data Integrity Report
- Artisan command `data:integrity-check` scans for data anomalies
- Chunked queries (`->chunk()`) for production-scale scanning
- Detects: status/timestamp inconsistencies, impossible timestamps, missing fields, orphaned references
- Produces `DATA_INTEGRITY_REPORT.md` with findings by severity (critical/warning/info)

## Environment

- **Framework**: Laravel 11 (PHP 8.4.1, Composer 2.8.3)
- **Runtime**: Laravel Sail (Docker) — MySQL + PHP container
- **App URL**: `http://localhost:8383` (APP_PORT=8383)
- **Database**: MySQL via Sail (seeded with 160 projects across 2 AHJs)
- **Project dir**: `solar-interview/` (created by `setup.sh`)
- **Start Sail**: `APP_PORT=8383 ./vendor/bin/sail up -d` (from `solar-interview/`)
- **Run tests locally**: `./vendor/bin/sail artisan test`

## Key Files

### Application
- `solar-interview/app/Http/Controllers/Ahj/DashboardController.php` — Task A
- `solar-interview/resources/views/pages/ahj/dashboard.blade.php` — Task B
- `solar-interview/app/Console/Commands/DataIntegrityCheck.php` — Task C
- `solar-interview/app/Models/Project.php` — Project model (`submitted_at`, `approved_at`, `status`)
- `solar-interview/app/Models/Ahj.php` — AHJ model (`hasMany(Project::class)`)

### Tests
- `solar-interview/tests/Feature/DashboardControllerTest.php` — backend (happy + negative)
- `solar-interview/tests/Feature/DashboardViewTest.php` — frontend (happy + negative)
- `solar-interview/tests/Feature/DataIntegrityCheckTest.php` — data integrity (happy + negative)
- `solar-interview/tests/Feature/SmokeTest.php` — baseline smoke test

### Agent Prompts
- `agents/product-owner-agent.md` — acceptance criteria (AC1–AC7, 34 total ACs)
- `agents/backend-agent.md` — backend specialist with TDD, security, performance guardrails
- `agents/frontend-agent.md` — frontend specialist with TDD, XSS, escaping guardrails
- `agents/data-integrity-agent.md` — audit specialist with TDD, chunking, severity guardrails

### Infrastructure
- `stubs/solarapp.yaml` — AWS CloudFormation template (ECS/RDS) — read for security context
- `.github/workflows/multi-agent-orchestrator.yml` — 5-phase CI/CD pipeline

## Agent Architecture

### Four Specialist Agents

| Agent | Role | Owns (write) | Reads (context) | Tests |
|-------|------|-------------|-----------------|-------|
| **Product Owner** | Defines ACs, validates output | `VALIDATION_REPORT.md` | all files | N/A |
| **Backend** | Controller logic | `DashboardController.php` | models, migration, views | `DashboardControllerTest.php` |
| **Frontend** | Blade template | `dashboard.blade.php` | layout, controller | `DashboardViewTest.php` |
| **Data Integrity** | Audit command + report | `DataIntegrityCheck.php`, `DATA_INTEGRITY_REPORT.md` | models, seeder, sample data | `DataIntegrityCheckTest.php` |

### Data Contract (Backend → Frontend)
```php
return view('pages.ahj.dashboard', [
    'ahj' => Ahj,                        // AHJ model
    'stats' => [
        'total_projects' => int,
        'approved_projects' => int,
        'pending_projects' => int,
        'avg_approval_time' => int|null,  // seconds, or null
    ],
    'projects' => LengthAwarePaginator,   // 20 per page
    'filters' => [
        'start_date' => string|null,      // Y-m-d
        'end_date' => string|null,
    ],
]);
```

### Guardrails

1. **File ownership** — each agent edits only its designated files, enforced by prompt and code review
2. **Data contract** — typed interface between backend and frontend, documented in both prompts
3. **TDD with green gate** — agents must achieve 0 test failures before their work is accepted. A `Verify all tests pass` CI step blocks the push if any test fails.
4. **AC mapping** — every test maps to a specific acceptance criterion (AC1–AC7)
5. **Infrastructure-informed security** — guardrails derived from real issues in `solarapp.yaml` (hardcoded passwords, wildcard IAM, no WAF)
6. **Query efficiency** — DB aggregates required (`->count()`, `->avg()`), `->get()` forbidden on unbounded queries, `->chunk()` for batch processing
7. **Multi-tenancy** — all queries scoped through `$ahj->projects()`, tested with 2 AHJs
8. **Negative testing** — SQL injection, XSS, invalid dates, null fields, boundary conditions, extreme pagination

### Known Issues Feed-Forward
Agent prompts include a "Known Issues from Code Review" section listing specific bugs from prior runs that MUST be fixed. This creates a feedback loop: code review → agent prompt update → next run fixes the issues.

## Orchestration Pipeline

GitHub Actions workflow (`.github/workflows/multi-agent-orchestrator.yml`):

```
Phase 1: Parallel Agent Execution
├── agent/backend    → DashboardController + tests
├── agent/frontend   → dashboard.blade.php + tests
└── agent/data-integrity → DataIntegrityCheck + tests + report
         │ (each agent has a "Verify all tests pass" gate)
         ▼
Phase 2: Merge Agent Branches
         → feature/multi-agent-delivery (handles output.txt conflicts)
         ▼
Phase 3: Test Suite
         → Full test suite on merged branch (MySQL service container)
         ▼
Phase 4: Create Pull Request
         → gh pr create to main (checks for existing PR first)
         ▼
Phase 5: Code Review Agent
         → Reviews against AC1–AC7, posts findings on the PR
```

**Trigger**: `workflow_dispatch` (manual) with `skip_agents` option to reuse existing branches.

**Key CI patterns for `claude-code-action`**:
- Use `mode: agent` (not `tag`) for `workflow_dispatch` events
- Use `direct_prompt` (not `prompt`) for the instruction text
- Requires `id-token: write` permission for OIDC auth
- Workflow file must match the default branch exactly
- Agents produce `output.txt` — handle merge conflicts and gitignore it

## Setup Notes
- `setup.sh` fails because `laravel/sanctum` isn't bundled with Laravel 11 — install manually
- CI uses `.env.example` + `composer install` instead of `setup.sh` (which skips when `solar-interview/` exists)
- Test database: `testing` DB must exist in MySQL. CI creates it via service container config. Locally: `./vendor/bin/sail mysql -e "CREATE DATABASE IF NOT EXISTS testing;"`
- `tests/Unit/` directory must exist even if empty (PHPUnit fails otherwise)

## Deliverables
1. Working code for Tasks A, B, C with comprehensive test coverage
2. Agent prompts with guardrails, TDD requirements, AC mappings, and known-issue feed-forward
3. GitHub Actions workflow for automated orchestration, testing, PR creation, and code review
4. `DATA_INTEGRITY_REPORT.md` — automated audit output
5. `PROCESS.md` — full documentation of decisions, AI usage, surprises, and lessons learned
