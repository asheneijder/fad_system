<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;

class LogFailedLogin implements ShouldQueue
{
    use InteractsWithQueue;

    protected Request $request;

    /**
     * Create the event listener.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        // Get user agent and IP address
        $userAgent = $this->request->userAgent();
        $ipAddress = $this->request->ip();

        activity()
            ->withProperties([
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'login_time' => now()->toDateTimeString(),
                'email' => $event->credentials['email'] ?? 'unknown',
                'attempt_failed' => true,
            ])
            ->log('failed_login');
    }
}
