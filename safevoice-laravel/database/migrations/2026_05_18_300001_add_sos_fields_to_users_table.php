<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Firebase FCM token — browser push notification এর জন্য
            $table->text('fcm_token')->nullable()->after('updated_at');

            // মোট কতবার SOS এ respond করেছে (unverified সহ)
            $table->integer('sos_helped_count')->default(0)->after('fcm_token');

            // Admin verify করেছে এমন SOS help count — এটা দিয়ে leaderboard ranking হবে
            $table->integer('sos_helped_verified_count')->default(0)->after('sos_helped_count');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['fcm_token', 'sos_helped_count', 'sos_helped_verified_count']);
        });
    }
};
