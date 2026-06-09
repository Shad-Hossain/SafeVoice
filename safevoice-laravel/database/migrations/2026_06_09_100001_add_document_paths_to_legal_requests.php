<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_requests', function (Blueprint $table) {
            $table->json('document_paths')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('legal_requests', function (Blueprint $table) {
            $table->dropColumn('document_paths');
        });
    }
};