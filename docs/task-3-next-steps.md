# Laravel Environment Setup

Use this guide to set up the Laravel environment. **Required for Option 3, optional for Option 2, not needed for Option 1.**

To choose your task, see [**README.md**](README.md).

You have been provided with a `setup.sh` script that builds a functional Laravel environment.

*   **Option 3 (AI-Assisted Engineering):** You are **expected** to use this environment to build the dashboard feature.
*   **Option 2 (Integration):** You may use this environment to verify your API code, but your primary deliverables are a Design Doc and Code Samples.
*   **Option 1 (Cloud):** This environment is not needed for your task (CloudFormation).

## Prerequisites
*   **PHP 8.1+**
*   **Composer** (PHP Package Manager)

## Quick Start

1.  **Run the Setup Script:**
    From this directory (the package root), run:
    ```bash
    chmod +x setup.sh
    ./setup.sh
    ```

    This will:
    *   Download a fresh Laravel application into `solar-interview/`.
    *   Copy the Models, Controllers, and Views into the correct places.
    *   Configure the routes.
    *   Set up a SQLite database with ~160 pre-seeded projects.

2.  **Start the Server:**
    ```bash
    cd solar-interview
    php artisan serve
    ```

3.  **View the App:**
    Open `http://localhost:8000` in your browser. You should see the "City of Solarville" dashboard populated with sample data.

## Working on the Task

### For Option 3 (AI-Assisted Engineering)
*   Edit `app/Http/Controllers/Ahj/DashboardController.php`.
*   Edit `resources/views/pages/ahj/dashboard.blade.php`.
*   The app uses a SQLite database that was populated by the setup script. The data comes from `stubs/sample_data.json` — you shouldn't need to modify the seed data, but you're welcome to inspect it.

### For Option 2 (Integration)
*   The stub API controller is at `app/Http/Controllers/Api/AhjController.php`.
*   A basic API route is pre-configured in `routes/api.php` — you'll likely want to add your own routes.

## AI Tools
You are encouraged to use Cursor, Claude Code, or Copilot. We'll be asking you about your usage and prompts during the follow-up interview.
