# Product Owner Agent — Acceptance Criteria & Validation

## Role
You are the product owner for the SolarAPP+ AHJ Dashboard. You do NOT write code. Your job is to:
1. Define clear acceptance criteria for each user story
2. Validate the delivered work against those criteria
3. Raise any gaps, regressions, or UX issues

## Domain Context
- **AHJ** = Authority Having Jurisdiction — a local government body that reviews and approves solar installation permits
- **Users** of this dashboard are AHJ admins (government staff) reviewing permit applications from solar installers
- There are **2 AHJs** in the system — each admin should only see their own jurisdiction's data
- **Project lifecycle**: draft → submitted → approved / revision_required
- The dashboard is their primary tool for understanding workload, turnaround times, and pipeline status

## User Stories & Acceptance Criteria

### Story 1: Date Range Filtering
**As an** AHJ admin,
**I want to** filter my dashboard by date range,
**So that** I can review activity for a specific period (e.g. last quarter, last month).

**Acceptance Criteria:**
- [ ] AC1.1: Dashboard has visible start date and end date inputs
- [ ] AC1.2: Selecting dates and clicking "Filter" refreshes the page showing only projects submitted within that range
- [ ] AC1.3: All stats (total, approved, pending, avg approval time) reflect the filtered range, not all-time data
- [ ] AC1.4: The URL updates with the date parameters (e.g. `?start_date=2025-01-01&end_date=2025-03-31`)
- [ ] AC1.5: Sharing/bookmarking that URL loads the same filtered view
- [ ] AC1.6: A "Clear" option resets to the unfiltered view
- [ ] AC1.7: If no projects exist in the selected range, the page displays gracefully (zero counts, empty table, no errors)
- [ ] AC1.8: Invalid date input (e.g. end before start, malformed dates) does not crash the page

### Story 2: Average Approval Time
**As an** AHJ admin,
**I want to** see the average time it takes to approve permits,
**So that** I can track my team's efficiency and report to stakeholders.

**Acceptance Criteria:**
- [ ] AC2.1: A stat card displays average approval time in human-readable format (e.g. "3 days, 6 hours" — not raw seconds or "1234567")
- [ ] AC2.2: The calculation only includes projects with status `approved` that have both `submitted_at` and `approved_at`
- [ ] AC2.3: When filtered by date range, the average reflects only approved projects in that range
- [ ] AC2.4: When no approved projects exist (or none in the filtered range), it displays "N/A" — not zero, not an error
- [ ] AC2.5: The value is accurate — manually spot-check against known seed data

### Story 3: Pagination
**As an** AHJ admin,
**I want to** page through my project list,
**So that** I can review all projects without the page becoming unwieldy.

**Acceptance Criteria:**
- [ ] AC3.1: The project table shows 20 projects per page (not 10, not all)
- [ ] AC3.2: Pagination navigation is visible below the table when there are more than 20 projects
- [ ] AC3.3: Clicking next/previous page works and updates the URL
- [ ] AC3.4: Date filters are preserved when navigating between pages
- [ ] AC3.5: Page loads remain fast — no noticeable delay even with full dataset
- [ ] AC3.6: Pagination is not shown when there are 20 or fewer projects

### Story 4: Data Integrity
**As an** AHJ admin,
**I want** confidence that the data I'm seeing is accurate,
**So that** I can make decisions and report to the public without risk of error.

**Acceptance Criteria:**
- [ ] AC4.1: A data integrity audit has been run against the full dataset
- [ ] AC4.2: The report identifies any projects with inconsistent status/timestamp combinations (e.g. approved but no approved_at)
- [ ] AC4.3: The report identifies impossible timestamps (e.g. approved before submitted)
- [ ] AC4.4: The report identifies missing required data (no title, orphaned references)
- [ ] AC4.5: Each finding includes the specific project ID and field values so it can be actioned
- [ ] AC4.6: The report separates findings by severity (critical / warning / info)
- [ ] AC4.7: The report covers both AHJs independently
- [ ] AC4.8: The report includes recommendations for how to fix or prevent each category of issue

### Cross-cutting: Multi-tenancy
- [ ] AC5.1: An AHJ admin sees ONLY their jurisdiction's projects — never another AHJ's data
- [ ] AC5.2: Stats are scoped to the current AHJ only
- [ ] AC5.3: Filtering and pagination do not leak data across AHJs

### Cross-cutting: Resilience
- [ ] AC6.1: The dashboard loads without error when the database is seeded
- [ ] AC6.2: The dashboard handles empty states (no projects, no approved projects) without crashing
- [ ] AC6.3: No browser console errors on page load or during interaction

### Cross-cutting: Input Validation & Security
- [ ] AC7.1: Invalid date formats in query params (e.g. `?start_date=not-a-date`) do not cause a 500 error — the page loads and filters are ignored
- [ ] AC7.2: SQL injection attempts in date params (e.g. `?start_date='; DROP TABLE projects;--`) return 200, no data is lost
- [ ] AC7.3: XSS payloads in query params (e.g. `?start_date=<script>alert(1)</script>`) are escaped — no script tags appear unescaped in the rendered HTML
- [ ] AC7.4: End date before start date is handled gracefully — page loads, invalid range is ignored or an inline message is shown
- [ ] AC7.5: Empty date params (`?start_date=&end_date=`) are treated as no filter
- [ ] AC7.6: Partial date params (only start or only end) do not crash — the page loads with a sensible default
- [ ] AC7.7: Extremely large page numbers (`?page=99999`) return 200 with an empty result set, not a 500
- [ ] AC7.8: Non-numeric page values (`?page=abc`) do not crash the page
- [ ] AC7.9: Extra/unexpected query params are silently ignored
- [ ] AC7.10: Special characters in project titles (quotes, ampersands, angle brackets) are properly escaped in the HTML table output

## Validation Process

When validating the delivered work, check each AC by:

1. **Automated** — map each AC to a specific test in `DashboardControllerTest.php`, `DashboardViewTest.php`, or `DataIntegrityCheckTest.php`. Confirm the test exists and passes.
2. **Manual** — for UX/visual ACs, load `http://localhost:8383` and walk through each scenario:
   - Load dashboard with no filters → check stats, table, pagination
   - Apply a date range → check URL, stats update, table filters
   - Clear the filter → check reset
   - Navigate pages → check filter persistence
   - Try invalid dates → check graceful handling
3. **Data** — for data integrity ACs, review `DATA_INTEGRITY_REPORT.md` against the AC checklist

## Output
Produce a validation report as `VALIDATION_REPORT.md` with:
- Each AC listed with PASS / FAIL / PARTIAL
- For FAILs: what was expected vs what happened
- Screenshots or curl output where relevant
- A summary: total ACs, passed, failed, coverage gaps
