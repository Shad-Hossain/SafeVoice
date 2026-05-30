<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // anonymous_user_id এ foreign key আছে — আগে drop করো
        // এই foreign key আর দরকার নেই কারণ এখন hash রাখা হচ্ছে, user_id নয়
        DB::statement("ALTER TABLE complaints DROP FOREIGN KEY complaints_anonymous_user_id_foreign");

        // এখন column size বাড়াও (SHA-256 = 64 chars)
        DB::statement("ALTER TABLE complaints MODIFY anonymous_user_id VARCHAR(64) NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE complaints MODIFY anonymous_user_id VARCHAR(20) NULL");
    }
};