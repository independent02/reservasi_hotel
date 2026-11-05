<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccountPasswordController;
use App\Http\Controllers\BookingCancellationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\HotelReviewController;
use App\Http\Controllers\ManualPaymentController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/locale/{locale}', [LocaleController::class, 'update'])
    ->whereIn('locale', ['id', 'en'])
    ->name('locale.switch');
Route::get('/hotel/search', [HomeController::class, 'search'])->name('hotels.search');
Route::get('/hotels/{hotel:slug}/reviews', [HotelReviewController::class, 'show'])->name('hotels.reviews.show');
Route::get('/reviews/{review}/media/{index}', [HotelReviewController::class, 'media'])
    ->whereNumber('index')
    ->name('reviews.media');
Route::get('/rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');
Route::middleware('guest')->group(function (): void {
    Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle'])
        ->name('auth.google.redirect');
    Route::get('/auth/google/password-reset', [GoogleController::class, 'redirectToGoogleForPasswordReset'])
        ->name('auth.google.password-reset');
    Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback'])
        ->name('auth.google.callback');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1')
        ->name('login.store');
    Route::view('/forgot-password', 'auth.forgot-password')->name('password.request');
    Route::get('/admin/login', [AuthController::class, 'showAdminLogin'])->name('admin.login');
    Route::post('/admin/login', [AuthController::class, 'adminLogin'])
        ->middleware('throttle:6,1')
        ->name('admin.login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:6,1')
        ->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/account/password', [AccountPasswordController::class, 'edit'])
        ->name('account.password.edit');
    Route::put('/account/password', [AccountPasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('account.password.update');
    Route::get('/rooms/{room}/reserve', [ReservationController::class, 'create'])->name('reservations.create');
    Route::get('/rooms/{room}/availability', [ReservationController::class, 'availability'])
        ->name('reservations.availability');
    Route::post('/rooms/{room}/selection-locks', [ReservationController::class, 'lockSelection'])
        ->name('reservations.lock-selection');
    Route::post('/rooms/{room}/reserve/guest', [ReservationController::class, 'continueToGuest'])
        ->name('reservations.continue-to-guest');
    Route::get('/rooms/{room}/reserve/guest', [ReservationController::class, 'guestDetails'])
        ->name('reservations.guest');
    Route::post('/rooms/{room}/reserve', [ReservationController::class, 'store'])->name('reservations.store');
    Route::get('/my-reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::delete('/bookings/{reservation}/cancel', [BookingCancellationController::class, 'destroy'])
        ->name('bookings.cancel');
    Route::post('/reservations/{reservation}/reviews', [HotelReviewController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('reviews.store');
    Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy'])->name('reservations.destroy');
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
    Route::post('/reservations/{reservation}/payment-proof', [ManualPaymentController::class, 'uploadProof'])
        ->middleware('throttle:6,1')
        ->name('reservations.payment.proof');
    Route::get('/reservations/{reservation}/voucher', [ReservationController::class, 'voucher'])
        ->name('reservations.voucher');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', [ManualPaymentController::class, 'dashboard'])->name('dashboard');
    Route::prefix('payment-reviews')->name('payment-reviews.')->group(function (): void {
        Route::get('/', [ManualPaymentController::class, 'index'])->name('index');
        Route::get('/{reservation}/proof', [ManualPaymentController::class, 'proof'])->name('proof');
        Route::patch('/{reservation}', [ManualPaymentController::class, 'review'])->name('review');
        Route::delete('/{reservation}', [ManualPaymentController::class, 'destroy'])->name('destroy');
    });
});
