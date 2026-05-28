<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Helpers\AnonymousId;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. users table: email_hash যোগ করো (login lookup এর জন্য) ──
        // কারণ: পরে email encrypt করলে WHERE email = ? কাজ করবে না
        // email_hash দিয়ে খুঁজে পাবে, কিন্তু hash দেখে কেউ email জানতে পারবে না
        if (!Schema::hasColumn('users', 'email_hash')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('email_hash', 64)->nullable()->after('email');
            });

            // বিদ্যমান সব user এর email hash করো
            DB::table('users')->orderBy('id')->each(function ($user) {
                DB::table('users')->where('id', $user->id)->update([
                    'email_hash' => hash('sha256', strtolower(trim($user->email))),
                ]);
            });

            // এখন unique index দাও (nullable তাই শুধু non-null এ)
            Schema::table('users', function (Blueprint $table) {
                $table->string('email_hash', 64)->unique()->nullable()->change();
            });
        }

        // ── 2. complaint_principals table: anonymous complaint এর encrypted user_id ──
        // Admin কখনো এই table দেখবে না। শুধু notification পাঠাতে ব্যবহার হবে।
        if (!Schema::hasTableListing() || !Schema::hasTable('complaint_principals')) {
            Schema::create('complaint_principals', function (Blueprint $table) {
                $table->id();
                $table->string('complaint_id', 20)->unique()->index();
                $table->text('encrypted_user_id'); // Crypt::encryptString() দিয়ে encrypt
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // ── 3. বিদ্যমান complaints এর anonymous_user_id HMAC hash এ convert করো ──
        // আগে plaintext user_id ছিল, এখন hash হবে
        DB::table('complaints')
            ->whereNotNull('anonymous_user_id')
            ->orderBy('id')
            ->each(function ($complaint) {
                $rawId = $complaint->anonymous_user_id;

                // শুধু numeric id গুলো convert করো (আগের plaintext format)
                if (is_numeric($rawId)) {
                    DB::table('complaints')
                        ->where('id', $complaint->id)
                        ->update([
                            'anonymous_user_id' => AnonymousId::make((int) $rawId),
                        ]);

                    // complaint_principals এ encrypted version store করো
                    \Illuminate\Support\Facades\DB::table('complaint_principals')->updateOrInsert(
                        ['complaint_id' => $complaint->complaint_id],
                        [
                            'encrypted_user_id' => \Illuminate\Support\Facades\Crypt::encryptString($rawId),
                            'created_at' => now(),
                        ]
                    );
                }
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_hash');
        });
        Schema::dropIfExists('complaint_principals');
    }
};
