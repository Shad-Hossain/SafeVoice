<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\LegalRequest;

class ExpireLegalRequests extends Command
{
    protected $signature   = 'legal:expire';
    protected $description = 'Deadline পার হওয়া legal requests expire করো এবং user কে notify করো';

    public function handle(): void
    {
        $expired = LegalRequest::whereIn('status', ['open', 'bidding'])
            ->whereNotNull('deadline')
            ->where('deadline', '<=', now())
            ->where('deadline_notified', false)
            ->get();

        foreach ($expired as $req) {
            DB::transaction(function () use ($req) {
                $req->update(['status' => 'expired', 'deadline_notified' => true, 'updated_at' => now()]);

                // User কে notification
                if ($req->user_id) {
                    $bidCount = $req->bids()->count();
                    $msg = $bidCount > 0
                        ? "Your legal request ({$req->request_id}) deadline has passed. {$bidCount} lawyer(s) placed bids — please review and choose one, or resubmit with a higher budget."
                        : "Your legal request ({$req->request_id}) received no bids before the deadline. Consider increasing your budget and resubmitting.";

                    DB::table('user_notifications')->insert([
                        'user_id'    => $req->user_id,
                        'type'       => 'legal_request_expired',
                        'title'      => $bidCount > 0 ? '⚖️ Your legal request deadline passed — bids waiting!' : '⏰ No lawyers responded to your request',
                        'body'       => $msg,
                        'data'       => json_encode([
                            'request_id' => $req->request_id,
                            'bid_count'  => $bidCount,
                            'action'     => $bidCount > 0 ? 'review_bids' : 'increase_budget',
                        ]),
                        'is_read'    => false,
                        'created_at' => now(),
                    ]);
                }

                // Pending lawyer notifications কে dismiss করো
                DB::table('lawyer_notifications')
                    ->where('type', 'new_request')
                    ->whereJsonContains('data->request_id', $req->request_id)
                    ->where('is_read', false)
                    ->update(['is_read' => true]);
            });

            $this->info("Expired: {$req->request_id}");
        }

        $this->info("Done. {$expired->count()} request(s) expired.");
    }
}
