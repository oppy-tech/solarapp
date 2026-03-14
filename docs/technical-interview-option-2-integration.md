# Technical Interview: API Design & Integration

## Overview
**Role:** Senior Product Engineer (Integrations Focus)
**Time Limit:** 4 Hours
**Tools:** You are encouraged to use AI coding assistants like Cursor, Claude Code, or similar.
**Deliverables:** A Design Document (Architecture Diagram + Explanation) and Code Samples (Controller/Model logic).

## Context
We are partnering with a major solar design software provider, "PermitTech". They want to integrate deeply with SolarAPP+ to allow their users (Installers) to seamlessly transition from designing a system in PermitTech to permitting it in SolarAPP+.

This is the first third-party integration SolarAPP+ has built. Whatever you design here will set the pattern for future partner integrations.

## The "PermitTech" Requirements
PermitTech has sent us the following requirements for their integration:

1.  **Programmatic Onboarding:** "We want to programmatically create Installer accounts in SolarAPP+ for our users so they don't have to sign up manually."
2.  **Identity Mapping:** "We will send our internal `permit_tech_user_id` when creating the account. You need to store this so we can link records later."
3.  **Status Sync:** "We need to know when the installer actually completes your registration process (e.g., accepts Terms of Service, uploads valid licenses). We don't want to poll your API."
4.  **Usage Tracking:** "We need a way to track how many projects our installers are submitting through SolarAPP+ and which Jurisdictions (AHJs) they are working in."

## Task: Design & Build

### Part 1: System Design (Diagram + Text)
Create a design document (in `PROCESS.md` or a separate file) that covers:

*   **Authentication:** How will PermitTech authenticate with our API to create installers? How will the *Installer* (the human) subsequently log in or authenticate when they jump from PermitTech to SolarAPP+?
*   **Duplicate Management:** How will you handle the case where an installer already has a SolarAPP+ account?
*   **Event Notification:** Design the mechanism to notify PermitTech when an installer becomes "Active" (licenses approved, ToS accepted). Think about what happens when things go wrong — PermitTech's servers are down, the notification fails, a notification is sent but PermitTech claims they never received it.

### Part 2: Implementation (Code Samples)
Using the provided stubs (`stubs/Installer.php`, `stubs/User.php`, etc.), write the PHP/Laravel code for:

*   **The Ingestion Endpoint:** `POST /api/partners/installers` (or similar). Implement the controller logic to handle the creation/lookup and storage of the `permit_tech_user_id`.
*   **The Notification System:** Implement the mechanism that notifies PermitTech when an installer's status changes. This should be production-ready — not just a proof-of-concept.

### Part 3: Production Readiness
In your design document, address:

*   **How would you test this integration** before going live with PermitTech? What does a test harness look like?
*   **On day one in production,** how will you know if the integration is working correctly? How will you know if it breaks?
*   **Six months from now,** PermitTech opens a support ticket saying "we sent a webhook 3 hours ago and the installer still isn't active." Walk through how you'd investigate this.

## Setup
You may use the Laravel environment to verify your API code. See [task-3-next-steps.md](task-3-next-steps.md) to run the setup script.

## Reference Files
*   `stubs/Installer.php`
*   `stubs/User.php`
*   `stubs/AhjController.php`
*   `stubs/Ahj.php`
*   `stubs/Project.php`
