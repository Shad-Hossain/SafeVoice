<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lawyer_commission_payments', function (Blueprint $table) {
            $table->id();
            $table->string('ref_code', 30)->unique(); // COMM-20260609-0001
            $table->unsignedBigInteger('lawyer_id');
            $table->decimal('amount', 10, 2);          // lawyer যত টাকা pay করেছে
            $table->enum('method', ['bkash', 'rocket', 'nagad', 'bank']);
            $table->string('transaction_ref', 100);    // bKash/bank transaction ID
            $table->string('screenshot_path', 300)->nullable(); // optional proof
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_note')->nullable();
            $table->datetime('submitted_at')->useCurrent();
            $table->datetime('reviewed_at')->nullable();
            $table->foreign('lawyer_id')->references('id')->on('lawyers')->cascadeOnDelete();
            $table->index(['lawyer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lawyer_commission_payments');
    }
};