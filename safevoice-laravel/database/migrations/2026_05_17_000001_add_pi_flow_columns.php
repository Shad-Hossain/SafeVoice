<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (!Schema::hasColumn('complaints', 'payment_deadline')) {
                $table->datetime('payment_deadline')->nullable()->after('status');
            }
            if (!Schema::hasColumn('complaints', 'pi_notified_at')) {
                $table->datetime('pi_notified_at')->nullable()->after('payment_deadline');
            }
            if (!Schema::hasColumn('complaints', 'assigned_pi_id')) {
                $table->unsignedBigInteger('assigned_pi_id')->nullable()->after('pi_notified_at');
            }
            if (!Schema::hasColumn('complaints', 'pi_assigned_at')) {
                $table->datetime('pi_assigned_at')->nullable()->after('assigned_pi_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('complaints', 'payment_deadline') ? 'payment_deadline' : null,
                Schema::hasColumn('complaints', 'pi_notified_at')   ? 'pi_notified_at'   : null,
                Schema::hasColumn('complaints', 'assigned_pi_id')   ? 'assigned_pi_id'   : null,
                Schema::hasColumn('complaints', 'pi_assigned_at')   ? 'pi_assigned_at'   : null,
            ]));
        });
    }
};