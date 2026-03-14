<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ahjs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address_line_1')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('contact_email')->nullable();
            $table->boolean('charges_fees')->default(false);
            $table->boolean('is_live')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ahj_id')->constrained();
            $table->string('title');
            $table->string('status')->default('draft');
            $table->string('project_type_id')->default('PV');
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
        });
        
        Schema::create('installers', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('email')->unique();
            $table->string('license_number')->nullable();
            $table->string('state')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
        
        // Add installer_id to users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('installer_id')->nullable()->constrained();
            $table->foreignId('ahj_id')->nullable()->constrained();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('installer_id');
            $table->dropConstrainedForeignId('ahj_id');
        });
        Schema::dropIfExists('projects');
        Schema::dropIfExists('installers');
        Schema::dropIfExists('ahjs');
    }
};
