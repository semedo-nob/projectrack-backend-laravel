<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            if (! Schema::hasColumn('receipts', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });
        Schema::table('material_usages', function (Blueprint $table) {
            if (! Schema::hasColumn('material_usages', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });

        DB::table('receipts')->whereNull('updated_at')->update([
            'updated_at' => DB::raw('created_at'),
        ]);
        DB::table('material_usages')->whereNull('updated_at')->update([
            'updated_at' => DB::raw('created_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            if (Schema::hasColumn('receipts', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
        Schema::table('material_usages', function (Blueprint $table) {
            if (Schema::hasColumn('material_usages', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }
};
