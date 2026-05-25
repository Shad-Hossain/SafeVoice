<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evidence_requests', function (Blueprint $table) {
            $table->unsignedTinyInteger('days')->default(7)->after('deadline');
        });
    }

    public function down(): void
    {
        Schema::table('evidence_requests', function (Blueprint $table) {
            $table->dropColumn('days');
        });
    }
};