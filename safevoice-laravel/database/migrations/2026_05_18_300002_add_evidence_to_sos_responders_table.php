<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sos_responders', function (Blueprint $table) {
            // Evidence file path (photo বা video)
            $table->text('evidence_path')->nullable()->after('responded_at');

            // Evidence এর ধরন
            $table->string('file_type', 20)->nullable()->after('evidence_path'); // image / video

            // Admin verify status
            $table->enum('evidence_status', ['none', 'pending', 'approved', 'rejected'])
                  ->default('none')
                  ->after('file_type');

            // Admin এর note (reject করলে কারণ লিখবে)
            $table->text('admin_note')->nullable()->after('evidence_status');

            // কখন evidence submit হয়েছে
            $table->timestamp('evidence_submitted_at')->nullable()->after('admin_note');

            // কখন admin verify করেছে
            $table->timestamp('verified_at')->nullable()->after('evidence_submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('sos_responders', function (Blueprint $table) {
            $table->dropColumn([
                'evidence_path',
                'file_type',
                'evidence_status',
                'admin_note',
                'evidence_submitted_at',
                'verified_at',
            ]);
        });
    }
};
