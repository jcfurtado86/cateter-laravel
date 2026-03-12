<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Dashboard;
use App\Livewire\Patients;
use App\Livewire\PatientDetail;
use App\Livewire\Catheters;
use App\Livewire\Users;
use App\Livewire\Logs;
use App\Livewire\Notifications;

Route::redirect('/', '/dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/patients', Patients::class)->name('patients');
    Route::get('/patients/{id}', PatientDetail::class)->name('patients.show');
    Route::get('/catheters', Catheters::class)->name('catheters');
    Route::get('/notifications', Notifications::class)->name('notifications');
    Route::get('/users', Users::class)->name('users');
    Route::get('/logs', Logs::class)->name('logs');
});

require __DIR__.'/auth.php';
