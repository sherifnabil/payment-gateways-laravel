<?php

use App\Http\Controllers\MyFatoorahController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymobController;
use App\Http\Controllers\PaypalController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\PayTabsController;

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

Route::view('paytabs-form', 'paytabs-form')->name('paytabs-form');
Route::post('paytabs', [PayTabsController::class, 'index'])->name('paytabs');

Route::view('myfatoorah-form', 'myfatoorah-form')->name('myfatoorah-form');
Route::post('myfatoorah-method', [MyFatoorahController::class, 'methodForm'])->name('myfatoorah-method');
Route::post('myfatoorah', [MyFatoorahController::class, 'index'])->name('myfatoorah');
Route::get('myfatoorah-callback', [MyFatoorahController::class, 'callback'])->name('myfatoorah.callback');
Route::get('myfatoorah-error', [MyFatoorahController::class, 'error'])->name('myfatoorah.error');
Route::get('myfatoorah-refund', [MyFatoorahController::class, 'refund'])->name('myfatoorah.refund');
