<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('login.form'));

Route::get('/login', [AuthController::class, 'showLogin'])->name('login.form');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register.form');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [WalletController::class, 'dashboard'])->name('dashboard');

    Route::get('/deposit', [WalletController::class, 'showDepositForm'])->name('deposit.form');
    Route::post('/deposit', [WalletController::class, 'deposit'])->name('deposit');

    Route::get('/transfer', [WalletController::class, 'showTransferForm'])->name('transfer.form');
    Route::post('/transfer', [WalletController::class, 'transfer'])->name('transfer');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions/{id}/reverse', [TransactionController::class, 'reverse'])->name('transactions.reverse');
});
