<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // anonymous complaint এ PI assign হলে victim কে modal দিয়ে জানানো হয়
        // victim "OK, I'll contact PI" click করলে এটা true হয় → modal আর আসে না
        Schema::table('complaints', function (Blueprint $table) {
            $table->boolean('pi_contact_acknowledged')->default(false)->after('pi_email_sent');
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropColumn('pi_contact_acknowledged');
        });
    }
};