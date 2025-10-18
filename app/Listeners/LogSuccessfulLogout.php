<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout; // ← Correct import
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;

class LogSuccessfulLogout implements ShouldQueue
{
    use InteractsWithQueue;

    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle(Logout $event): void
    {
        // Only log if there's a user (not when session expires)
        if ($event->user) {
            activity()
                ->causedBy($event->user)
                ->withProperties([
                    'ip_address' => $this->request->ip(),
                    'user_agent' => $this->request->userAgent(),
                    'logout_time' => now()->toDateTimeString(),
                ])
                ->log('user_logout');
        }
    }
}
