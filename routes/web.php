<?php

use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-email', function () {
    $user = User::first();
    Mail::to('g@apss.com')->send(new WelcomeMail($user));
});

Auth::routes();

Route::middleware(['auth:sanctum', 'verified'])->group(function () {});

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
