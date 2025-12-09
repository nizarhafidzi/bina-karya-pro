<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Project\Dashboard;
use App\Livewire\Project\DailyReportInput;
use App\Livewire\Project\WeeklyProgress;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Redirect root ke admin panel
Route::get('/', function () {
    return redirect('/app');
});

Route::get('/login', function () {
    return redirect()->route('filament.app.auth.login');
})->name('login');

// CUSTOM PROJECT ROUTES (Phase 5)
Route::middleware([
    'auth', // Harus Login
    // 'verified', // Opsional: Verifikasi email
    // Tambahkan middleware tenancy check kamu di sini jika perlu
])->prefix('project')->name('project.')->group(function () {

    // 1. Project Dashboard (Owner View & Manager View)
    // Bisa diakses oleh semua anggota tim yang berwenang
    Route::get('/{project}/dashboard', Dashboard::class)
        ->name('dashboard');

    // 2. Daily Report Input (Manager Only)
    // Policy check dilakukan di dalam Component mount()
    Route::get('/{project}/daily-report', DailyReportInput::class)
        ->name('daily-report');

    // 3. Weekly Progress Input (Manager Only)
    // Policy check dilakukan di dalam Component mount()
    Route::get('/{project}/weekly-progress', WeeklyProgress::class)
    ->name('weekly-progress');

});