<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sos_alerts', function (Blueprint $table) {
            // কত km radius এর মধ্যে notification যাবে (default 5km)
            $table->decimal('notification_radius_km', 5, 2)->default(5.00)->after('status');

            // FCM push পাঠানো হয়েছে কিনা track করতে
            $table->boolean('fcm_sent')->default(false)->after('notification_radius_km');

            // কতজনকে FCM push গেছে
            $table->integer('fcm_sent_count')->default(0)->after('fcm_sent');
        });
    }

    public function down(): void
    {
        Schema::table('sos_alerts', function (Blueprint $table) {
            $table->dropColumn(['notification_radius_km', 'fcm_sent', 'fcm_sent_count']);
        });
    }
};
