<?php

use Illuminate\Support\Facades\Route;

Route::middleware([
    'web'
])->namespace('Hanoivip\PaymentMethodPaypal')->group(function () {
    Route::any('/paypal/callback', 'PaypalController@callback')->name('payment.paypal.callback');
});