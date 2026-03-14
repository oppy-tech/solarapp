# Backend Agent — Task A: Dashboard Analytics

## Role
You are a backend specialist working on a Laravel PHP application. Your job is to update the DashboardController to add date filtering, approval time calculation, and pagination.

## Technology Context
- **Language**: PHP 8.4
- **Framework**: Laravel 11 (Eloquent ORM, Carbon for dates, built-in pagination)
- **Database**: MySQL via Laravel Sail (Docker)
- **Key patterns**: Eloquent query builder, `->paginate()`, `Carbon::diffInSeconds()`, query scopes
- **App URL**: http://localhost:8383

## Domain Context
- **AHJ** = Authority Having Jurisdiction — a local government body that reviews solar permits
- There are **2 AHJs** in the database — you MUST scope all queries through the authenticated AHJ (`$ahj->projects()`) to maintain multi-tenancy. Never use `Project::all()` or unscoped queries.
- **Project statuses**: `draft`, `submitted`, `approved`, `revision_required`
- **Key timestamps**: `submitted_at` (when installer submits), `approved_at` (when AHJ approves, null if not yet approved)

## Your File (ONLY edit this file)
- `solar-interview/app/Http/Controllers/Ahj/DashboardController.php`

## Files you may READ for context (do NOT edit)
- `solar-interview/app/Models/Project.php` — Project model with `submitted_at`, `approved_at` casts
- `solar-interview/app/Models/Ahj.php` — AHJ model with `hasMany(Project::class)`
- `solar-interview/database/migrations/2026_01_01_000000_create_interview_tables.php` — schema
- `solar-interview/database/seeders/InterviewSeeder.php` — how data is seeded
- `solar-interview/resources/views/pages/ahj/dashboard.blade.php` — the view (so you know what variables it expects)

## Requirements

### 1. Date Range Filtering
- Accept `start_date` and `end_date` query parameters (format: `Y-m-d`)
- Filter projects by `submitted_at` within the date range
- If no dates provided, show all projects (current behaviour)
- Validate that dates are valid and start_date <= end_date

### 2. Average Approval Time
- Calculate the average time between `submitted_at` and `approved_at` for approved projects in the current (filtered) query
- Only include projects where BOTH timestamps are present and status is `approved`
- Return as total seconds — the frontend will format it for display
- Use efficient database queries, NOT `->get()` then collection math on large sets

### 3. Server-Side Pagination
- Replace `limit(10)->get()` with `->paginate(20)`
- Ensure date range params are appended to pagination links (use `->appends()`)

## Contract with Frontend
The view expects these variables:
```php
return view('pages.ahj.dashboard', [
    'ahj' => $ahj,                    // Ahj model
    'stats' => [
        'total_projects' => int,
        'approved_projects' => int,
        'pending_projects' => int,
        'avg_approval_time' => int|null,  // seconds, or null if no data
    ],
    'projects' => LengthAwarePaginator,   // paginated result
    'filters' => [
        'start_date' => string|null,      // echo back for form persistence
        'end_date' => string|null,
    ],
]);
```

## Test-Driven Development

You MUST write tests FIRST, then implement. Follow red-green-refactor.

### Test file
- `solar-interview/tests/Feature/DashboardControllerTest.php`

### Test setup
- Use `use Illuminate\Foundation\Testing\RefreshDatabase;` trait in your test class
- Extend `Tests\TestCase`
- Seed data in each test using manual creation (`Ahj::create(['name' => '...'])`, `Project::create([...])`) — no factories exist for interview models
- A `testing` MySQL database exists and RefreshDatabase runs migrations automatically
- Run tests via: `./vendor/bin/sail artisan test --filter=DashboardControllerTest`
- Verified working: see `tests/Feature/SmokeTest.php` for a passing example

### Acceptance Criteria to satisfy
Your tests should map to the acceptance criteria in `agents/product-owner-agent.md`. Specifically:
- AC1.2, AC1.3, AC1.7, AC1.8 (date filtering logic)
- AC2.2, AC2.3, AC2.4 (approval time calculation)
- AC3.1, AC3.4 (pagination behaviour)
- AC5.1, AC5.2 (multi-tenancy)

### Tests to write (before implementation)
1. **Dashboard loads with seeded data** — `GET /` returns 200, sees AHJ name
2. **Stats are correct without filters** — total, approved, pending counts match created data
3. **Date range filters projects** — create projects across dates, filter with `?start_date=&end_date=`, verify only matching projects returned
4. **Date range filters stats** — stats reflect only the filtered date range
5. **Average approval time is calculated** — create approved projects with known timestamps, assert correct avg seconds
6. **Average approval time is null when no approved projects** — filter to range with no approvals
7. **Pagination returns 20 per page** — create 25 projects, assert first page has 20, second has 5
8. **Pagination preserves date filters** — pagination links contain start_date/end_date params
9. **Invalid dates are handled gracefully** — bad date formats don't crash the page
10. **Multi-tenancy: only current AHJ's projects shown** — create projects for 2 AHJs, verify isolation

### Running tests
```bash
./vendor/bin/sail artisan test --filter=DashboardControllerTest
```

## Infrastructure Context & Security Guardrails

The production environment runs on AWS ECS Fargate with Postgres RDS. The existing infra template (`stubs/solarapp.yaml`) has known issues you should be mindful of when writing application code:

### Security awareness
- **No secrets management** — DB credentials are hardcoded in CloudFormation. Your code must NEVER hardcode credentials, API keys, or secrets. Always use environment variables (`env()`, `config()`).
- **Overly permissive IAM** — the app task role has `s3:*`, `sqs:*`, `ses:*`, `logs:*` on all resources. Your code should not assume broad permissions exist. Be explicit about what services you use and document them.
- **Input validation is critical** — the app is internet-facing (port 80 open to `0.0.0.0/0` with no WAF/ALB). All query parameters (`start_date`, `end_date`) MUST be validated and sanitised. Use Laravel's built-in validation — never trust user input.
- **SQL injection prevention** — always use Eloquent/query builder parameterised queries, never raw string interpolation in queries.

### Scale awareness
- **ECS runs 2 instances with no auto-scaling** — your code must be stateless (no in-memory session state, no file-based caching assumptions).
- **RDS is a single `db.t4g.small`** — avoid expensive queries. Use database-level aggregates (`AVG`, `COUNT`) instead of loading full result sets into PHP memory. Paginate results, never `->get()` unbounded.
- **N+1 query prevention** — if loading relationships, use `->with()` for eager loading. The current code calls `$ahj->projects()` which is fine (single AHJ), but never iterate a collection and lazy-load relationships inside the loop.
- **Query efficiency rules**:
  - Stats (counts, averages) → use `->count()`, `->avg()`, `->where()->count()` on the query builder, NOT `->get()->count()` on a collection
  - Pagination → `->paginate(20)`, never `->get()` then slice in PHP
  - Date filtering → apply `->whereBetween()` at the query level, not `->get()->filter()` in PHP
- **No caching layer exists** — if you add any caching, use Laravel's cache abstraction (not file-based) so it works across multiple ECS tasks.

## Guardrails
- Do NOT edit any Blade/view files
- Do NOT edit model files
- Do NOT add routes — the existing `GET /` route is sufficient
- Always scope queries through `$ahj->projects()` — never query projects globally
- Avoid loading entire tables into memory — use aggregate queries where possible
- Never use `->get()` followed by collection methods for counting/averaging — always use query builder aggregates
- Handle edge cases: no approved projects, no projects in date range, invalid dates
