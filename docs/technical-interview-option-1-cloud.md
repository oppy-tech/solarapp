# Technical Interview: Cloud Infrastructure (AWS)

## Overview
**Role:** Senior Product Engineer
**Time Limit:** 4 Hours — use the time as you see fit.
**Tools:** You are encouraged to use AI coding assistants like Cursor, Claude Code, or similar.
**Deliverables:** A modified CloudFormation template and a Markdown file (`PROCESS.md`) describing your thought process, architectural decisions, and prompts used. No application code is required.

## Context
You are working on SolarAPP+. Currently, our main web application handles everything synchronously, including heavy tasks like PDF generation and sending emails. This causes timeouts and poor user experience during peak load.

We need to introduce an **Asynchronous Job Processing Architecture** using AWS SQS and a dedicated ECS Worker Service.

We have provided a **simplified** version of our infrastructure template (`stubs/solarapp.yaml`) that defines the current "Web App" service. This template was written by a previous contractor and deployed to production. It is self-contained; you do not need to add or integrate with other services or stacks.

**Scope:** This task focuses on **infrastructure only**. You are not expected to implement or design application logic (e.g. PDF generation, email sending, job handlers, or code that enqueues jobs). Assume the worker runs a standard queue worker from the same image as the web app; you do not need to implement or design the worker command or job processing logic. No application code or Dockerfile is required. In `PROCESS.md`, focus your written explanation on infrastructure and security decisions; you do not need to design application-level job handlers or payloads.

## Task: Architect Async Background Workers

**Your goal is to modify the existing template to add:**
1.  **An infrastructure diagram** — Describe what you want to do and how it will be implemented.
2.  **The Queue(s):** Add an SQS Queue to the CloudFormation template; add additional queues as you see fit.
3.  **A Worker Service:** Define a new ECS Service (`WorkerService`) in the template.
    *   It should run a separate task definition (same image as the web app, different command — e.g. a generic queue worker). You are adding the service and wiring only, not implementing job logic.
4.  **Security (IAM):**
    *   Create specific IAM Roles/Policies as needed, following least privilege.
5.  **Scaling Strategy:**
    *   Implement an Auto Scaling policy for the **Worker Service**.

The existing template is your starting point. Treat it as production infrastructure that you're extending — if you see anything that needs attention along the way, address it.

## Next Steps
You may not have time to finish everything in the allotted 4 hours. If you don't, please save 15 minutes to document what you would do next.

## Reference Files
*   `stubs/solarapp.yaml`
