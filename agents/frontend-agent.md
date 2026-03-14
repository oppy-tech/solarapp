# Frontend Agent — Task B: Dashboard UI

## Role
You are a frontend specialist working on a Laravel Blade template. Your job is to update the dashboard view to add a date range picker, display average approval time, and add pagination navigation.

## Technology Context
- **Templating**: Laravel Blade (`@extends`, `@section`, `@foreach`, `{{ }}` for output, `{!! !!}` for unescaped HTML)
- **CSS**: Tailwind CSS (loaded via CDN `<script src="https://cdn.tailwindcss.com"></script>`)
- **Layout**: Extends `layouts.app` which provides nav bar and `@yield('content')`
- **JavaScript**: Vanilla JS only — no build step, no npm, no Vite. Keep it simple with inline `<script>` if needed.
- **No component libraries** — use native HTML inputs styled with Tailwind

## Domain Context
- **AHJ** = Authority Having Jurisdiction — a local government body that reviews solar permits
- The dashboard shows one AHJ's project data (multi-tenancy is handled server-side)
- **Project statuses**: `draft`, `submitted`, `approved`, `revision_required`

## Your File (ONLY edit this file)
- `solar-interview/resources/views/pages/ahj/dashboard.blade.php`

## Files you may READ for context (do NOT edit)
- `solar-interview/resources/views/layouts/app.blade.php` — the layout template
- `solar-interview/app/Http/Controllers/Ahj/DashboardController.php` — to understand what variables are passed to the view

## Data Contract from Backend
The controller passes these variables:
```php
$ahj              // Ahj model (has ->name)
$stats = [
    'total_projects' => int,
    'approved_projects' => int,
    'pending_projects' => int,
    'avg_approval_time' => int|null,  // seconds, or null if no data
]
$projects          // LengthAwarePaginator (use ->links() for pagination, iterable for rows)
$filters = [
    'start_date' => string|null,      // Y-m-d format
    'end_date' => string|null,
]
```

## Requirements

### 1. Date Range Picker
- Add a form above the stats cards with `start_date` and `end_date` inputs (type="date")
- Form submits via GET to the same page (preserves URL bookmarkability)
- Pre-fill inputs from `$filters['start_date']` and `$filters['end_date']`
- Include a "Filter" submit button and a "Clear" link back to `/`
- Style consistently with existing Tailwind classes

### 2. Average Approval Time Display
- The stat card for "Avg. Approval Time" already exists — update it to show human-readable format
- Convert `$stats['avg_approval_time']` (seconds) to a readable string like "3 days, 6 hours" or "2 hours, 15 minutes"
- Handle the null case — display "N/A" when no data
- Do this formatting in Blade (inline PHP is fine) — no need for a separate helper

### 3. Pagination Navigation
- Replace the current static table with paginated output
- Add `{{ $projects->links() }}` below the table for Laravel's built-in pagination
- Pagination links must preserve the date filter query params (backend handles this via `->appends()`)

### 4. URL Persistence
- The GET form naturally puts params in the URL — verify the date range survives page reloads and pagination clicks
- No JavaScript required for this — it's handled by the form method and backend `->appends()`

## Test-Driven Development

You MUST write tests FIRST, then implement. Follow red-green-refactor.

### Test file
- `solar-interview/tests/Feature/DashboardViewTest.php`

### Test setup
- Use `use Illuminate\Foundation\Testing\RefreshDatabase;` trait in your test class
- Extend `Tests\TestCase`
- These are HTTP feature tests — seed an AHJ + projects with manual creation (`Ahj::create`, `Project::create`), then `GET /` and assert against the response content
- No factories exist for interview models — create records inline
- The backend agent will implement the controller contract in parallel — your tests should seed data and hit the endpoint, asserting on what the HTML contains
- A `testing` MySQL database exists and RefreshDatabase runs migrations automatically
- Run tests via: `./vendor/bin/sail artisan test --filter=DashboardViewTest`
- Verified working: see `tests/Feature/SmokeTest.php` for a passing example

### Acceptance Criteria to satisfy
Your tests should map to the acceptance criteria in `agents/product-owner-agent.md`. Specifically:
- AC1.1, AC1.4, AC1.5, AC1.6 (date range UI, URL persistence, clear)
- AC2.1, AC2.4 (human-readable display, N/A handling)
- AC3.2, AC3.3, AC3.6 (pagination nav visibility and behaviour)
- AC6.2, AC6.3 (empty states, no errors)
- AC7.3, AC7.10 (XSS escaping in params and table output)

### Tests to write (before implementation)

#### Happy path
1. **Date range form is present** — response contains date inputs with names `start_date` and `end_date`
2. **Date inputs are pre-filled from query params** — `GET /?start_date=2025-01-01&end_date=2025-06-01` has those values in the inputs
3. **Filter and Clear buttons exist** — response contains a submit button and a clear/reset link
4. **Avg approval time displays human-readable format** — seed an approved project with `submitted_at` 3 days 6 hours before `approved_at`, assert response contains "3 days" and "6 hours" (the backend returns seconds, the view formats them)
5. **Avg approval time shows N/A when null** — seed only draft projects (no approvals), assert "N/A" displayed
6. **Pagination links are rendered** — create 25 projects, assert pagination nav is present on page
7. **Project table displays correct columns** — assert table headers and row data are present
8. **Empty state is handled** — no projects seeded, page still loads without errors

#### Negative / edge case tests (REQUIRED)
9. **XSS in date params is escaped** — `GET /?start_date=<script>alert(1)</script>` returns 200 and response does NOT contain `<script>alert(1)</script>` (must be escaped in the input value attribute)
10. **Null submitted_at does not crash the table** — seed a draft project with `submitted_at => null`, page loads without 500 error. Use null-safe operator (`?->`) or conditional in the Blade template.
11. **Avg approval time with hours only (no days)** — seed approved project with 2 hours 15 min approval time, assert "2 hours, 15 minutes" (not "0 days, 2 hours")
12. **Avg approval time with minutes only** — seed approved project with 45 min approval time, assert "45 minutes" (not "0 hours, 45 minutes")
13. **Avg approval time with very large value** — seed approved project with 30 days approval time, assert "30 days" is displayed without overflow or layout breakage
14. **Avg approval time near zero** — seed approved project where `submitted_at == approved_at` (0 seconds), assert something reasonable like "Less than 1 minute" (not empty string or "0")
15. **Pagination not shown with exactly 20 projects** — create exactly 20 projects, pagination nav should NOT appear (no "Next" link)
16. **Clear link resets to clean URL** — clear link href is exactly `/` with no query params
17. **Table renders with special characters in title** — seed project with title `<b>O'Malley & Sons "Solar"</b>`, assert it appears escaped in the HTML (no raw HTML injection)
18. **Date form uses GET method** — verify the form method is GET (not POST) so the URL is bookmarkable

### Running tests
```bash
./vendor/bin/sail artisan test --filter=DashboardViewTest
```

## Infrastructure Context & Security Guardrails

The production environment runs on AWS ECS Fargate. The app is internet-facing (port 80 open to `0.0.0.0/0` with no WAF). Keep this in mind for frontend code:

### Security awareness
- **XSS prevention** — always use `{{ }}` (escaped output) in Blade, NEVER `{!! !!}` unless rendering trusted Laravel-generated HTML (e.g. `$projects->links()`). User-supplied data (dates, query params) must always be escaped.
- **CSRF** — any forms must include `@csrf`. GET forms for filtering are exempt (no state mutation), but POST/PUT/DELETE forms require it.
- **No inline event handlers with user data** — if using JavaScript, never interpolate user input into JS strings. Use `data-` attributes and read from the DOM.
- **Input validation** — use `type="date"` for date inputs to leverage browser-level validation. Don't rely solely on client-side validation — the backend validates too.

### Scale awareness
- **No CDN in place** — the Tailwind CDN script is fine for this interview, but note this is a dev convenience, not production-ready.
- **Multiple ECS instances serve requests** — do not assume sticky sessions or local state. The view must be fully stateless (URL params for state, no localStorage dependencies for critical functionality).
- **Keep the DOM lean** — paginated to 20 rows, don't add heavy client-side JavaScript that could impact load times on the small ECS instances (1 vCPU, 2GB RAM).

## Guardrails
- Do NOT edit any PHP controller or model files
- Do NOT add new routes
- Do NOT add npm packages or a build step — Tailwind CDN and vanilla JS only
- Keep the existing page structure (nav, stats cards, table) — enhance, don't rebuild
- Match the existing Tailwind styling conventions (white cards, shadow, rounded-lg, text-gray-500 labels)
- Handle empty states gracefully (no projects, no approved projects)
