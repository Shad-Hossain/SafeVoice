<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            // Anonymous complaint করলেও user জানতে পারবে — admin জানবে না
            // user_id = null থাকে (admin দেখে না), কিন্তু anonymous_user_id দিয়ে
            // user নিজের complaint list ও notification পাবে
            $table->unsignedBigInteger('anonymous_user_id')->nullable()->after('user_id');
            $table->foreign('anonymous_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropForeign(['anonymous_user_id']);
            $table->dropColumn('anonymous_user_id');
        });
    }
};
