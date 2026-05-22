namespace App\Console\Commands;
 
use Illuminate\Console\Command;
use App\Models\PiNotification;
use App\Models\UserNotification;
use Carbon\Carbon;
 
class SendPaymentReminders extends Command
{
    protected $signature   = 'notifications:payment-reminders';
    protected $description = 'Send 12-hour payment reminder to users before PI payment option expires';
 
    public function handle(): void
    {
        // PI notification যেগুলো payment_pending আছে এবং
        // sent_at থেকে 3 দিন পরে expire হবে → 3দিন - 12ঘণ্টা = 60ঘণ্টা পর reminder
        $remindAt = Carbon::now()->subHours(60); // sent_at 60 ঘণ্টা আগে হলে এখন reminder দাও
        $expireAt = Carbon::now()->subHours(72); // sent_at 72 ঘণ্টা আগে হলে already expired
 
        $pendingNotifs = PiNotification::where('status', 'payment_pending')
            ->whereBetween('sent_at', [$remindAt->copy()->subMinutes(5), $remindAt->copy()->addMinutes(5)])
            // ± 5 মিনিটের মধ্যে যেগুলো 60 ঘণ্টা আগে sent হয়েছে
            ->get();
 
        foreach ($pendingNotifs as $notif) {
            if (!$notif->user_id) continue;
 
            // Already reminder দেওয়া হয়েছে কিনা check (duplicate avoid)
            $alreadyNotified = UserNotification::where('user_id', $notif->user_id)
                ->where('complaint_id', $notif->complaint_id)
                ->where('type', 'pi_payment_reminder')
                ->exists();
 
            if ($alreadyNotified) continue;
 
            UserNotification::notify(
                $notif->user_id,
                'pi_payment_reminder',
                '⏰ Payment Option Closes in 12 Hours!',
                "Complaint {$notif->complaint_id} এর জন্য PI payment option আর মাত্র 12 ঘণ্টা থাকবে। এখনই payment করো নাহলে option বন্ধ হয়ে যাবে।",
                [
                    'complaint_id' => $notif->complaint_id,
                    'action_url'   => '/dashboard', // dashboard এ গিয়ে payment করবে
                    'icon'         => '⏰',
                ]
            );
 
            $this->info("Reminder sent to user {$notif->user_id} for complaint {$notif->complaint_id}");
        }
    }
}