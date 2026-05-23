<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PiCaseAssignment;
use App\Http\Controllers\PiCaseAssignmentController;

class AutoExpirePiAssignments extends Command
{
    protected $signature   = 'safevoice:expire-pi-assignments';
    protected $description = 'Auto-expire pending PI assignment tokens after 48 hours and forward to next PI';

    public function handle(): void
    {
        // যেসব assignment এখনো pending কিন্তু token expire হয়ে গেছে
        $expired = PiCaseAssignment::where('status', 'pending')
            ->where('token_expires_at', '<', now())
            ->get();

        if ($expired->isEmpty()) {
            $this->info('No expired PI assignments found.');
            return;
        }

        $controller = new PiCaseAssignmentController();

        foreach ($expired as $assignment) {
            // Assignment টাকে expired mark করো
            $assignment->update([
                'status'   => 'expired',
                'acted_at' => now(),
            ]);

            $complaintId = $assignment->complaint_id;
            $piCode      = optional($assignment->pi)->pi_code ?? 'N/A';

            $this->info("Expired assignment: Complaint #{$complaintId} — PI {$piCode} did not respond in 48h.");

            // পরের PI তে forward করো (existing method ব্যবহার করে)
            $result = $controller->sendToNextPi($complaintId);

            if ($result['success']) {
                $this->info("  ✅ Forwarded to next PI: {$result['pi_code']}");
            } else {
                $this->warn("  ⚠️  {$result['message']}");
            }
        }

        $this->info("Done. {$expired->count()} assignment(s) processed.");
    }
}