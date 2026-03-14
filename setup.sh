#!/bin/bash

# SolarAPP+ Technical Interview Setup Script
# This script scaffolds a fresh Laravel application and injects the interview stubs.

set -e

PROJECT_NAME="solar-interview"

echo "☀️  Setting up SolarAPP+ Interview Environment..."

# 1. Check Prerequisites
if ! command -v composer &> /dev/null; then
    echo "❌ Error: 'composer' is not installed. Please install PHP Composer first."
    exit 1
fi

if ! command -v php &> /dev/null; then
    echo "❌ Error: 'php' is not installed."
    exit 1
fi

# 2. Create Laravel Project
if [ -d "$PROJECT_NAME" ]; then
    echo "⚠️  Directory '$PROJECT_NAME' already exists. Skipping create-project."
else
    echo "📦 Creating fresh Laravel project..."
    composer create-project laravel/laravel "$PROJECT_NAME" --quiet
fi

# 3. Inject Stubs
echo "💉 Injecting interview code..."
cd "$PROJECT_NAME"

# Create directories
mkdir -p app/Models
mkdir -p app/Http/Controllers/Ahj
mkdir -p app/Http/Controllers/Api
mkdir -p resources/views/pages/ahj
mkdir -p resources/views/layouts
mkdir -p stubs

# Copy Stub Files
cp ../stubs/Ahj.php app/Models/
cp ../stubs/Project.php app/Models/
cp ../stubs/Installer.php app/Models/
cp ../stubs/User.php app/Models/
cp ../stubs/DashboardController.php app/Http/Controllers/Ahj/
cp ../stubs/AhjController.php app/Http/Controllers/Api/
cp ../stubs/dashboard.blade.php resources/views/pages/ahj/
cp ../stubs/app.blade.php resources/views/layouts/
cp ../stubs/sample_data.json stubs/
cp ../stubs/InterviewSeeder.php database/seeders/
cp ../stubs/2026_01_01_000000_create_interview_tables.php database/migrations/

# 4. Configure Routes
echo "🔗 Configuring routes..."
cat <<EOF >> routes/web.php

// SolarAPP+ Interview Routes
use App\Http\Controllers\Ahj\DashboardController;
Route::get('/', [DashboardController::class, 'index']);
EOF

cat <<EOF >> routes/api.php

// SolarAPP+ Interview Routes
use App\Http\Controllers\Api\AhjController;
Route::post('/partners/installers', [AhjController::class, 'store']);
EOF

# 5. Database Setup (SQLite)
echo "🗄️  Setting up SQLite Database..."
# Force DB_CONNECTION=sqlite in .env
if grep -q "DB_CONNECTION=mysql" .env; then
    sed -i.bak 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/g' .env
    # Comment out other DB vars
    sed -i.bak 's/^DB_HOST/#DB_HOST/g' .env
    sed -i.bak 's/^DB_PORT/#DB_PORT/g' .env
    sed -i.bak 's/^DB_DATABASE/#DB_DATABASE/g' .env
    sed -i.bak 's/^DB_USERNAME/#DB_USERNAME/g' .env
    sed -i.bak 's/^DB_PASSWORD/#DB_PASSWORD/g' .env
fi

touch database/database.sqlite

# Clean up sed backup files
rm -f .env.bak

# Run Migrations & Seeder
php artisan migrate:fresh --quiet
php artisan db:seed --class=InterviewSeeder --quiet

# 6. Final Instructions
echo ""
echo "✅ Setup Complete!"
echo ""
echo "To start the application:"
echo "  cd $PROJECT_NAME"
echo "  php artisan serve"
echo ""
echo "Then visit: http://localhost:8000"
echo "Good luck! 🚀"
