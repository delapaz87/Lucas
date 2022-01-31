<?php

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
    return redirect('api/documentation');
});

Route::get('/mailable/otpvalidator', function () {
    return new App\Mail\OTPCodeValidator("Antonio","de la Paz","6332532");
});
