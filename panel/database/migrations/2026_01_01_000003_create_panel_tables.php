<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nodes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('fqdn')->nullable();
            $table->string('scheme')->default('http');
            $table->string('daemon_url');
            $table->text('token');
            $table->string('public_ip')->nullable();
            $table->unsignedInteger('memory_mb')->default(0);
            $table->unsignedInteger('disk_mb')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->json('stats')->nullable();
            $table->timestamps();
        });

        Schema::create('docker_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('image');
            $table->text('startup_command')->nullable();
            $table->json('environment')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('node_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('server_id')->nullable()->index();
            $table->string('ip');
            $table->unsignedInteger('port');
            $table->string('alias')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->unique(['node_id', 'ip', 'port']);
        });

        Schema::create('servers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('node_id')->constrained()->restrictOnDelete();
            $table->foreignId('allocation_id')->nullable()->constrained('allocations')->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('installing')->index();
            $table->string('docker_image');
            $table->text('startup_command')->nullable();
            $table->json('environment')->nullable();
            $table->unsignedInteger('memory_mb')->default(512);
            $table->decimal('cpu_limit', 5, 2)->default(1);
            $table->unsignedInteger('disk_mb')->default(1024);
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('server_variables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->text('value')->nullable();
            $table->boolean('is_secret')->default(false);
            $table->timestamps();
            $table->unique(['server_id', 'key']);
        });

        Schema::create('server_databases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('node_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('username');
            $table->text('password');
            $table->string('host')->default('127.0.0.1');
            $table->unsignedInteger('port')->default(3306);
            $table->timestamps();
            $table->unique(['server_id', 'name']);
        });

        Schema::create('server_backups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('path')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('status')->default('pending')->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('server_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('cron');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();
        });

        Schema::create('server_schedule_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_schedule_id')->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->json('payload')->nullable();
            $table->unsignedInteger('sequence')->default(0);
            $table->timestamps();
        });

        Schema::create('server_subusers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('permissions')->nullable();
            $table->timestamps();
            $table->unique(['server_id', 'user_id']);
        });

        Schema::create('server_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('ip_address', 45)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('api_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('token', 80)->unique();
            $table->json('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('daemon_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('node_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('token');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('type')->default('string');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('daemon_tokens');
        Schema::dropIfExists('api_tokens');
        Schema::dropIfExists('server_activity_logs');
        Schema::dropIfExists('server_subusers');
        Schema::dropIfExists('server_schedule_tasks');
        Schema::dropIfExists('server_schedules');
        Schema::dropIfExists('server_backups');
        Schema::dropIfExists('server_databases');
        Schema::dropIfExists('server_variables');
        Schema::dropIfExists('servers');
        Schema::dropIfExists('allocations');
        Schema::dropIfExists('docker_templates');
        Schema::dropIfExists('nodes');
    }
};
