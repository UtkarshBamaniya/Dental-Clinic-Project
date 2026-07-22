<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BranchMasterController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\RoleMasterController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StaffController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/book-appointment', [AppointmentController::class, 'publicCreate'])->name('public.booking');
Route::post('/book-appointment', [AppointmentController::class, 'publicStore'])->name('public.booking.store');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/inquiries', [InquiryController::class, 'index'])->name('inquiries.index');
    Route::post('/inquiries', [InquiryController::class, 'store'])->name('inquiries.store');
    Route::patch('/inquiries/{inquiry}/converted', [InquiryController::class, 'markConverted'])->name('inquiries.converted');
    Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
    Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status');
    Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
    Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
    Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
    Route::post('/finance/payments', [FinanceController::class, 'storePayment'])->name('finance.payments.store');
    Route::post('/finance/expenses', [FinanceController::class, 'storeExpense'])->name('finance.expenses.store');
    Route::post('/finance/payroll', [FinanceController::class, 'storePayroll'])->name('finance.payroll.store');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'admin'])->prefix('masters')->name('masters.')->group(function () {
    Route::get('/branches', [BranchMasterController::class, 'index'])->name('branches.index');
    Route::post('/branches', [BranchMasterController::class, 'store'])->name('branches.store');
    Route::put('/branches/{branch}', [BranchMasterController::class, 'update'])->name('branches.update');
    Route::delete('/branches/{branch}', [BranchMasterController::class, 'destroy'])->name('branches.destroy');

    Route::get('/users', [StaffController::class, 'index'])->name('users.index');
    Route::post('/users', [StaffController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [StaffController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [StaffController::class, 'destroy'])->name('users.destroy');

    Route::get('/roles', [RoleMasterController::class, 'index'])->name('roles.index');
    Route::post('/roles', [RoleMasterController::class, 'store'])->name('roles.store');
    Route::put('/roles/{role}', [RoleMasterController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [RoleMasterController::class, 'destroy'])->name('roles.destroy');
});

Route::middleware(['auth', 'admin'])->get('/staff', fn () => redirect()->route('masters.users.index'))->name('staff.index');

require __DIR__.'/auth.php';
