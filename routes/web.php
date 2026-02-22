<?php

use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\RewardController;
use App\Http\Controllers\AMLevelUpController;
use App\Http\Controllers\BackController;



Route::get('/', [AMLevelUpController::class, 'getReportData'])->name('home');

Route::post('/login', [BackController::class, 'login'])->name('login');
Route::post('/logout', [BackController::class, 'logout'])->name('logout');

Route::post('/redeem', [AMLevelUpController::class, 'redeemPrize'])->name('redeem');
