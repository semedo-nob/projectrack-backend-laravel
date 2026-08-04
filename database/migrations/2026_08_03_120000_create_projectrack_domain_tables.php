<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('name');
            $table->string('display_name');
            $table->string('category'); // weight|volume|count|length|area|time
            $table->double('to_base_factor');
            $table->string('base_unit');
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('name');
            $table->string('color')->nullable();
            $table->string('icon')->nullable();
            $table->double('budget')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('owner_id', 50)->nullable()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamp('start_date');
            $table->timestamp('end_date')->nullable();
            $table->double('budget')->default(0);
            $table->double('spent')->default(0);
            $table->string('status')->default('Planning');
            $table->string('category')->default('General');
            $table->double('progress')->default(0);
            $table->string('image_url')->nullable();
            $table->timestamps();

            $table->foreign('owner_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('project_tags', function (Blueprint $table) {
            $table->string('project_id', 50);
            $table->string('tag');
            $table->primary(['project_id', 'tag']);
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
        });

        Schema::create('project_members', function (Blueprint $table) {
            $table->id();
            $table->string('project_id', 50);
            $table->string('user_id', 50);
            $table->string('role', 32); // supervisor|owner|viewer
            $table->string('invited_by', 50)->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('invited_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('project_shares', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('project_id', 50);
            $table->string('created_by', 50);
            $table->string('role', 32)->default('owner');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('use_count')->default(0);
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('project_id', 50)->index();
            $table->string('merchant');
            $table->double('amount');
            $table->timestamp('date');
            $table->string('category');
            $table->text('notes')->nullable();
            $table->string('receipt_image')->nullable();
            $table->string('status')->default('pending');
            $table->double('quantity_original')->nullable();
            $table->string('unit_original')->nullable();
            $table->double('quantity_base')->nullable();
            $table->string('unit_base')->nullable();
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
        });

        Schema::create('receipts', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('expense_id', 50)->index();
            $table->string('image_url');
            $table->text('ocr_data')->nullable();
            $table->double('confidence')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('expense_id')->references('id')->on('expenses')->cascadeOnDelete();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('project_id', 50)->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('pending');
            $table->string('priority')->default('medium');
            $table->timestamp('due_date')->nullable();
            $table->string('assigned_to')->nullable();
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('project_id', 50)->index();
            $table->string('name');
            $table->string('name_key');
            $table->double('quantity_base')->default(0);
            $table->string('unit_base');
            $table->string('preferred_unit_id')->nullable();
            $table->double('reorder_level')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
        });

        Schema::create('material_usages', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('project_id', 50)->index();
            $table->string('inventory_item_id', 50)->index();
            $table->string('kind'); // purchase|usage|adjustment
            $table->double('quantity_base');
            $table->string('unit_base');
            $table->double('quantity_original')->nullable();
            $table->string('unit_original')->nullable();
            $table->text('notes')->nullable();
            $table->string('expense_id', 50)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->nullable();

            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->cascadeOnDelete();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('project_id', 50)->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('activity_type')->default('other');
            $table->timestamp('occurred_at');
            $table->double('hours_spent')->nullable();
            $table->string('performed_by')->nullable();
            $table->string('location')->nullable();
            $table->string('linked_expense_id', 50)->nullable();
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
        });

        Schema::create('project_milestones', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('project_id', 50)->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->string('status')->default('pending');
            $table->integer('sort_order')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
        });

        Schema::create('media', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('project_id', 50)->nullable()->index();
            $table->string('entity_type')->nullable();
            $table->string('entity_id', 50)->nullable();
            $table->string('path');
            $table->string('disk')->default('public');
            $table->string('mime')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->string('uploaded_by', 50)->nullable();
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('sync_tombstones', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');
            $table->string('entity_id', 50);
            $table->string('project_id', 50)->nullable()->index();
            $table->timestamp('deleted_at');
            $table->index(['entity_type', 'entity_id']);
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_tombstones');
        Schema::dropIfExists('media');
        Schema::dropIfExists('project_milestones');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('material_usages');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('project_shares');
        Schema::dropIfExists('project_members');
        Schema::dropIfExists('project_tags');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('units');
    }
};
