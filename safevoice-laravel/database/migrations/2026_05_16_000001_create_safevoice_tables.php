<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. users
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 150)->unique();
            $table->string('phone', 20)->unique();
            $table->string('password_hash', 255);
            $table->enum('id_type', ['nid', 'birth_certificate']);
            $table->string('id_number', 50);
            $table->string('id_document_path', 255)->nullable();
            $table->string('location', 255)->nullable();
            $table->string('profile_photo', 255)->nullable();
            $table->enum('status', ['Active', 'Suspended', 'Probation', 'Banned'])->default('Active');
            $table->integer('complaints_count')->default(0);
            $table->datetime('joined_at')->useCurrent();
            $table->datetime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // 2. officers
        Schema::create('officers', function (Blueprint $table) {
            $table->id();
            $table->string('officer_code', 20)->unique();
            $table->string('name', 100);
            $table->string('badge', 50)->nullable();
            $table->string('department', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('assigned_cases')->default(0);
            $table->datetime('created_at')->useCurrent();
        });

        // 3. complaints
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('complaint_id', 20)->unique();
            $table->string('type', 50);
            $table->datetime('incident_date')->nullable();
            $table->string('location', 255)->nullable();
            $table->text('description');
            $table->boolean('is_anonymous')->default(false);
            $table->enum('status', [
                'Submitted', 'Under Review', 'PI Notification Sent',
                'PI Payment Confirmed', 'Private Investigator Assigned',
                'Resolved', 'Rejected'
            ])->default('Submitted');
            $table->string('assigned_officer_code', 20)->nullable()->index();
            $table->datetime('submitted_at')->useCurrent();
            $table->datetime('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->string('user_name', 100)->nullable();
            $table->string('user_phone', 20)->nullable();
            $table->string('user_email', 150)->nullable();
            $table->text('user_address')->nullable();
            $table->unsignedBigInteger('assigned_pi_id')->nullable();
            $table->datetime('pi_assigned_at')->nullable();
            $table->boolean('pi_email_sent')->default(false);
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->json('evidence_files')->nullable();

            $table->foreign('assigned_officer_code')->references('officer_code')->on('officers')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // 4. complaint_evidence
        Schema::create('complaint_evidence', function (Blueprint $table) {
            $table->id();
            $table->string('complaint_id', 30)->index();
            $table->string('file_path', 500);
            $table->string('file_name', 255);
            $table->datetime('uploaded_at')->useCurrent();
        });

        // 5. password_resets
        Schema::create('password_resets', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->index();
            $table->string('otp_code', 6);
            $table->datetime('expires_at');
            $table->boolean('used')->default(false);
            $table->datetime('created_at')->useCurrent();
        });

        // 6. private_investigators
        Schema::create('private_investigators', function (Blueprint $table) {
            $table->id();
            $table->string('pi_code', 20)->unique();
            $table->string('full_name', 100);
            $table->string('email', 150)->unique();
            $table->string('phone', 20);
            $table->text('address');
            $table->string('nid_number', 30)->unique();
            $table->string('photo_url', 500)->nullable();
            $table->string('nid_photo_url', 500)->nullable();
            $table->string('login_email', 150);
            $table->string('password_hash', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('active_cases')->default(0);
            $table->integer('total_cases')->default(0);
            $table->datetime('joined_at')->useCurrent();
            $table->text('notes')->nullable();
        });

        // 7. pi_notifications
        Schema::create('pi_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('complaint_id', 20);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('status', ['sent', 'payment_pending', 'payment_confirmed', 'dismissed'])->default('sent');
            $table->datetime('sent_at')->useCurrent();
            $table->datetime('responded_at')->nullable();
        });

        // 8. pi_payments
        Schema::create('pi_payments', function (Blueprint $table) {
            $table->id();
            $table->string('complaint_id', 20);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->decimal('amount', 10, 2)->default(1000.00);
            $table->enum('payment_method', ['bkash', 'nagad', 'rocket', 'bank']);
            $table->string('sender_number', 20)->nullable();
            $table->string('txn_id', 50)->nullable()->unique();
            $table->enum('status', ['pending', 'confirmed', 'failed'])->default('pending');
            $table->datetime('initiated_at')->useCurrent();
            $table->datetime('confirmed_at')->nullable();
        });

        // 9. sos_alerts
        Schema::create('sos_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('latitude', 50)->nullable();
            $table->string('longitude', 50)->nullable();
            $table->text('location_text')->nullable();
            $table->string('crime_type', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->boolean('notification_sent')->default(false);
            $table->integer('notified_count')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        // 10. sos_evidence
        Schema::create('sos_evidence', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sos_id');
            $table->text('file_path')->nullable();
            $table->string('file_type', 50)->nullable();
            $table->timestamp('uploaded_at')->useCurrent();
        });

        // 11. sos_notifications
        Schema::create('sos_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sos_id')->index();
            $table->unsignedBigInteger('notified_user_id')->index();
            $table->enum('status', ['sent', 'responded', 'ignored'])->default('sent');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['sos_id', 'notified_user_id'], 'unique_notif');
        });

        // 12. sos_responders
        Schema::create('sos_responders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sos_id');
            $table->unsignedBigInteger('responder_id');
            $table->timestamp('responded_at')->useCurrent();

            $table->unique(['sos_id', 'responder_id'], 'unique_resp');
        });

        // 13. super_admins
        Schema::create('super_admins', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('password_hash', 255);
            $table->datetime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('super_admins');
        Schema::dropIfExists('sos_responders');
        Schema::dropIfExists('sos_notifications');
        Schema::dropIfExists('sos_evidence');
        Schema::dropIfExists('sos_alerts');
        Schema::dropIfExists('pi_payments');
        Schema::dropIfExists('pi_notifications');
        Schema::dropIfExists('private_investigators');
        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('complaint_evidence');
        Schema::dropIfExists('complaints');
        Schema::dropIfExists('officers');
        Schema::dropIfExists('users');
    }
};