<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * পুরনো records যেখানে preferred_district null কিন্তু preferred_city আছে,
     * সেগুলোতে preferred_district = preferred_city করে দাও।
     */
    public function up(): void
    {
        DB::table('legal_requests')
            ->whereNull('preferred_district')
            ->whereNotNull('preferred_city')
            ->update([
                'preferred_district' => DB::raw('preferred_city'),
            ]);
    }

    public function down(): void {}
};
