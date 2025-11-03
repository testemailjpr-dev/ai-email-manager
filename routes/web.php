<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\GmailController;
use App\Http\Controllers\GmailAccountController;


Route::get('/', fn() => redirect('/dashboard'));
Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn() => view('dashboard'))->name('dashboard');

	Route::resource('categories', CategoryController::class);
    Route::get('/categories/{id}/emails', [EmailController::class, 'index'])->name('category.emails.index');

    Route::post('/logout', function (\Illuminate\Http\Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    })->name('logout');

	Route::get('/emails', [GmailController::class, 'index'])->name('emails.index');
	Route::get('/emails/{id}', [GmailController::class, 'show'])->name('emails.show');

	Route::post('/emails/bulk-action', [EmailController::class, 'bulkAction'])->name('emails.bulkAction');
	Route::get('/emails/trash/{id}', [EmailController::class, 'actionTrash'])->name('emails.trash');
	Route::get('/emails/unsubscribe/{id}', [EmailController::class, 'actionUnsubscribe'])->name('emails.unsubscribe');
	
	Route::get('/google-accounts', [GmailAccountController::class, 'index'])->name('google.accounts.index');
});
