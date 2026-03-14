# SolarAPP+ Technical Interview

## Project Overview
Technical interview project for SolarAPP+ Senior Product Engineer role. We are executing **Option 3: Multi-Agent Engineering** — a multi-agent workflow that completes three parallel tasks (backend, frontend, data integrity).

## Task Summary (Option 3)

### Task A: Dashboard Analytics (Backend)
- Update `DashboardController.php` to accept `start_date`/`end_date` query params and filter stats
- Calculate average approval time (`submitted_at` → `approved_at`) for approved projects
- Replace `limit(10)` with server-side pagination (20 per page)

### Task B: Dashboard UI (Frontend)
- Add date range picker submitting start/end dates to `dashboard.blade.php`
- Display average approval time in human-readable format (e.g. "3 days, 6 hours")
- Add pagination nav links
- Persist date range in URL for shareability/bookmarkability

### Task C: Data Integrity Report
- Scan project data for integrity issues (inconsistent statuses, impossible timestamps, missing fields)
- Output `DATA_INTEGRITY_REPORT.md`

## Environment

- **Framework**: Laravel (PHP 8.4.1, Composer 2.8.3)
- **Runtime**: Laravel Sail (Docker)
- **App URL**: `http://localhost:8383` (APP_PORT=8383)
- **Database**: MySQL via Sail (seeded with 160 projects across 2 AHJs)
- **Project dir**: `solar-interview/` (created by `setup.sh`)
- **Start Sail**: `APP_PORT=8383 ./vendor/bin/sail up -d` (from `solar-interview/`)

## Key Files

- `app/Http/Controllers/Ahj/DashboardController.php` — Task A target
- `resources/views/pages/ahj/dashboard.blade.php` — Task B target
- `app/Models/Project.php` — Project model
- `app/Models/Ahj.php` — AHJ model
- `stubs/sample_data.json` — seed data source
- `database/seeders/InterviewSeeder.php` — seeder

## Setup Notes
- `setup.sh` failed initially because `laravel/sanctum` wasn't included — installed manually
- Sail installed with `composer require laravel/sail --dev` then `php artisan sail:install`
- Docs (original README, task descriptions) moved to `docs/`

## Agent Architecture
Four specialist agents in `agents/`:
- **Product Owner** (`product-owner-agent.md`) — defines acceptance criteria, validates output. Does not write code.
- **Backend** (`backend-agent.md`) — owns `DashboardController.php`, TDD against ACs
- **Frontend** (`frontend-agent.md`) — owns `dashboard.blade.php`, TDD against ACs
- **Data Integrity** (`data-integrity-agent.md`) — read-only audit, produces `DATA_INTEGRITY_REPORT.md`, TDD against ACs

Each specialist agent references the PO's acceptance criteria and maps its tests to specific ACs.

## Orchestration
GitHub Actions workflow (`.github/workflows/multi-agent-orchestrator.yml`) with 5 phases:
1. **Phase 1** — Three agent jobs run in parallel, each on its own branch (`agent/backend`, `agent/frontend`, `agent/data-integrity`)
2. **Phase 2** — Merge all agent branches into `feature/multi-agent-delivery`
3. **Phase 3** — Run full test suite on merged branch (MySQL service container)
4. **Phase 4** — Create PR to `main` with structured description
5. **Phase 5** — Code review agent validates against ACs and checks security/performance/multi-tenancy

Trigger: `workflow_dispatch` (manual) with option to skip agent execution and reuse existing branches.

## Deliverables
1. Working code for Tasks A, B, C
2. Workflow artifacts (orchestration scripts, prompts, configs)
3. `DATA_INTEGRITY_REPORT.md`
4. `PROCESS.md` (AI usage log, rejected approaches, surprises, unknowns, next steps)

## Multi-Tenancy Warning
There are 2 AHJs in the database — agents must scope queries correctly and not mix data across AHJs.
