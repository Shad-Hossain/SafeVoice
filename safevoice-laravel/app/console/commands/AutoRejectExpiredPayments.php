<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Complaint;

class AutoRejectExpiredPayments extends Command
{
    protected $signature   = 'safevoice:auto-reject';
    protected $description = 'Auto-reject complaints where PI payment deadline has passed';

    public function handle(): void
    {
        $expired = Complaint::where('status', 'PI Notification Sent')
            ->whereNotNull('payment_deadline')
            ->where('payment_deadline', '<', now())
            ->get();

        foreach ($expired as $complaint) {
            $complaint->update([
                'status' => 'Rejected',
            ]);
            $this->info("Auto-rejected: {$complaint->complaint_id}");
        }

        $this->info("Done. {$expired->count()} complaint(s) auto-rejected.");
    }
}
