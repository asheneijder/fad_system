<?php

namespace App\Console\Commands;

use App\Jobs\SendDailyPendingReminder;
use App\Models\AccommodationClaim;
use App\Models\DailyAllowance;
use App\Models\TransportationClaim;
use App\Models\TravelClaim;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendPendingClaimsReminder extends Command
{
    protected $signature = 'claims:send-reminders';

    protected $description = 'Send daily reminders for pending claims to approvers';

    public function handle()
    {
        // Only run on weekdays (Monday to Friday)
        if (! now()->isWeekday()) {
            $this->info('Today is weekend, no reminders sent.');

            return;
        }

        $this->info('Sending daily pending claims reminders...');

        // Get all approvers
        $approvers = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['hod-approver', 'fad-approver', 'system-admin']);
        })->get();

        if ($approvers->isEmpty()) {
            $this->warn('No approvers found.');

            return;
        }

        $totalRemindersSent = 0;

        foreach ($approvers as $approver) {
            // Get pending claims for this approver
            $pendingClaims = $this->getPendingClaimsForApprover($approver);

            if ($pendingClaims->isNotEmpty()) {
                // Send reminder for this approver
                dispatch(new SendDailyPendingReminder($approver, $pendingClaims));
                $totalRemindersSent++;

                $this->info("Reminder queued for {$approver->name} ({$approver->email}) - {$pendingClaims->count()} pending claims");
            }
        }

        $this->info("Daily reminders completed. {$totalRemindersSent} approvers notified.");
        Log::info("Daily pending claims reminders sent to {$totalRemindersSent} approvers");
    }

    private function getPendingClaimsForApprover(User $approver)
    {
        $travelClaims = TravelClaim::where('approver_id', $approver->id)
            ->whereIn('status', ['submitted', 'pending'])
            ->with('user')
            ->get()
            ->map(function ($claim) {
                $daysPending = $claim->created_at->diffInDays(now());
                $hoursPending = $claim->created_at->diffInHours(now());

                return [
                    'id' => $claim->id,
                    'type' => 'travel',
                    'type_display' => 'Travel',
                    'user_name' => $claim->user->name,
                    'purpose' => $claim->purpose,
                    'amount' => $claim->total_cost,
                    'submitted_date' => $claim->created_at->format('M d, Y H:i'),
                    'days_pending' => $daysPending,
                    'hours_pending' => $hoursPending,
                    'pending_display' => $this->formatPendingTime($claim->created_at),
                ];
            });

        $dailyClaims = DailyAllowance::where('approver_id', $approver->id)
            ->whereIn('status', ['submitted', 'pending'])
            ->with('user')
            ->get()
            ->map(function ($claim) {
                $daysPending = $claim->created_at->diffInDays(now());
                $hoursPending = $claim->created_at->diffInHours(now());

                return [
                    'id' => $claim->id,
                    'type' => 'daily',
                    'type_display' => 'Daily Allowance',
                    'user_name' => $claim->user->name,
                    'purpose' => $claim->purpose,
                    'amount' => $claim->claim_amount,
                    'submitted_date' => $claim->created_at->format('M d, Y H:i'),
                    'days_pending' => $daysPending,
                    'hours_pending' => $hoursPending,
                    'pending_display' => $this->formatPendingTime($claim->created_at),
                ];
            });

        $accommodationClaims = AccommodationClaim::where('approver_id', $approver->id)
            ->where('status', AccommodationClaim::STATUS_SUBMITTED)
            ->with('user')
            ->get()
            ->map(function ($claim) {
                $daysPending = $claim->created_at->diffInDays(now());
                $hoursPending = $claim->created_at->diffInHours(now());

                return [
                    'id' => $claim->id,
                    'type' => 'accommodation',
                    'type_display' => 'Accommodation',
                    'user_name' => $claim->user->name,
                    'purpose' => $claim->purpose,
                    'amount' => $claim->total_amount,
                    'submitted_date' => $claim->created_at->format('M d, Y H:i'),
                    'days_pending' => $daysPending,
                    'hours_pending' => $hoursPending,
                    'pending_display' => $this->formatPendingTime($claim->created_at),
                ];
            });

        $transportationClaims = TransportationClaim::where('approver_id', $approver->id)
            ->where('status', TransportationClaim::STATUS_SUBMITTED)
            ->with('user')
            ->get()
            ->map(function ($claim) {
                $daysPending = $claim->created_at->diffInDays(now());
                $hoursPending = $claim->created_at->diffInHours(now());

                return [
                    'id' => $claim->id,
                    'type' => 'transportation',
                    'type_display' => 'Transportation',
                    'user_name' => $claim->user->name,
                    'purpose' => $claim->purpose,
                    'amount' => $claim->amount,
                    'submitted_date' => $claim->created_at->format('M d, Y H:i'),
                    'days_pending' => $daysPending,
                    'hours_pending' => $hoursPending,
                    'pending_display' => $this->formatPendingTime($claim->created_at),
                ];
            });

        return $travelClaims->merge($dailyClaims)
            ->merge($accommodationClaims)
            ->merge($transportationClaims);
    }

    /**
     * Format pending time in a human-readable way
     */
    private function formatPendingTime($createdAt)
    {
        $days = $createdAt->diffInDays(now());
        $hours = $createdAt->diffInHours(now());

        if ($days > 0) {
            return $days.' day'.($days > 1 ? 's' : '');
        }

        if ($hours > 0) {
            return $hours.' hour'.($hours > 1 ? 's' : '');
        }

        $minutes = $createdAt->diffInMinutes(now());

        return $minutes.' minute'.($minutes > 1 ? 's' : '');
    }
}
