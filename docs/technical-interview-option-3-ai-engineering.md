# Technical Interview: Multi-Agent Engineering

## Overview
**Role:** Senior Product Engineer
**Time Limit:** 4 Hours
**Tools:** You are **expected** to use AI coding assistants (Claude Code, Cursor, Copilot, or similar). This task is specifically about building and orchestrating multi-agent workflows.
**Deliverables:** A working multi-agent setup, the output it produces, and `PROCESS.md`.

## Context

SolarAPP+ is preparing for a quarterly release. Three related-but-independent changes need to ship together. Your engineering lead wants to see how you'd use AI agents to tackle them in parallel — not just for speed, but with proper coordination, verification, and guardrails.

You've been given a working Laravel application (SQLite, pre-seeded data). Your job is to build a multi-agent workflow that can execute the tasks below, then run it and deliver the results.

## The Tasks Your Agents Should Execute

Your multi-agent workflow should accomplish all three of these:

### Task A: Dashboard Analytics (Backend)
Update `DashboardController.php` to:
- Accept `start_date` and `end_date` query parameters and filter all stats by date range
- Calculate and return the **average approval time** (time between `submitted_at` and `approved_at`) for approved projects in the range
- Replace the current `limit(10)` with proper server-side pagination (20 per page)

### Task B: Dashboard UI (Frontend)
Update `dashboard.blade.php` to:
- Add a date range picker that submits start/end dates
- Display the average approval time in a human-readable format (e.g., "3 days, 6 hours")
- Add pagination navigation links
- Persist the selected date range in the URL so the view is shareable/bookmarkable

### Task C: Data Integrity Report
Write a script, command, or agent workflow that:
- Scans the project data for integrity issues (inconsistent statuses, impossible timestamps, missing required fields)
- Produces a report of what it found and how each issue should be handled
- The report should be saved as `DATA_INTEGRITY_REPORT.md`

## What You're Actually Building

The above tasks are the *inputs* to your workflow. What we want to see is:

1. **The workflow itself.** How do you set up, coordinate, and run multiple agents? This could be:
   - Multiple Claude Code sessions with different instructions
   - A script/config that orchestrates agents (e.g., Claude Agent SDK, custom bash orchestration, Cursor rules)
   - A CLAUDE.md or system prompt strategy that decomposes work
   - Any other approach you can demonstrate

2. **Guardrails.** What constraints or instructions do you give each agent to prevent them from:
   - Breaking each other's work
   - Introducing performance problems (e.g., loading full tables into memory)
   - Missing edge cases in the data
   - Violating multi-tenancy (there are two AHJs in the database — agents should scope to the correct one)

3. **Verification.** How do you know the agents' output is correct? Show us your verification strategy — this could include:
   - Tests (written by you or by another agent)
   - A "reviewer" agent that checks the others' work
   - Manual spot-checks with documented results
   - Running the app and testing specific scenarios

4. **The output.** The actual working code and data integrity report produced by your agents.

## Setup

Run the setup script to get your Laravel environment:
```bash
chmod +x setup.sh
./setup.sh
cd solar-interview
php artisan serve
```

See [task-3-next-steps.md](task-3-next-steps.md) for details.

## Deliverables

1. **The working code** — Tasks A, B, and C completed
2. **Your workflow artifacts** — whatever you used to orchestrate agents (scripts, configs, prompts, CLAUDE.md files, etc.)
3. **DATA_INTEGRITY_REPORT.md** — the output from Task C
4. **PROCESS.md** — see the [README](README.md) for the specific questions to answer. In addition, describe:
   - How you decomposed the work across agents
   - What guardrails you set up and why
   - Where agents succeeded and where they failed
   - What you'd do differently next time

## Reference Files
*   `stubs/DashboardController.php`
*   `stubs/dashboard.blade.php`
*   `stubs/Project.php`
*   `stubs/Ahj.php`
