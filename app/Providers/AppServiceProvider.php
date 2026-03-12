<?php

namespace App\Providers;

use App\Listeners\AuthEventSubscriber;
use App\Models\CatheterRecord;
use App\Models\Patient;
use App\Observers\CatheterRecordObserver;
use App\Observers\PatientObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
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

        Patient::observe(PatientObserver::class);
        CatheterRecord::observe(CatheterRecordObserver::class);

        Event::subscribe(AuthEventSubscriber::class);
    }
}
