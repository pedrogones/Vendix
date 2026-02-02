<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientAuthController;
use Joaopaulolndev\FilamentEditProfile\Pages\EditProfilePage;

Route::get('/', [\App\Http\Controllers\Portal\HomeController::class, 'index'])->name('initial-page');
Route::get('login', fn () => view('portal.login'))->name('portal.login');
Route::post('login', [ClientAuthController::class, 'login']);

Route::get('register', fn () => view('portal.register'))->name('portal.register');
Route::post('register', [ClientAuthController::class, 'store'])->name('register.store');
Route::match(['get', 'post'], 'admin/logout', [ClientAuthController::class, 'logout'])->name('portal.logout');
