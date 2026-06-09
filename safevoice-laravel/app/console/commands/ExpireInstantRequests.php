<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\LegalRequest;

class ExpireInstantRequests extends Command
{
    protected $signature   = 'legal:expire-instant';
    protected $description = 'Expire instant legal requests past their 30-min deadline';

    public function handle(): void
    {
        $expired = LegalRequest::where('is_instant', true)
            ->whereIn('status', ['open', 'bidding'])
            ->where('deadline', '<', now())
            ->get();

        foreach ($expired as $req) {
            $req->update(['status' => 'expired', 'updated_at' => now()]);

            // User কে notification পাঠাও
            if ($req->user_id) {
                DB::table('user_notifications')->insert([
                    'user_id'    => $req->user_id,
                    'type'       => 'instant_expired',
                    'title'      => '⏰ Instant Request Expired',
                    'body'       => "Your instant legal request ({$req->request_id}) expired without a response. You can resubmit as a scheduled request.",
                    'data'       => json_encode(['request_id' => $req->request_id]),
                    'is_read'    => false,
                    'created_at' => now(),
                ]);
            }
        }

        $count = $expired->count();
        if ($count > 0) {
            $this->info("Expired {$count} instant request(s).");
        }
    }
}
