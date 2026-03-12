<?php

namespace App\Listeners;

use App\Models\AuthLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class AuthEventSubscriber
{
    public function handleLogin(Login $event): void
    {
        AuthLog::create([
            'user_id'    => $event->user->id,
            'email'      => $event->user->email,
            'event'      => 'LOGIN',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function handleLogout(Logout $event): void
    {
        AuthLog::create([
            'user_id'    => $event->user?->id,
            'email'      => $event->user?->email,
            'event'      => 'LOGOUT',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function handleFailed(Failed $event): void
    {
        AuthLog::create([
            'user_id'    => null,
            'email'      => $event->credentials['email'] ?? null,
            'event'      => 'LOGIN_FAILED',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function subscribe(): array
    {
        return [
            Login::class  => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
        ];
    }
}
