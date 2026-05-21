<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->enum('legal_consent',   ['yes', 'no'])->nullable()->after('is_anonymous');
            $table->enum('publish_consent', ['yes', 'no'])->nullable()->after('legal_consent');
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropColumn(['legal_consent', 'publish_consent']);
        });
    }
};