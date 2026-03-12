<?php

namespace App\Providers;

use App\Listeners\AuthEventSubscriber;
use App\Models\CatheterRecord;
use App\Models\Notification;
use App\Models\Patient;
use App\Observers\CatheterRecordObserver;
use App\Observers\NotificationObserver;
use App\Observers\PatientObserver;
use App\Models\User;
use App\Policies\CatheterRecordPolicy;
use App\Policies\PatientPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.default');
        Paginator::defaultSimpleView('vendor.pagination.simple-default');

        Gate::define('admin-only', fn(User $user) => $user->role === 'ADMIN');
        Gate::policy(Patient::class, PatientPolicy::class);
        Gate::policy(CatheterRecord::class, CatheterRecordPolicy::class);

        Patient::observe(PatientObserver::class);
        CatheterRecord::observe(CatheterRecordObserver::class);
        Notification::observe(NotificationObserver::class);

        Event::subscribe(AuthEventSubscriber::class);
    }
}
