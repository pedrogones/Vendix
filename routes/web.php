<?php

use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ClientAuthController;
use App\Http\Controllers\OrderStatusController;
use App\Http\Controllers\VitrineController;
use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Http\Controllers\Portal\HomeController::class, 'index'])->name('initial-page');
Route::get('/vendix-preview', [VitrineController::class, 'index'])->name('vitrine.preview');
Route::post('/chatbot/message', [ChatbotController::class, 'message'])
    ->middleware('throttle:chatbot')
    ->name('chatbot.message');

Route::get('/pedido/consultar', [OrderStatusController::class, 'show'])->name('order-status.show');
Route::post('/pedido/consultar', [OrderStatusController::class, 'lookup'])
    ->middleware('throttle:order-status')
    ->name('order-status.lookup');
Route::get('/pedido/t/{token}', [OrderStatusController::class, 'byToken'])
    ->middleware('throttle:order-status')
    ->name('order-status.token');

Route::get('login', fn () => view('portal.login'))->name('portal.login');
Route::post('login', [ClientAuthController::class, 'login']);

Route::get('register', fn () => view('portal.register'))->name('portal.register');
Route::post('register', [ClientAuthController::class, 'store'])->name('register.store');
Route::match(['get', 'post'], 'admin/logout', [ClientAuthController::class, 'logout'])->name('portal.logout');
