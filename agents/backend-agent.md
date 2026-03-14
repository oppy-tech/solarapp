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
- AC5.1, AC5.2, AC5.3 (multi-tenancy)
- AC7.1, AC7.2, AC7.3, AC7.4, AC7.5, AC7.6, AC7.7, AC7.8, AC7.9 (input validation & security)

### Tests to write (before implementation)

#### Happy path
1. **Dashboard loads with seeded data** — `GET /` returns 200, sees AHJ name
2. **Stats are correct without filters** — total, approved, pending counts match created data
3. **Date range filters projects** — create projects across dates, filter with `?start_date=&end_date=`, verify only matching projects returned
4. **Date range filters stats** — stats reflect only the filtered date range
5. **Average approval time is calculated** — create approved projects with known timestamps, assert correct avg seconds
6. **Average approval time is null when no approved projects** — filter to range with no approvals
7. **Pagination returns 20 per page** — create 25 projects, assert first page has 20, second has 5
8. **Pagination preserves date filters** — pagination links contain start_date/end_date params
9. **Multi-tenancy: only current AHJ's projects shown** — create projects for 2 AHJs, verify isolation

#### Negative / edge case tests (REQUIRED)
10. **Invalid date format does not crash** — `?start_date=not-a-date&end_date=2025-01-31` returns 200, filters are ignored (shows all data)
11. **End date before start date is handled** — `?start_date=2025-06-01&end_date=2025-01-01` returns 200, invalid range is ignored
12. **SQL injection in date params** — `?start_date=2025-01-01'; DROP TABLE projects;--&end_date=2025-01-31` returns 200, no error, no data loss
13. **XSS in date params** — `?start_date=<script>alert(1)</script>&end_date=2025-01-31` returns 200, script tag is NOT present in response body (escaped or ignored)
14. **Empty string dates** — `?start_date=&end_date=` returns 200, treated as no filter
15. **Only start_date provided** — `?start_date=2025-01-01` (no end_date) returns 200, either filters from start_date onwards or ignores incomplete range
16. **Only end_date provided** — `?end_date=2025-01-31` (no start_date) returns 200, either filters up to end_date or ignores incomplete range
17. **Extreme date range** — `?start_date=1900-01-01&end_date=2099-12-31` returns 200, does not timeout or error
18. **Negative page number** — `?page=-1` returns 200 (Laravel handles this gracefully) or redirects to page 1
19. **Page number beyond last page** — create 5 projects, request `?page=999`, returns 200 with empty results (not a 500)
20. **Non-numeric page** — `?page=abc` returns 200, does not crash
21. **Extra unexpected query params** — `?start_date=2025-01-01&end_date=2025-01-31&foo=bar&admin=true` returns 200, extra params are ignored
22. **Date at boundary** — project with `submitted_at` exactly at `start_date 00:00:00` and `end_date 23:59:59` are included
23. **Approval time with zero duration** — approved project where `submitted_at == approved_at` (0 seconds), avg should be 0 not null
24. **Multi-tenancy with date filters** — filter by date range, verify other AHJ's projects in that range are NOT included in stats or results

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
