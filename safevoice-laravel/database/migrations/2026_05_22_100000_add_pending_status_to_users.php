<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('Active','Suspended','Probation','Banned','Pending') DEFAULT 'Active'");

            $table->text('rejection_reason')->nullable()->after('id_document_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('Active','Suspended','Probation','Banned') DEFAULT 'Active'");
            $table->dropColumn('rejection_reason');
        });
    }
};