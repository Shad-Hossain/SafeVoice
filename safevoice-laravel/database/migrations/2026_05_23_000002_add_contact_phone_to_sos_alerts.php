<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sos_alerts', function (Blueprint $table) {
            // Login ছাড়া SOS দিলে manually phone number দেওয়া যাবে
            $table->string('contact_phone', 20)->nullable()->after('user_id');
            $table->string('contact_name', 100)->nullable()->after('contact_phone');
        });
    }

    public function down(): void
    {
        Schema::table('sos_alerts', function (Blueprint $table) {
            $table->dropColumn(['contact_phone', 'contact_name']);
        });
    }
};
