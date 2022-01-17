<?php

use App\Http\Controllers\PaymobController;
use App\Http\Controllers\PaypalController;
use App\Http\Controllers\StripeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::view('paypal-form', 'paypal');
Route::post('paypal', [PaypalController::class, 'index'])->name('paypal');
Route::get('paypal/return', [PaypalController::class, 'paypalReturn'])->name('paypal.return');
Route::get('paypal/cancel', [PaypalController::class, 'paypalCancel'])->name('paypal.cancel');

Route::view('stripe-form', 'stripe-form')->name('stripe-form');
Route::post('stripe', [StripeController::class, 'stripe'])->name('stripe');
Route::get('stripe-success', [StripeController::class, 'success'])->name('stripe.success');
Route::get('stripe-cancel', [StripeController::class, 'cancel'])->name('stripe.cancel');

Route::view('paymob-form', 'paymob-form')->name('paymob-form');
Route::get('paymob-callback', [PaymobController::class, 'callback']);
Route::post('paymob', [PaymobController::class, 'index'])->name('paymob');
