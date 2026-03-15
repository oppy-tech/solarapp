# SolarAPP+ Technical Interview — Option 3: Multi-Agent Engineering

## Quick Links

| Deliverable | Location |
|-------------|----------|
| **Working Code (PR)** | [PR #2](https://github.com/oppy-tech/solarapp/pull/2) — all code changes, tests, and code review |
| **PROCESS.md** | [`PROCESS.md`](PROCESS.md) — AI usage log, decisions, surprises, lessons learned |
| **Data Integrity Report** | [`solar-interview/DATA_INTEGRITY_REPORT.md`](solar-interview/DATA_INTEGRITY_REPORT.md) (on PR branch) |
| **CI Pipeline** | [Actions](https://github.com/oppy-tech/solarapp/actions/workflows/multi-agent-orchestrator.yml) — full pipeline runs |

## Repository Structure

```
.
├── README.md                          ← You are here
├── CLAUDE.md                          ← Project context for AI agents
├── PROCESS.md                         ← Interview deliverable (decisions, process, learnings)
│
├── agents/                            ← Agent prompt specifications
│   ├── product-owner-agent.md         ← 34 acceptance criteria (AC1–AC7)
│   ├── backend-agent.md               ← Backend specialist (Task A)
│   ├── frontend-agent.md              ← Frontend specialist (Task B)
│   └── data-integrity-agent.md        ← Data integrity specialist (Task C)
│
├── .github/workflows/
│   └── multi-agent-orchestrator.yml   ← 5-phase CI/CD pipeline
│
├── solar-interview/                   ← Laravel application
│   ├── app/
│   │   ├── Http/Controllers/Ahj/
│   │   │   └── DashboardController.php    ← Task A (on PR branch)
│   │   ├── Console/Commands/
│   │   │   └── DataIntegrityCheck.php     ← Task C (on PR branch)
│   │   └── Models/
│   │       ├── Project.php
│   │       └── Ahj.php
│   ├── resources/views/pages/ahj/
│   │   └── dashboard.blade.php            ← Task B (on PR branch)
│   ├── tests/Feature/
│   │   ├── DashboardControllerTest.php    ← 24 backend tests (on PR branch)
│   │   ├── DashboardViewTest.php          ← 18 frontend tests (on PR branch)
│   │   └── DataIntegrityCheckTest.php     ← 25 data integrity tests (on PR branch)
│   └── DATA_INTEGRITY_REPORT.md           ← Generated audit report (on PR branch)
│
├── docs/                              ← Original interview instructions
│   ├── README.md
│   ├── technical-interview-option-3-ai-engineering.md
│   ├── task-3-next-steps.md
│   └── ...
│
└── stubs/                             ← Original starter files
    ├── DashboardController.php
    ├── dashboard.blade.php
    ├── sample_data.json
    ├── solarapp.yaml                  ← AWS CloudFormation (read for infra context)
    └── ...
```

## How to Review

### 1. Start with the PR
[PR #2](https://github.com/oppy-tech/solarapp/pull/2) contains all code changes with a structured description. The code review agent's findings are posted as a comment on the PR.

### 2. Read PROCESS.md
[`PROCESS.md`](PROCESS.md) answers all required interview questions:
- **AI Usage Log** — every artifact tagged with how it was produced
- **Decisions Rejected** — 7 approaches we considered and rejected (with reasoning)
- **What Surprised Us** — 6 unexpected findings (cross-agent contracts, CI debugging, PHP type coercion)
- **Code We Don't Fully Understand** — 3 areas flagged with honest assessment
- **What We'd Do Next** — completed checklist + future improvements
- **Multi-Agent Workflow** — decomposition, 9 guardrails, success/failure analysis, 6 lessons learned

### 3. Review the Agent Prompts
The [`agents/`](agents/) directory contains the specialist prompts. Each includes:
- Role and technology context
- Known issues from code review (feed-forward loop)
- File ownership boundaries
- Data contract between agents
- TDD requirements with green gate enforcement
- Acceptance criteria mapping
- Negative test specifications
- Infrastructure-informed security guardrails

### 4. Review the Pipeline
The [GitHub Actions workflow](.github/workflows/multi-agent-orchestrator.yml) orchestrates the full pipeline:

```
Backend Agent ──→ Frontend Agent ──┐
(Task A)          (Task B)         ├──→ Merge → Test Suite → PR → Code Review
Data Integrity Agent ──────────────┘
(Task C, parallel)
```

Each agent has a "Verify all tests pass" gate that blocks the push if any test fails.

### 5. Run Locally (optional)
```bash
# Prerequisites: Docker, PHP 8.1+, Composer

# Setup
cd solar-interview
cp .env.example .env
composer install
composer require laravel/sanctum

# Start (via Sail)
APP_PORT=8383 ./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:fresh
./vendor/bin/sail artisan db:seed --class=InterviewSeeder

# View dashboard
open http://localhost:8383

# Run tests
./vendor/bin/sail artisan test

# Run data integrity check
./vendor/bin/sail artisan data:integrity-check
```

## Key Numbers

- **69 tests**, 182 assertions, all passing
- **34 acceptance criteria** across 7 categories
- **160 projects** scanned, **162 data integrity issues** found
- **4 specialist agents** (Product Owner, Backend, Frontend, Data Integrity)
- **9 guardrails** (file ownership, data contract, TDD, AC mapping, security, query efficiency, multi-tenancy, negative testing, known issues feed-forward)
- **5-phase pipeline** (agents → merge → test → PR → code review)
