<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->default('game');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 12, 2)->default(0);
            $table->unsignedInteger('ram_mb')->default(512);
            $table->decimal('cpu_limit', 5, 2)->default(1);
            $table->unsignedInteger('disk_mb')->default(1024);
            $table->unsignedInteger('database_limit')->default(1);
            $table->unsignedInteger('backup_limit')->default(1);
            $table->unsignedInteger('allocation_limit')->default(1);
            $table->string('docker_image')->default('zy4/generic:latest');
            $table->text('startup_command')->nullable();
            $table->json('environment')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['product_id', 'slug']);
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('pending')->index();
            $table->decimal('total', 12, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('number')->unique();
            $table->string('status')->default('pending')->index();
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('method')->default('manual');
            $table->string('proof_path')->nullable();
            $table->string('status')->default('pending')->index();
            $table->text('note')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('products');
    }
};
