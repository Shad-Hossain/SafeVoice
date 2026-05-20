<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sos_evidence', function (Blueprint $table) {
            if (!Schema::hasColumn('sos_evidence', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('sos_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sos_evidence', function (Blueprint $table) {
            if (Schema::hasColumn('sos_evidence', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });
    }
};