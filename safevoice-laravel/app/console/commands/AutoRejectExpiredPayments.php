<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Complaint;
use App\Models\PiNotification;

class AutoRejectExpiredPayments extends Command
{
    protected $signature   = 'safevoice:auto-reject';
    protected $description = 'Auto-reject PI cases where user did not pay within 3-day deadline';

    public function handle(): void
    {
        $neverPaid = Complaint::whereIn('status', ['PI Notification Sent', 'PI Payment Pending'])
    ->whereNotNull('payment_deadline')
    ->where('payment_deadline', '<', now())
    ->get();
        foreach ($neverPaid as $complaint) {
            $complaint->update(['status' => 'Rejected']);

            PiNotification::where('complaint_id', $complaint->complaint_id)
                ->update(['status' => 'dismissed', 'responded_at' => now()]);

            $this->info("Auto-rejected (no payment): {$complaint->complaint_id}");
        }

        $this->info("Done. {$neverPaid->count()} complaint(s) auto-rejected.");
    }
}