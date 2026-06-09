<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lawyers', function (Blueprint $table) {
            if (!Schema::hasColumn('lawyers', 'warning_count')) {
                $table->integer('warning_count')->default(0)->after('status');
            }
            if (!Schema::hasColumn('lawyers', 'admin_note')) {
                $table->text('admin_note')->nullable()->after('warning_count');
            }
            if (!Schema::hasColumn('lawyers', 'suspended_until')) {
                $table->datetime('suspended_until')->nullable()->after('admin_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lawyers', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('lawyers', 'warning_count') ? 'warning_count' : null,
                Schema::hasColumn('lawyers', 'admin_note')    ? 'admin_note'    : null,
                Schema::hasColumn('lawyers', 'suspended_until') ? 'suspended_until' : null,
            ]));
        });
    }
};
