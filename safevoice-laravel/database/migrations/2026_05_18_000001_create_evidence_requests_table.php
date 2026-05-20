<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_requests', function (Blueprint $table) {
            $table->id();
            $table->string('complaint_id', 30)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->text('admin_note')->nullable();
            // pending → user notified, waiting; submitted → user submitted evidence; skipped → user skipped; expired → 7 days passed without submission; dismissed → admin dismissed
            $table->enum('status', ['pending', 'submitted', 'skipped', 'expired', 'dismissed'])->default('pending');
            $table->datetime('requested_at')->useCurrent();
            $table->datetime('deadline')->nullable(); // requested_at + 7 days
            $table->datetime('responded_at')->nullable();
            $table->datetime('skip_until')->nullable(); // when user clicks "Skip for now"
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_requests');
    }
};
