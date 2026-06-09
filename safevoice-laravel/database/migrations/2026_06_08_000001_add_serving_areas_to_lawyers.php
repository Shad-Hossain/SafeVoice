<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── lawyers table ──────────────────────────────────────────────
        Schema::table('lawyers', function (Blueprint $table) {
            // serving_areas: ["Dhaka", "Gazipur", "Narayanganj"] — lawyer যে district/city গুলোতে কাজ করে
            $table->json('serving_areas')->nullable()->after('city');
            // division: "Dhaka", "Chittagong" etc.
            $table->string('division', 100)->nullable()->after('city');
        });

        // ── legal_requests table ───────────────────────────────────────
        Schema::table('legal_requests', function (Blueprint $table) {
            // user এর division preference
            $table->string('preferred_division', 100)->nullable()->after('preferred_city');
            // user এর specific district
            $table->string('preferred_district', 100)->nullable()->after('preferred_division');
        });
    }

    public function down(): void
    {
        Schema::table('lawyers', function (Blueprint $table) {
            $table->dropColumn(['serving_areas', 'division']);
        });
        Schema::table('legal_requests', function (Blueprint $table) {
            $table->dropColumn(['preferred_division', 'preferred_district']);
        });
    }
};