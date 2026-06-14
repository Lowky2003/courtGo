<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

// Temporary smoke route to verify role-based access (replaced by the real owner dashboard in Phase 2)
Route::get('/owner', fn () => 'Owner area')
    ->middleware(['auth', 'role:owner'])
    ->name('owner.dashboard');

require __DIR__.'/settings.php';
