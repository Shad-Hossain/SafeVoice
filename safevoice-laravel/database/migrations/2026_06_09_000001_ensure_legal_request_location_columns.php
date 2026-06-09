<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('legal_requests', 'preferred_city')) {
                $table->string('preferred_city', 100)->nullable()->after('location');
            }
            if (!Schema::hasColumn('legal_requests', 'preferred_division')) {
                $table->string('preferred_division', 100)->nullable()->after('preferred_city');
            }
            if (!Schema::hasColumn('legal_requests', 'preferred_district')) {
                $table->string('preferred_district', 100)->nullable()->after('preferred_division');
            }
        });
    }

    public function down(): void {}
};
