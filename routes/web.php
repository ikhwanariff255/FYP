<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;

Route::get('/', function () {
    return redirect('/admin/login');
});

// Alias Route
Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

Route::get('/register', function () {
    return redirect('/admin/register');
})->name('register');