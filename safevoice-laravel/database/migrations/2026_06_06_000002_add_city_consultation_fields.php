<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // legal_requests এ preferred_city যোগ করো
        Schema::table('legal_requests', function (Blueprint $table) {
            $table->string('preferred_city', 100)->nullable()->after('location');
        });

        // lawyer_bids এ consultation_date + office_address যোগ করো
        Schema::table('lawyer_bids', function (Blueprint $table) {
            $table->datetime('consultation_date')->nullable()->after('cover_note');
            $table->string('office_address', 300)->nullable()->after('consultation_date');
        });
    }

    public function down(): void
    {
        Schema::table('legal_requests', function (Blueprint $table) {
            $table->dropColumn('preferred_city');
        });
        Schema::table('lawyer_bids', function (Blueprint $table) {
            $table->dropColumn(['consultation_date', 'office_address']);
        });
    }
};