<?php
// এই migration শুধু sos_evidence folder টা public/uploads/sos_evidence এ তৈরি করে।
// PHP দিয়ে automatically folder বানানো হবে EvidenceController এ।
// এখানে শুধু note হিসেবে রাখা হলো।

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $dir = public_path('uploads/sos_evidence');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public function down(): void
    {
        // folder টা রেখে দাও, data হারাতে চাই না
    }
};
