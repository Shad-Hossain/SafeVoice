<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_payments', function (Blueprint $table) {
            $table->datetime('admin_contacted_at')->nullable()->after('disputed_at');
        });
    }

    public function down(): void
    {
        Schema::table('case_payments', function (Blueprint $table) {
            $table->dropColumn('admin_contacted_at');
        });
    }
};