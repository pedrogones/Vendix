<?php

use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ClientAuthController;
use App\Http\Controllers\VitrineController;
use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Http\Controllers\Portal\HomeController::class, 'index'])->name('initial-page');
Route::get('/vendix-preview', [VitrineController::class, 'index'])->name('vitrine.preview');
Route::post('/chatbot/message', [ChatbotController::class, 'message'])->name('chatbot.message');

Route::get('login', fn () => view('portal.login'))->name('portal.login');
Route::post('login', [ClientAuthController::class, 'login']);

Route::get('register', fn () => view('portal.register'))->name('portal.register');
Route::post('register', [ClientAuthController::class, 'store'])->name('register.store');
Route::match(['get', 'post'], 'admin/logout', [ClientAuthController::class, 'logout'])->name('portal.logout');
